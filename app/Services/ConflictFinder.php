<?php

namespace App\Services;

use App\Classification;
use App\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Finds gene + disease + mode-of-inheritance groups where curating groups disagree.
 *
 * A group is "conflicting" when at least one live, published, non-deleted
 * submission has a classification on the vocabulary's strong side (Definitive,
 * Strong, or Moderate) and at least one is on the other side (Limited, Disputed,
 * Refuted, or No Known Disease Relationship). Database IDs and order values do
 * not carry classification meaning.
 */
class ConflictFinder
{
    /**
     * Cache key for the computed conflict set.
     *
     * The cached value is a nested array that Blade and Livewire destructure by
     * key, and the cache is not ephemeral: CACHE_DRIVER=file with storage/
     * bind-mounted from the host, so entries survive a redeploy. Advance this
     * key when the array shape or membership rules change so stale data is not
     * reused after deployment.
     */
    const CACHE_KEY = 'conflict-viewer.triples.v8';

    /** How long the computed conflict set stays cached. */
    const CACHE_HOURS = 6;

    /**
     * The conflicting gene/disease/MOI groups, sorted by submission count descending.
     *
     * @return \Illuminate\Support\Collection<int, array>
     */
    public static function conflicts(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addHours(self::CACHE_HOURS),
            fn () => self::compute()
        );
    }

    /**
     * Return conflicts after removing submissions that cannot be downloaded.
     *
     * Eligibility is recomputed at the group level. A public conflict whose
     * downloadable submissions all fall on one side is therefore no longer a
     * conflict and is dropped entirely.
     *
     * Currently uncalled. The conflict download stopped filtering on
     * `downloadable` so that it matches both the public conflict page and
     * gencc-sub's release export, neither of which honours the flag. This method
     * is retained as the reference implementation for the pending work that
     * applies the flag consistently across both exports.
     */
    public static function downloadableConflicts(): Collection
    {
        $downloadableSubmitterIds = DB::table('submitters')
            ->where('downloadable', true)
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(string) $id => true])
            ->all();

        return self::conflicts()
            ->map(function ($group) use ($downloadableSubmitterIds) {
                $submissions = collect($group['submissions'])
                    ->filter(fn ($submission) => isset($downloadableSubmitterIds[(string) $submission['submitter_id']]))
                    ->values()
                    ->all();

                return self::summarizeGroup($group, $submissions);
            })
            ->filter(fn ($group) => $group['strong_count'] > 0 && $group['other_count'] > 0)
            ->values();
    }

    /**
     * Drop the cached conflict set so the next request recomputes it.
     *
     * @return void
     */
    public static function flush()
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Fold every live, published, non-deleted submission into gene/disease/MOI
     * groups and keep the ones that hold both D/S/M and L/P/R/N assertions.
     *
     * Uses the query builder rather than Eloquent: App\Submission eager-loads six
     * relations via $with, which makes a 30k-row fetch unusable.
     *
     * @return \Illuminate\Support\Collection<int, array>
     */
    protected static function compute(): Collection
    {
        $groups = [];

        $rows = DB::table('submissions as s')
            ->join('genes as g', 'g.id', '=', 's.gene_id')
            ->join('diseases as d', 'd.id', '=', 's.disease_id')
            ->join('classifications as c', 'c.id', '=', 's.classification_id')
            ->join('submitters as sub', 'sub.id', '=', 's.submitter_id')
            ->leftJoin('inheritances as i', 'i.id', '=', 's.inheritance_id')
            ->leftJoin('diseases as original_d', 'original_d.id', '=', 's.original_disease_id')
            ->where('s.is_live', true)
            ->where('s.status', Submission::STATUS_PUBLISHED)
            ->whereNull('s.deleted_at')
            ->select(
                's.gene_id',
                's.disease_id',
                's.inheritance_id',
                's.sid as sgc_id',
                's.version_number',
                's.report_date',
                'g.symbol as gene_symbol',
                'g.hgnc_id as gene_curie',
                'g.hgnc_id',
                'd.curie as disease_curie',
                'd.name as disease_name',
                'original_d.curie as disease_original_curie',
                'original_d.name as disease_original_name',
                'i.curie as moi_curie',
                'i.name as moi_title',
                'c.curie as classification_curie',
                'c.name as classification',
                'sub.curie as submitter_curie',
                'sub.name as submitter',
                'sub.id as submitter_id'
            )
            ->orderBy('s.id')
            ->cursor();

        foreach ($rows as $row) {
            $metadata = Classification::VOCABULARY[$row->classification_curie] ?? null;
            $side = $metadata['conflict_side'] ?? null;

            // Supportive, Animal Model Only, placeholders, and future terms do
            // not participate unless the vocabulary explicitly assigns one of
            // the two recognized conflict sides.
            if (! in_array($side, ['strong', 'other'], true)) {
                continue;
            }

            $key = $row->gene_id . '|' . $row->disease_id . '|' . $row->inheritance_id;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key'           => $key,
                    'gene_symbol'   => $row->gene_symbol,
                    'gene_curie'    => $row->gene_curie,
                    'hgnc_id'       => $row->hgnc_id,
                    'disease_curie' => $row->disease_curie,
                    'disease_name'  => $row->disease_name,
                    'moi'           => $row->moi_title ?: 'Unknown',
                    'moi_curie'     => $row->moi_curie,
                    'submissions'   => [],
                ];
            }

            $group = &$groups[$key];
            $submitter = $row->submitter ?: 'Unknown';

            // Keep every participating submission. Display summaries are built
            // from this list below, and downloads use it without collapsing
            // legacy duplicates or normalization collisions.
            $group['submissions'][] = [
                'sgc_id'                    => $row->sgc_id,
                'version_number'            => $row->version_number,
                'gene_curie'                => $row->gene_curie,
                'gene_symbol'               => $row->gene_symbol,
                'disease_curie'             => $row->disease_curie,
                'disease_title'             => $row->disease_name,
                'disease_original_curie'    => $row->disease_original_curie,
                'disease_original_title'    => $row->disease_original_name,
                'moi_curie'                 => $row->moi_curie,
                'moi_title'                 => $row->moi_title ?: 'Unknown',
                'classification_group'      => $side === 'strong' ? 'D/S/M' : 'L/P/R/N',
                'classification_curie'      => $row->classification_curie,
                'classification_title'      => $row->classification,
                'classification_priority'   => $metadata['priority'],
                'classification_css_class'  => $metadata['css_class'],
                'conflict_side'              => $side,
                'submitter_curie'            => $row->submitter_curie,
                'submitter_id'               => $row->submitter_id,
                'submitter_title'            => $submitter,
                'submitter_slug'             => Str::slug($submitter),
                'submitted_as_date'          => $row->report_date,
            ];

            unset($group);
        }

        return collect($groups)
            ->map(fn ($group) => self::summarizeGroup($group, $group['submissions']))
            ->filter(fn ($group) => $group['strong_count'] > 0 && $group['other_count'] > 0)
            ->sortByDesc('total_count')
            ->values();
    }

    /** Build the viewer summary fields from a group of participating submissions. */
    protected static function summarizeGroup(array $group, array $submissions): array
    {
        $strong = [];
        $other = [];
        $submitterSlugs = [];
        $strongCount = 0;
        $otherCount = 0;

        foreach ($submissions as $submission) {
            $side = $submission['conflict_side'];
            $submitter = $submission['submitter_title'];
            ${$side}[$submitter][$submission['classification_curie']] = [
                'curie' => $submission['classification_curie'],
                'label' => $submission['classification_title'],
                'css_class' => $submission['classification_css_class'],
                'priority' => $submission['classification_priority'],
            ];

            if ($side === 'strong') {
                $strongCount++;
            } else {
                $otherCount++;
                $submitterSlugs[$submission['submitter_slug']] = $submitter;
            }
        }

        $group['submissions'] = array_values($submissions);
        $group['strong'] = self::orderSide($strong);
        $group['other'] = self::orderSide($other);
        $group['submitter_slugs'] = $submitterSlugs;
        $group['strong_count'] = $strongCount;
        $group['other_count'] = $otherCount;
        $group['total_count'] = $strongCount + $otherCount;

        return $group;
    }

    /**
     * Rank one side of a conflict by evidence strength, then by submitter name.
     *
     * Both levels are ordered: each submitter's own classifications are listed
     * strongest-first, and the submitters themselves are ranked by the strongest
     * classification they assert. Reading a row top-to-bottom therefore walks
     * from the strongest assertion to the weakest, which is what the removed
     * "Range" column used to state explicitly.
     *
     * The submitter name breaks ties so the output is deterministic. Without it
     * the order fell out of `submissions.id` — stable in practice but arbitrary,
     * and free to change whenever a submitter reloads its data.
     *
     * Takes submitter => [classification CURIE => metadata] and returns
     * submitter => [metadata, ...], which is the shape Blade iterates.
     *
     * @param  array  $side
     * @return array
     */
    protected static function orderSide(array $side): array
    {
        $submitters = [];

        foreach ($side as $submitter => $classifications) {
            // Ascending vocabulary priority == canonical display order.
            uasort($classifications, fn ($a, $b) => $a['priority'] <=> $b['priority']);

            $submitters[] = [
                'name'            => $submitter,
                'strongest_order' => (int) reset($classifications)['priority'],
                'classifications' => array_values($classifications),
            ];
        }

        // mb_strtolower so casing does not outrank the alphabet: a byte comparison
        // sorts every capitalised name ahead of every lowercased one.
        usort($submitters, fn ($a, $b) => [$a['strongest_order'], mb_strtolower($a['name'])]
            <=> [$b['strongest_order'], mb_strtolower($b['name'])]);

        $ordered = [];

        foreach ($submitters as $submitter) {
            $ordered[$submitter['name']] = $submitter['classifications'];
        }

        return $ordered;
    }
}
