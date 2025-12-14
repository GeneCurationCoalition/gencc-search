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

class SubmissionFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function submissions_index_redirects_to_home()
    {
        $response = $this->get('/submissions');

        $response->assertRedirect('home');
    }

    /** @test */
    public function submission_show_page_returns_200_for_valid_submission()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        $submission = Submission::factory()->create([
            'uuid' => 'test-uuid-12345',
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'disease_original_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'status' => 1,
        ]);

        $response = $this->get('/submissions/test-uuid-12345');

        $response->assertStatus(200);
        $response->assertViewIs('submissions.show');
        $response->assertViewHas('submission');
    }

    /** @test */
    public function submission_show_page_returns_404_for_invalid_submission()
    {
        $response = $this->get('/submissions/non-existent-uuid');

        $response->assertStatus(404);
    }

    /** @test */
    public function submission_show_includes_related_gene_disease_and_submitter()
    {
        $gene = Gene::factory()->create(['title' => 'BRCA1']);
        $disease = Disease::factory()->create(['title' => 'Breast Cancer']);
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create(['title' => 'Test Lab']);
        $inheritance = Inheritance::factory()->create();

        $submission = Submission::factory()->create([
            'uuid' => 'test-uuid-67890',
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'disease_original_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'status' => 1,
        ]);

        $response = $this->get('/submissions/test-uuid-67890');

        $response->assertStatus(200);
        $response->assertViewHas('submission', function ($sub) use ($gene, $disease, $submitter) {
            return $sub->gene->title === 'BRCA1'
                && $sub->disease->title === 'Breast Cancer'
                && $sub->submitter->title === 'Test Lab';
        });
    }

    /** @test */
    public function submission_show_page_has_seo_meta_title()
    {
        $gene = Gene::factory()->create(['title' => 'BRCA1']);
        $disease = Disease::factory()->create(['title' => 'Breast Cancer']);
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create(['title' => 'Test Lab']);
        $inheritance = Inheritance::factory()->create(['title' => 'Autosomal dominant']);

        $submission = Submission::factory()->create([
            'uuid' => 'test-uuid-meta',
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'disease_original_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'status' => 1,
        ]);

        $response = $this->get('/submissions/test-uuid-meta');

        $response->assertStatus(200);
        $response->assertViewHas('page_meta');
    }
}
