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

class StatisticsFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function statistics_page_returns_200()
    {
        $response = $this->get('/statistics');

        $response->assertStatus(200);
        $response->assertViewIs('statistics.index');
    }

    /** @test */
    public function statistics_page_has_required_view_variables()
    {
        $response = $this->get('/statistics');

        $response->assertStatus(200);
        $response->assertViewHas('genesCount');
        $response->assertViewHas('diseasesCount');
        $response->assertViewHas('submissionsCount');
        $response->assertViewHas('classifications');
        $response->assertViewHas('page_meta');
    }

    /** @test */
    public function statistics_page_shows_correct_gene_count()
    {
        $gene1 = Gene::factory()->create();
        $gene2 = Gene::factory()->create();
        $geneWithoutSubmission = Gene::factory()->create();

        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        Submission::factory()->create([
            'gene_id' => $gene1->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'is_current' => true,
        ]);

        Submission::factory()->create([
            'gene_id' => $gene2->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'is_current' => true,
        ]);

        $response = $this->get('/statistics');

        $response->assertStatus(200);
        $response->assertViewHas('genesCount', 2);
    }

    /** @test */
    public function statistics_page_shows_correct_disease_count()
    {
        $gene = Gene::factory()->create();
        $disease1 = Disease::factory()->create();
        $disease2 = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        $submission1 = Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease1->id,
            'disease_original_id' => $disease1->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'is_current' => true,
        ]);

        $submission2 = Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease2->id,
            'disease_original_id' => $disease2->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'is_current' => true,
        ]);

        // Attach submissions to diseases via pivot table (disease_submission)
        $disease1->submissions()->attach($submission1->id, ['type' => 'current']);
        $disease2->submissions()->attach($submission2->id, ['type' => 'current']);

        $response = $this->get('/statistics');

        $response->assertStatus(200);
        $response->assertViewHas('diseasesCount', 2);
    }

    /** @test */
    public function statistics_page_shows_correct_submission_count()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        // Create 3 current submissions
        Submission::factory()->count(3)->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'is_current' => true,
        ]);

        // Create 1 non-current submission (superseded or unpublished)
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'is_current' => false,
        ]);

        $response = $this->get('/statistics');

        $response->assertStatus(200);
        $response->assertViewHas('submissionsCount', 3);
    }

    /** @test */
    public function statistics_page_includes_classifications_with_submissions()
    {
        $classification = Classification::factory()->create();

        $response = $this->get('/statistics');

        $response->assertStatus(200);
        $response->assertViewHas('classifications');
    }

    /** @test */
    public function statistics_page_has_seo_meta_title()
    {
        $response = $this->get('/statistics');

        $response->assertStatus(200);
        $response->assertViewHas('page_meta', function ($meta) {
            return isset($meta['seo']['title']) && str_contains($meta['seo']['title'], 'Statistics');
        });
    }
}
