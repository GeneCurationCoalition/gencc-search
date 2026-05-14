<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Gene;
use App\Disease;
use App\Classification;
use App\Submitter;
use App\Submission;
use App\Inheritance;
use Illuminate\Support\Facades\DB;

class MemberFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function members_index_page_returns_200()
    {
        $response = $this->get('/members');

        $response->assertStatus(200);
        $response->assertViewIs('submitters.index');
    }

    /** @test */
    public function members_index_only_shows_active_submitters()
    {
        $activeSubmitter = Submitter::factory()->create(['status' => 1]);
        $inactiveSubmitter = Submitter::factory()->create(['status' => 0]);

        $response = $this->get('/members');

        $response->assertStatus(200);
        $response->assertViewHas('submitters', function ($submitters) use ($activeSubmitter, $inactiveSubmitter) {
            $ids = $submitters->pluck('id')->toArray();
            return in_array($activeSubmitter->id, $ids) && !in_array($inactiveSubmitter->id, $ids);
        });
    }

    /** @test */
    public function member_show_page_returns_200_for_valid_submitter()
    {
        // Controller looks up by ident column
        $submitter = Submitter::factory()->create(['ident' => 'test-submitter-ident']);

        $response = $this->get('/members/test-submitter-ident');

        $response->assertStatus(200);
        $response->assertViewIs('submitters.show');
        $response->assertViewHas('submitter');
    }

    /** @test */
    public function member_show_page_returns_404_for_invalid_submitter()
    {
        $response = $this->get('/members/non-existent-uuid');

        $response->assertStatus(404);
    }

    /** @test */
    public function member_show_includes_classifications()
    {
        // Controller looks up by ident column
        $submitter = Submitter::factory()->create(['ident' => 'test-submitter-class']);

        $response = $this->get('/members/test-submitter-class');

        $response->assertStatus(200);
        $response->assertViewHas('classifications');
    }

    /** @test */
    public function member_show_with_missing_counts_shows_unavailable_without_aggregate_fallback()
    {
        $submitter = Submitter::factory()->create([
            'ident' => 'test-submitter-counts',
            'counts' => [
                'total' => 2,
            ],
        ]);
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $inheritance = Inheritance::factory()->create();
        $definitive = Classification::factory()->definitive()->create();

        Submission::factory()->count(2)->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'original_disease_id' => $disease->id,
            'inheritance_id' => $inheritance->id,
            'submitter_id' => $submitter->id,
            'classification_id' => $definitive->id,
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $response = $this->get('/members/test-submitter-counts');
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        $response->assertStatus(200);
        $response->assertSee('Submission counts unavailable');
        $this->assertFalse($response->viewData('submitter')->relationLoaded('submissions'));
        $this->assertNull($response->viewData('countSummary'));
        $this->assertNull($response->viewData('submitterSubmissionsCount'));

        $aggregateQueries = collect($queries)->filter(function ($query) {
            return strpos($query['query'], 'classification_id') !== false
                && strpos(strtolower($query['query']), 'group by') !== false;
        });

        $this->assertCount(0, $aggregateQueries);
    }

    /** @test */
    public function member_show_uses_precomputed_release_json_counts_when_available()
    {
        $submitter = Submitter::factory()->create([
            'ident' => 'test-submitter-json-counts',
            'counts' => [
                'total' => 99,
                'by_classification' => [
                    'Definitive' => [
                        'count' => 7,
                        'abbreviation' => 'DEF',
                    ],
                    'Strong' => [
                        'count' => 5,
                        'abbreviation' => 'STR',
                    ],
                ],
            ],
        ]);
        $definitive = Classification::factory()->definitive()->create();
        $strong = Classification::factory()->strong()->create();

        $response = $this->get('/members/test-submitter-json-counts');

        $response->assertStatus(200);
        $this->assertFalse($response->viewData('submitter')->relationLoaded('submissions'));
        $this->assertSame(99, $response->viewData('submitterSubmissionsCount'));
        $this->assertSame(7, (int) $response->viewData('classificationCounts')->get($definitive->id));
        $this->assertSame(5, (int) $response->viewData('classificationCounts')->get($strong->id));
    }

    /** @test */
    public function member_show_with_zero_release_counts_shows_submission_data_coming_soon()
    {
        $submitter = Submitter::factory()->create([
            'ident' => 'test-submitter-zero-counts',
            'counts' => [
                'total' => 0,
                'by_classification' => [],
            ],
        ]);

        $response = $this->get('/members/test-submitter-zero-counts');

        $response->assertStatus(200);
        $response->assertSee('Submission Data Coming Soon');
        $this->assertSame(0, $response->viewData('submitterSubmissionsCount'));
        $this->assertNotNull($response->viewData('countSummary'));
    }

    /** @test */
    public function member_show_with_empty_release_counts_shows_submission_data_coming_soon()
    {
        $submitter = Submitter::factory()->create([
            'ident' => 'test-submitter-empty-counts',
            'counts' => [],
        ]);

        $response = $this->get('/members/test-submitter-empty-counts');

        $response->assertStatus(200);
        $response->assertSee('Submission Data Coming Soon');
        $this->assertSame(0, $response->viewData('submitterSubmissionsCount'));
        $this->assertNotNull($response->viewData('countSummary'));
    }

    /** @test */
    public function members_index_uses_precomputed_release_json_counts_when_available()
    {
        $submitter = Submitter::factory()->create([
            'status' => 1,
            'counts' => [
                'total' => 12,
                'by_classification' => [
                    'Definitive' => [
                        'count' => 8,
                        'abbreviation' => 'DEF',
                    ],
                    'Strong' => [
                        'count' => 4,
                        'abbreviation' => 'STR',
                    ],
                ],
            ],
        ]);

        $response = $this->get('/members');

        $response->assertStatus(200);
        $summary = $response->viewData('submitterCountSummaries')->get($submitter->id);
        $this->assertSame(12, $summary['total']);
        $this->assertSame(8, $summary['displayCounts']['definitive']);
        $this->assertSame(4, $summary['displayCounts']['strong']);
    }

    /** @test */
    public function members_index_with_zero_release_counts_shows_submission_data_coming_soon()
    {
        $submitter = Submitter::factory()->create([
            'status' => 1,
            'counts' => [
                'total' => 0,
                'by_classification' => [],
            ],
        ]);

        $response = $this->get('/members');

        $response->assertStatus(200);
        $response->assertSee('Submission Data Coming Soon');
        $summary = $response->viewData('submitterCountSummaries')->get($submitter->id);
        $this->assertSame(0, $summary['total']);
    }

    /** @test */
    public function members_index_with_empty_release_counts_shows_submission_data_coming_soon()
    {
        $submitter = Submitter::factory()->create([
            'status' => 1,
            'counts' => [],
        ]);

        $response = $this->get('/members');

        $response->assertStatus(200);
        $response->assertSee('Submission Data Coming Soon');
        $summary = $response->viewData('submitterCountSummaries')->get($submitter->id);
        $this->assertSame(0, $summary['total']);
    }

    /** @test */
    public function members_index_with_missing_counts_shows_unavailable_without_aggregate_fallback()
    {
        $submitter = Submitter::factory()->create([
            'status' => 1,
            'counts' => [
                'total' => 2,
            ],
        ]);
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $inheritance = Inheritance::factory()->create();
        $definitive = Classification::factory()->definitive()->create();

        Submission::factory()->count(2)->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'original_disease_id' => $disease->id,
            'inheritance_id' => $inheritance->id,
            'submitter_id' => $submitter->id,
            'classification_id' => $definitive->id,
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $response = $this->get('/members');
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        $response->assertStatus(200);
        $response->assertSee('Submission counts unavailable');
        $this->assertNull($response->viewData('submitterCountSummaries')->get($submitter->id));

        $aggregateQueries = collect($queries)->filter(function ($query) {
            return strpos($query['query'], 'classification_id') !== false
                && strpos($query['query'], 'submitter_id') !== false
                && strpos(strtolower($query['query']), 'group by') !== false;
        });

        $this->assertCount(0, $aggregateQueries);
    }

    /** @test */
    public function members_index_is_paginated()
    {
        // Create more than 25 submitters to test pagination
        Submitter::factory()->count(30)->create(['status' => 1]);

        $response = $this->get('/members');

        $response->assertStatus(200);
        $response->assertViewHas('submitters');
    }

    /** @test */
    public function member_show_page_has_seo_meta_title()
    {
        // Controller looks up by ident column
        $submitter = Submitter::factory()->create([
            'ident' => 'test-submitter-meta',
            'name' => 'Test Consortium'
        ]);

        $response = $this->get('/members/test-submitter-meta');

        $response->assertStatus(200);
        $response->assertViewHas('page_meta');
    }
}
