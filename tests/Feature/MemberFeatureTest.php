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
    public function member_show_uses_live_published_aggregate_counts_without_eager_loading_submissions()
    {
        $submitter = Submitter::factory()->create(['ident' => 'test-submitter-counts']);
        $otherSubmitter = Submitter::factory()->create();
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $inheritance = Inheritance::factory()->create();
        $definitive = Classification::factory()->definitive()->create();
        $strong = Classification::factory()->strong()->create();

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

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'original_disease_id' => $disease->id,
            'inheritance_id' => $inheritance->id,
            'submitter_id' => $submitter->id,
            'classification_id' => $strong->id,
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ]);

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'original_disease_id' => $disease->id,
            'inheritance_id' => $inheritance->id,
            'submitter_id' => $otherSubmitter->id,
            'classification_id' => $definitive->id,
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ]);

        Submission::factory()->notLive()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'original_disease_id' => $disease->id,
            'inheritance_id' => $inheritance->id,
            'submitter_id' => $submitter->id,
            'classification_id' => $definitive->id,
            'status' => Submission::STATUS_PUBLISHED,
        ]);

        Submission::factory()->unpublished()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'original_disease_id' => $disease->id,
            'inheritance_id' => $inheritance->id,
            'submitter_id' => $submitter->id,
            'classification_id' => $strong->id,
        ]);

        $response = $this->get('/members/test-submitter-counts');

        $response->assertStatus(200);
        $this->assertFalse($response->viewData('submitter')->relationLoaded('submissions'));
        $this->assertSame(3, $response->viewData('submitterSubmissionsCount'));
        $this->assertSame(2, (int) $response->viewData('classificationCounts')->get($definitive->id));
        $this->assertSame(1, (int) $response->viewData('classificationCounts')->get($strong->id));
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
