<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Gene;
use App\Disease;
use App\Classification;
use App\Submitter;
use App\Submission;

class GeneFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function genes_index_page_returns_200()
    {
        $response = $this->get('/genes');

        $response->assertStatus(200);
        $response->assertViewIs('genes.index');
        $response->assertViewHas('page_meta');
    }

    /** @test */
    public function home_page_returns_200()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /** @test */
    public function gene_show_page_returns_200_for_valid_gene()
    {
        // Gene curie scope looks up by hgnc_id column
        $gene = Gene::factory()->create(['hgnc_id' => 'HGNC:12345']);

        $response = $this->get('/genes/HGNC:12345');

        $response->assertStatus(200);
        $response->assertViewIs('genes.show');
        $response->assertViewHas('gene');
        $response->assertViewHas('records');
    }

    /** @test */
    public function gene_show_page_returns_404_for_invalid_gene()
    {
        $response = $this->get('/genes/HGNC:99999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function gene_disease_page_returns_200_for_valid_gene()
    {
        // Gene curie scope looks up by hgnc_id column
        $gene = Gene::factory()->create(['hgnc_id' => 'HGNC:12345']);

        $response = $this->get('/genes/HGNC:12345/disease');

        $response->assertStatus(200);
        $response->assertViewIs('genes.disease');
        $response->assertViewHas('gene');
    }

    /** @test */
    public function gene_submitter_page_returns_200_for_valid_gene()
    {
        // Gene curie scope looks up by hgnc_id column
        $gene = Gene::factory()->create(['hgnc_id' => 'HGNC:12345']);

        $response = $this->get('/genes/HGNC:12345/submitters');

        $response->assertStatus(200);
        $response->assertViewIs('genes.submitter');
        $response->assertViewHas('gene');
    }

    /** @test */
    public function gene_show_includes_submissions_organized_by_classification()
    {
        $gene = Gene::factory()->create();
        $classification = Classification::factory()->definitive()->create();
        $disease = Disease::factory()->create();
        $submitter = Submitter::factory()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
        ]);

        $response = $this->get('/genes/' . $gene->curie);

        $response->assertStatus(200);
        $response->assertViewHas('records');
    }

    /** @test */
    public function gene_page_has_correct_seo_meta_title()
    {
        // Gene curie scope looks up by hgnc_id, title accessor reads from symbol
        $gene = Gene::factory()->create([
            'hgnc_id' => 'HGNC:12345',
            'symbol' => 'BRCA1',
        ]);

        $response = $this->get('/genes/HGNC:12345');

        $response->assertStatus(200);
        $response->assertViewHas('page_meta', function ($meta) {
            return str_contains($meta['seo']['title'], 'BRCA1');
        });
    }
}
