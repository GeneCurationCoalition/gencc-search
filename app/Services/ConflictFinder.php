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
 * A group is "conflicting" when at least one live, published submission has a
 * classification on the vocabulary's strong side (Definitive, Strong, or
 * Moderate) and at least one is on the other side (Limited, Disputed, Refuted,
 * or No Known Disease Relationship). Database IDs and order values do not carry
 * classification meaning.
 */
class ConflictFinder
{
    /**
     * Cache key for the computed conflict set.
     *
     * The cached value is a nested array that Blade and Livewire destructure by
     * key, and the cache is not ephemeral: CACHE_DRIVER=file with storage/
     * bind-mounted from the host, so entries survive a redeploy. If the shape of
     * that array changes, run `php artisan conflicts:clear-cache` after deploying,
     * or wait out CACHE_HOURS.
     */
    const CACHE_KEY = 'conflict-viewer.triples.v5';

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
     * Drop the cached conflict set so the next request recomputes it.
     *
     * @return void
     */
    public static function flush()
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Fold every live, published submission into gene/disease/MOI groups and keep
     * the ones that hold both D/S/M and L/P/R/N assertions.
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
            ->where('s.is_live', true)
            ->where('s.status', Submission::STATUS_PUBLISHED)
            ->select(
                's.gene_id',
                's.disease_id',
                's.inheritance_id',
                'g.symbol as gene_symbol',
                'g.hgnc_id',
                'd.curie as disease_curie',
                'd.name as disease_name',
                'i.name as moi',
                'c.curie as classification_curie',
                'c.name as classification',
                'sub.name as submitter'
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
                    'hgnc_id'       => $row->hgnc_id,
                    'disease_curie' => $row->disease_curie,
                    'disease_name'  => $row->disease_name,
                    'moi'           => $row->moi ?: 'Unknown',
                    'strong'        => [],
                    'other'         => [],
                    'submitter_slugs' => [],
                    'strong_count'  => 0,
                    'other_count'   => 0,
                    'total_count'   => 0,
                ];
            }

            $group = &$groups[$key];
            $submitter = $row->submitter ?: 'Unknown';

            // Submitter => classification CURIE => presentation metadata, so a submitter
            // appears once per side. The priority is carried so orderSide()
            // can rank both the submitters and each submitter's own pills by strength;
            // the stable CURIE prevents mutable display names from defining identity.
            $group[$side][$submitter][$row->classification_curie] = [
                'curie'     => $row->classification_curie,
                'label'     => $row->classification,
                'css_class' => $metadata['css_class'],
                'priority'  => $metadata['priority'],
            ];
            $group[$side . '_count']++;
            $group['total_count']++;

            // Slug => name for the other-side submitters. The slug is the facet key:
            // submitters.ident is a UUID, which would be unreadable in a shared URL
            // and is not stable across database reloads.
            if ($side === 'other') {
                $group['submitter_slugs'][Str::slug($submitter)] = $submitter;
            }

            unset($group);
        }

        return collect($groups)
            ->filter(fn ($group) => $group['strong_count'] > 0 && $group['other_count'] > 0)
            ->map(function ($group) {
                $group['strong']        = self::orderSide($group['strong']);
                $group['other']         = self::orderSide($group['other']);

                return $group;
            })
            ->sortByDesc('total_count')
            ->values();
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
