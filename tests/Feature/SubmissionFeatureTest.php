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
    public function submission_show_page_returns_200_for_valid_submission_with_version()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        // byDisplayId scope looks up by sid column
        $submission = Submission::factory()->create([
            'sid' => 'SGC-12345',
            'version_number' => 1,
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'original_disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
        ]);

        // Request with versioned URL
        $response = $this->get('/submissions/SGC-12345.1');

        $response->assertStatus(200);
        $response->assertViewIs('submissions.show');
        $response->assertViewHas('submission');
    }

    /** @test */
    public function submission_show_redirects_non_versioned_url_to_versioned()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        // byDisplayId scope looks up by sid column
        // Non-versioned URL requires is_live=true and status=published
        $submission = Submission::factory()->create([
            'sid' => 'SGC-REDIRECT',
            'version_number' => 2,
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'original_disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
        ]);

        // Request without version should redirect to versioned URL
        $response = $this->get('/submissions/SGC-REDIRECT');

        $response->assertRedirect('/submissions/SGC-REDIRECT.2');
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
        // Gene title accessor reads from symbol, Disease title reads from title first
        $gene = Gene::factory()->create(['symbol' => 'BRCA1']);
        $disease = Disease::factory()->create(['name' => 'Breast Cancer']);
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create(['name' => 'Test Lab']);
        $inheritance = Inheritance::factory()->create();

        // byDisplayId scope looks up by sid column
        $submission = Submission::factory()->create([
            'sid' => 'SGC-67890',
            'version_number' => 1,
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'original_disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
        ]);

        $response = $this->get('/submissions/SGC-67890.1');

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
        // Gene title accessor reads from symbol
        $gene = Gene::factory()->create(['symbol' => 'BRCA1']);
        $disease = Disease::factory()->create(['name' => 'Breast Cancer']);
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create(['name' => 'Test Lab']);
        $inheritance = Inheritance::factory()->create(['name' => 'Autosomal dominant']);

        // byDisplayId scope looks up by sid column
        $submission = Submission::factory()->create([
            'sid' => 'SGC-META',
            'version_number' => 1,
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'original_disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
        ]);

        $response = $this->get('/submissions/SGC-META.1');

        $response->assertStatus(200);
        $response->assertViewHas('page_meta');
    }
}
