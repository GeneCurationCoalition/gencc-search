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

class DownloadFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function download_page_returns_200()
    {
        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertViewIs('download.index');
    }

    /** @test */
    public function download_csv_returns_file()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
        ]);

        $response = $this->get('/download/action/submissions-export-csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/plain; charset=UTF-8');
    }

    /** @test */
    public function download_tsv_returns_file()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
        ]);

        $response = $this->get('/download/action/submissions-export-tsv');

        $response->assertStatus(200);
    }

    /** @test */
    public function download_xlsx_returns_file()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
        ]);

        $response = $this->get('/download/action/submissions-export-xlsx');

        $response->assertStatus(200);
    }
}
