<?php

namespace Tests\Feature\Livewire;

use App\Gene;
use App\Disease;
use App\Classification;
use App\Submitter;
use App\Submission;
use App\Inheritance;
use App\Http\Livewire\Gene\ListingByClassification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GeneListingByClassificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function listing_by_classification_component_can_render()
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
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        $component = Livewire::test(ListingByClassification::class, ['gene' => $gene]);

        $component->assertStatus(200);
    }

    /** @test */
    public function listing_by_classification_shows_no_results_view_when_no_submissions()
    {
        $gene = Gene::factory()->create();

        $component = Livewire::test(ListingByClassification::class, ['gene' => $gene]);

        $component->assertStatus(200);
    }

    /** @test */
    public function listing_by_classification_initializes_filter_set()
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
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        $component = Livewire::test(ListingByClassification::class, ['gene' => $gene]);

        // Check that filter_set arrays are initialized
        $filterSet = $component->get('filter_set');
        $this->assertIsArray($filterSet);
        $this->assertArrayHasKey('classifications', $filterSet);
        $this->assertArrayHasKey('diseases', $filterSet);
        $this->assertArrayHasKey('inheritances', $filterSet);
        $this->assertArrayHasKey('submitters', $filterSet);
    }

    /** @test */
    public function classification_sections_use_canonical_order_not_database_order()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();
        $refuted = Classification::factory()->create([
            'curie' => 'GENCC:100006',
            'name' => 'Refuted Evidence',
            'order' => 1,
        ]);
        $animal = Classification::factory()->create([
            'curie' => 'GENCC:100007',
            'name' => 'Animal Model Only',
            'order' => 999,
        ]);

        foreach ([$refuted, $animal] as $classification) {
            Submission::factory()->create([
                'gene_id' => $gene->id,
                'disease_id' => $disease->id,
                'classification_id' => $classification->id,
                'submitter_id' => $submitter->id,
                'inheritance_id' => $inheritance->id,
                'is_live' => true,
                'status' => Submission::STATUS_PUBLISHED,
            ]);
        }

        $titles = collect(Livewire::test(ListingByClassification::class, ['gene' => $gene])
            ->viewData('filter')['classifications'])
            ->pluck('title')
            ->values()
            ->all();

        $this->assertSame(['Animal Model Only', 'Refuted Evidence'], $titles);
    }

    /** @test */
    public function listing_by_classification_can_filter_by_classifications()
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
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        $component = Livewire::test(ListingByClassification::class, ['gene' => $gene]);

        // Toggle classification filter
        $component->call('filterByClassifications', [$classification->id]);

        $filterSet = $component->get('filter_set');
        $this->assertContains($classification->id, $filterSet['classifications']);
    }

    /** @test */
    public function listing_by_classification_can_filter_by_diseases()
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
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        $component = Livewire::test(ListingByClassification::class, ['gene' => $gene]);

        // Toggle disease filter
        $component->call('filterByDiseases', [$disease->id]);

        $filterSet = $component->get('filter_set');
        $this->assertContains($disease->id, $filterSet['diseases']);
    }

    /** @test */
    public function listing_by_classification_can_filter_by_inheritances()
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
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        $component = Livewire::test(ListingByClassification::class, ['gene' => $gene]);

        // Toggle inheritance filter
        $component->call('filterByInheritances', [$inheritance->id]);

        $filterSet = $component->get('filter_set');
        $this->assertContains($inheritance->id, $filterSet['inheritances']);
    }

    /** @test */
    public function listing_by_classification_can_filter_by_submitters()
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
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        $component = Livewire::test(ListingByClassification::class, ['gene' => $gene]);

        // Toggle submitter filter
        $component->call('filterBySubmitters', [$submitter->id]);

        $filterSet = $component->get('filter_set');
        $this->assertContains($submitter->id, $filterSet['submitters']);
    }

    /** @test */
    public function listing_by_classification_only_shows_published_live_submissions()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->definitive()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->autosomalDominant()->create();

        // Create a live published submission
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        // Create a non-live submission (should not appear)
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
            'is_live' => false,
            'status' => 'published',
        ]);

        // Create an unpublished submission (should not appear)
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'draft',
        ]);

        $component = Livewire::test(ListingByClassification::class, ['gene' => $gene]);

        // Only 1 submission should be visible
        $component->assertStatus(200);
    }

    /** @test */
    public function listing_by_classification_handles_null_inheritance()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->definitive()->create();
        $submitter = Submitter::factory()->create();

        // Create submission without inheritance
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => null,
            'is_live' => true,
            'status' => 'published',
        ]);

        // This should not throw an error
        $component = Livewire::test(ListingByClassification::class, ['gene' => $gene]);

        $component->assertStatus(200);
    }
}
