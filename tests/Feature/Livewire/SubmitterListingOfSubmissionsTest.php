<?php

namespace Tests\Feature\Livewire;

use App\Gene;
use App\Disease;
use App\Classification;
use App\Submitter;
use App\Submission;
use App\Inheritance;
use App\Http\Livewire\Submitter\ListingOfSubmissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubmitterListingOfSubmissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function listing_of_submissions_component_can_render()
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

        $component = Livewire::test(ListingOfSubmissions::class, ['submitter' => $submitter]);

        $component->assertStatus(200);
    }

    /** @test */
    public function listing_of_submissions_shows_empty_when_no_submissions()
    {
        $submitter = Submitter::factory()->create();

        $component = Livewire::test(ListingOfSubmissions::class, ['submitter' => $submitter]);

        $component->assertStatus(200);
    }

    /** @test */
    public function listing_of_submissions_initializes_filter_set()
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

        $component = Livewire::test(ListingOfSubmissions::class, ['submitter' => $submitter]);

        $filterSet = $component->get('filter_set');
        $this->assertIsArray($filterSet);
        $this->assertArrayHasKey('classifications', $filterSet);
        $this->assertArrayHasKey('diseases', $filterSet);
        $this->assertArrayHasKey('genes', $filterSet);
        $this->assertArrayHasKey('inheritances', $filterSet);
        $this->assertArrayHasKey('submitters', $filterSet);
    }

    /** @test */
    public function listing_of_submissions_can_filter_by_classifications()
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

        $component = Livewire::test(ListingOfSubmissions::class, ['submitter' => $submitter]);
        $component
            ->call('gotoPage', 6)
            ->assertSet('page', 6)
            ->call('filterByClassifications', [$classification->id])
            ->assertSet('page', 1);

        $filterSet = $component->get('filter_set');
        $this->assertContains($classification->id, $filterSet['classifications']);
    }

    /** @test */
    public function listing_of_submissions_query_gene_resets_pagination_to_first_page()
    {
        $gene = Gene::factory()->create([
            'symbol' => 'RESETGENE',
            'title' => 'RESETGENE',
        ]);
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

        Livewire::test(ListingOfSubmissions::class, ['submitter' => $submitter])
            ->call('gotoPage', 6)
            ->assertSet('page', 6)
            ->set('query_gene', $gene->symbol)
            ->assertSet('page', 1)
            ->assertSee($gene->symbol);
    }

    /** @test */
    public function listing_of_submissions_can_filter_by_genes()
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

        $component = Livewire::test(ListingOfSubmissions::class, ['submitter' => $submitter]);
        $component->call('filterByGenes', [$gene->id]);

        $filterSet = $component->get('filter_set');
        $this->assertContains($gene->id, $filterSet['genes']);
    }

    /** @test */
    public function listing_of_submissions_handles_null_inheritance()
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
            'inheritance_id' => null,
            'is_live' => true,
            'status' => 'published',
        ]);

        $component = Livewire::test(ListingOfSubmissions::class, ['submitter' => $submitter]);

        $component->assertStatus(200);
    }

    /**
     * @test
     * @dataProvider paddedSearchTermProvider
     *
     * Regression for #207: a pasted leading/trailing space was becoming part of
     * the LIKE pattern, so the search silently returned nothing.
     */
    public function listing_of_submissions_ignores_surrounding_whitespace_in_search($pad)
    {
        $submitter = $this->createSearchableSubmission('GJB2', 'hearing loss');

        $component = Livewire::test(ListingOfSubmissions::class, ['submitter' => $submitter])
            ->set('query_gene', $pad . 'GJB2' . $pad);

        $this->assertCount(1, $component->viewData('records'));

        $component->set('query_gene', '')
            ->set('query_disease', $pad . 'hearing loss' . $pad);

        $this->assertCount(1, $component->viewData('records'));
    }

    public function paddedSearchTermProvider(): array
    {
        return [
            'space'        => [' '],
            'tab'          => ["\t"],
            'newline'      => ["\n"],
            'non-breaking' => ["\u{00A0}"],
        ];
    }

    /** @test */
    public function listing_of_submissions_still_excludes_non_matching_search_terms()
    {
        $submitter = $this->createSearchableSubmission('GJB2', 'hearing loss');

        $component = Livewire::test(ListingOfSubmissions::class, ['submitter' => $submitter])
            ->set('query_gene', ' BRCA1 ');

        $this->assertCount(0, $component->viewData('records'));
    }

    /**
     * Create a published submission with a known gene symbol and disease name.
     */
    private function createSearchableSubmission(string $symbol, string $diseaseName): Submitter
    {
        $gene = Gene::factory()->create(['symbol' => $symbol, 'title' => $symbol]);
        $disease = Disease::factory()->create(['name' => $diseaseName, 'title' => $diseaseName]);
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

        return $submitter;
    }
}
