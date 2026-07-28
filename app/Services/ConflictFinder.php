<?php

namespace App\Services;

use App\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Finds gene + disease + mode-of-inheritance groups where curating groups disagree.
 *
 * A group is "conflicting" when at least one live, published submission asserts
 * strong evidence (Definitive, Strong or Moderate — classifications.order <= 30)
 * and at least one asserts something weaker.
 *
 * Ranking is done strictly on classifications.order because classifications.slug
 * is NULL for every row in the production data, and the classification ids are not
 * in strength order (GENCC:100009 Supportive is id 4).
 */
class ConflictFinder
{
    /** Cache key for the computed conflict set. Bump the suffix when the shape changes. */
    const CACHE_KEY = 'conflict-viewer.triples.v2';

    /** Highest classifications.order still considered "strong" (Definitive 10, Strong 20, Moderate 30). */
    const STRONG_MAX_ORDER = 30;

    /** Lowest weakest-classification order that makes a conflict "vs Limited" (Limited 50). */
    const TIER_LIMITED_MIN_ORDER = 50;

    /**
     * Lowest weakest-classification order that makes a conflict "vs Contradictory":
     * Disputed Evidence 60, Refuted Evidence 70, Animal Model Only 80,
     * No Known Disease Relationship 90.
     */
    const TIER_CONTRADICTORY_MIN_ORDER = 60;

    const TIER_SUPPORTIVE    = 'supportive';
    const TIER_LIMITED       = 'limited';
    const TIER_CONTRADICTORY = 'contradictory';

    /**
     * Severity tiers in strength order, strongest-conflict-last. Drives facet display order.
     *
     * The labels name the classifications involved rather than editorialising about
     * them — a Supportive-only submitter cannot express agreement with a Definitive,
     * so "vs Supportive" is a statement about vocabularies, not about disagreement.
     *
     * @var array
     */
    const TIER_LABELS = [
        self::TIER_SUPPORTIVE    => 'Strong evidence vs Supportive',
        self::TIER_LIMITED       => 'Strong evidence vs Limited',
        self::TIER_CONTRADICTORY => 'Strong evidence vs Contradictory',
    ];

    /** How long the computed conflict set stays cached. */
    const CACHE_HOURS = 6;

    /**
     * The severity tier for a group, keyed off the weakest classification present.
     *
     * Public and static so it can be exercised without building fixture rows.
     *
     * @param  int  $maxOrder
     * @return string
     */
    public static function tierFor(int $maxOrder): string
    {
        if ($maxOrder >= self::TIER_CONTRADICTORY_MIN_ORDER) {
            return self::TIER_CONTRADICTORY;
        }

        if ($maxOrder >= self::TIER_LIMITED_MIN_ORDER) {
            return self::TIER_LIMITED;
        }

        return self::TIER_SUPPORTIVE;
    }

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
     * the ones that hold both strong and weaker assertions.
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
                'c.name as classification',
                'c.order as classification_order',
                'sub.name as submitter'
            )
            ->orderBy('s.id')
            ->cursor();

        foreach ($rows as $row) {
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
                    'other_slugs'   => [],
                    'strong_count'  => 0,
                    'other_count'   => 0,
                    'total_count'   => 0,
                    'min_order'     => $row->classification_order,
                    'max_order'     => $row->classification_order,
                    'strongest'     => $row->classification,
                    'weakest'       => $row->classification,
                ];
            }

            $group    = &$groups[$key];
            $side     = $row->classification_order <= self::STRONG_MAX_ORDER ? 'strong' : 'other';
            $submitter = $row->submitter ?: 'Unknown';

            // Submitter => set of classifications, so a submitter appears once per side.
            $group[$side][$submitter][$row->classification] = $row->classification;
            $group[$side . '_count']++;
            $group['total_count']++;

            // Slug => name for the dissenting submitters. The slug is the facet key:
            // submitters.ident is a UUID, which would be unreadable in a shared URL
            // and is not stable across database reloads.
            if ($side === 'other') {
                $group['other_slugs'][Str::slug($submitter)] = $submitter;
            }

            if ($row->classification_order < $group['min_order']) {
                $group['min_order'] = $row->classification_order;
                $group['strongest'] = $row->classification;
            }

            if ($row->classification_order > $group['max_order']) {
                $group['max_order'] = $row->classification_order;
                $group['weakest']   = $row->classification;
            }

            unset($group);
        }

        return collect($groups)
            ->filter(fn ($group) => $group['strong_count'] > 0 && $group['other_count'] > 0)
            // max_order is only final once a group has been fully folded.
            ->map(function ($group) {
                $group['severity_tier'] = self::tierFor((int) $group['max_order']);

                return $group;
            })
            ->sortByDesc('total_count')
            ->values();
    }
}
