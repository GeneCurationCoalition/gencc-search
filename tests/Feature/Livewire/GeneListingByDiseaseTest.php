<?php

namespace Tests\Feature\Livewire;

use App\Gene;
use App\Disease;
use App\Classification;
use App\Submitter;
use App\Submission;
use App\Inheritance;
use App\Http\Livewire\Gene\ListingByDisease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GeneListingByDiseaseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function listing_by_disease_component_can_render()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->definitive()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->autosomalDominant()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        $component = Livewire::test(ListingByDisease::class, ['gene' => $gene]);

        $component->assertStatus(200);
    }

    /** @test */
    public function listing_by_disease_shows_no_results_view_when_no_submissions()
    {
        $gene = Gene::factory()->create();

        $component = Livewire::test(ListingByDisease::class, ['gene' => $gene]);

        $component->assertStatus(200);
    }

    /** @test */
    public function listing_by_disease_initializes_filter_set()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->definitive()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->autosomalDominant()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        $component = Livewire::test(ListingByDisease::class, ['gene' => $gene]);

        $filterSet = $component->get('filter_set');
        $this->assertIsArray($filterSet);
        $this->assertArrayHasKey('classifications', $filterSet);
        $this->assertArrayHasKey('diseases', $filterSet);
        $this->assertArrayHasKey('inheritances', $filterSet);
        $this->assertArrayHasKey('submitters', $filterSet);
    }

    /** @test */
    public function listing_by_disease_can_filter_by_classifications()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->definitive()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->autosomalDominant()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        $component = Livewire::test(ListingByDisease::class, ['gene' => $gene]);
        $component->call('filterByClassifications', [$classification->id]);

        $filterSet = $component->get('filter_set');
        $this->assertContains($classification->id, $filterSet['classifications']);
    }

    /** @test */
    public function listing_by_disease_handles_null_inheritance()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->definitive()->create();
        $submitter = Submitter::factory()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => null,
            'is_live' => true,
            'status' => 'published',
        ]);

        $component = Livewire::test(ListingByDisease::class, ['gene' => $gene]);

        $component->assertStatus(200);
    }
}
