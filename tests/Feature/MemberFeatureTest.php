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
        $submitter = Submitter::factory()->create(['uuid' => 'test-submitter-uuid']);

        $response = $this->get('/members/test-submitter-uuid');

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
        $submitter = Submitter::factory()->create(['uuid' => 'test-submitter-class']);

        $response = $this->get('/members/test-submitter-class');

        $response->assertStatus(200);
        $response->assertViewHas('classifications');
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
        $submitter = Submitter::factory()->create([
            'uuid' => 'test-submitter-meta',
            'title' => 'Test Consortium'
        ]);

        $response = $this->get('/members/test-submitter-meta');

        $response->assertStatus(200);
        $response->assertViewHas('page_meta');
    }
}
