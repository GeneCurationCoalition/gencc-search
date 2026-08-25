<?php

namespace Tests\Unit;

use App\Exports\ConflictSubmissionExport;
use App\Services\ConflictExportCache;
use App\Services\ConflictViewerFilters;
use Tests\TestCase;

class ConflictExportOrderingTest extends TestCase
{
    /** @test */
    public function source_permutations_have_identical_semantic_order_cache_identity_and_etag()
    {
        $groups = collect([
            $this->group('z', 'ZZZ', 'HGNC:2', 'Alpha', 'MONDO:1', 'Dominant', 'HP:2', 'Z-D'),
            $this->group('a', 'AAA', 'HGNC:1', 'Beta', 'MONDO:2', 'Recessive', 'HP:1', 'A-D'),
        ]);
        $filters = new ConflictViewerFilters();
        $state = $filters->normalize($groups, [
            'sortField' => 'total_count',
            'sortDirection' => 'desc',
        ]);
        $ordered = $filters->apply($groups, $state);
        $permutedSource = $groups->reverse()->map(function ($group) {
            $group['submissions'] = array_reverse($group['submissions']);

            return $group;
        })->values();
        $permuted = $filters->apply($permutedSource, $state);

        $this->assertSame(['a', 'z'], $ordered->pluck('key')->all());
        $this->assertSame($ordered->pluck('key')->all(), $permuted->pluck('key')->all());

        $first = new ConflictSubmissionExport($ordered, 'csv');
        $second = new ConflictSubmissionExport($permuted, 'csv');
        $this->assertSame($first->cacheRows(), $second->cacheRows());

        $cache = new ConflictExportCache();
        $firstIdentity = $cache->identity('csv', $filters->canonicalState($state), $first->cacheRows());
        $secondIdentity = $cache->identity('csv', $filters->canonicalState($state), $second->cacheRows());
        $this->assertSame($firstIdentity, $secondIdentity);
    }

    /** @test */
    public function primary_direction_precedes_ascending_semantic_tie_breakers()
    {
        $groups = collect([
            $this->group('alpha', 'AAA', 'HGNC:1', 'Alpha', 'MONDO:1', 'Dominant', 'HP:1', 'A-D'),
            $this->group('beta', 'ZZZ', 'HGNC:2', 'Beta', 'MONDO:2', 'Dominant', 'HP:1', 'Z-D'),
        ]);
        $filters = new ConflictViewerFilters();
        $state = $filters->normalize($groups, [
            'sortField' => 'disease_name',
            'sortDirection' => 'desc',
        ]);

        $this->assertSame(['beta', 'alpha'], $filters->apply($groups, $state)->pluck('key')->all());
    }

    private function group(
        string $key,
        string $gene,
        string $geneCurie,
        string $disease,
        string $diseaseCurie,
        string $moi,
        string $moiCurie,
        string $sgcId
    ): array {
        $submission = [
            'sgc_id' => $sgcId,
            'version_number' => 1,
            'gene_curie' => $geneCurie,
            'gene_symbol' => $gene,
            'disease_curie' => $diseaseCurie,
            'disease_title' => $disease,
            'disease_original_curie' => '',
            'disease_original_title' => '',
            'moi_curie' => $moiCurie,
            'moi_title' => $moi,
            'classification_group' => 'D/S/M',
            'classification_curie' => 'GENCC:100001',
            'classification_title' => 'Definitive',
            'classification_priority' => 1,
            'conflict_side' => 'strong',
            'submitter_curie' => 'GENCC:000001',
            'submitter_title' => 'Lab',
            'submitted_as_date' => '2026-01-01',
        ];

        return [
            'key' => $key,
            'gene_symbol' => $gene,
            'gene_curie' => $geneCurie,
            'disease_name' => $disease,
            'disease_curie' => $diseaseCurie,
            'moi' => $moi,
            'moi_curie' => $moiCurie,
            'total_count' => 2,
            'submitter_slugs' => ['lab' => 'Lab'],
            'submissions' => [
                $submission,
                array_merge($submission, [
                    'sgc_id' => $sgcId . '-L',
                    'classification_group' => 'L/P/R/N',
                    'classification_curie' => 'GENCC:100004',
                    'classification_title' => 'Limited',
                    'classification_priority' => 4,
                    'conflict_side' => 'other',
                ]),
            ],
        ];
    }
}
