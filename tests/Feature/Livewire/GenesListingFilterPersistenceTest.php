<?php

namespace Tests\Feature\Livewire;

use App\Gene;
use App\Disease;
use App\Classification;
use App\Submitter;
use App\Submission;
use App\Inheritance;
use App\Http\Livewire\Genes\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ShimsSqliteFunctions;
use Tests\TestCase;

/**
 * Covers reflecting the genes listing filters in the URL (#204).
 *
 * The two halves that matter are round-tripping (a filtered view can be shared
 * and arrives filtered) and not polluting a clean URL (an unfiltered page has no
 * query string, so ?submitters= does not list all twenty-odd submitters).
 */
class GenesListingFilterPersistenceTest extends TestCase
{
    use RefreshDatabase;
    use ShimsSqliteFunctions;

    /** @test */
    public function an_unfiltered_listing_leaves_the_query_string_empty()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');

        $component = Livewire::test(Listing::class);

        $this->assertSame('', $component->get('title'));
        $this->assertSame('', $component->get('hasDisease'));
        // Empty rather than every ident, which is what binding
        // curations_from_submitters straight to the URL would have produced.
        $this->assertSame('', $component->get('submitterFilter'));
        $this->assertFalse($component->get('hasActiveFilters'));
    }

    /** @test */
    public function a_gene_symbol_filter_arrives_from_the_url()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');
        $this->createGeneWithSubmission('BRCA1');

        $component = Livewire::withQueryParams(['title' => 'GJB2'])->test(Listing::class);

        $this->assertCount(1, $component->viewData('genes'));
        $this->assertTrue($component->get('hasActiveFilters'));
    }

    /** @test */
    public function a_classification_filter_arrives_from_the_url()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');

        // The gene's only submission is classification 1, so switching that one
        // off has to hide it even though the other eight stay on.
        $component = Livewire::withQueryParams(['definitive' => '0'])->test(Listing::class);

        // assertEquals, not assertSame: Livewire casts a numeric query param to
        // int, so this arrives as 0 rather than the '0' the UI buttons write.
        $this->assertEquals(0, $component->get('curations_definitive'));
        $this->assertEquals(1, $component->get('curations_strong'));
        $this->assertCount(0, $component->viewData('genes'));
        $this->assertTrue($component->get('hasActiveFilters'));
    }

    /** @test */
    public function a_submitter_filter_round_trips_through_the_url()
    {
        $this->shimRegexpSubstrForSqlite();
        $first = $this->createGeneWithSubmission('GJB2');
        $this->createGeneWithSubmission('BRCA1');

        $wanted = Submission::where('gene_id', $first->id)->first()->submitter->ident;

        $component = Livewire::withQueryParams(['submitters' => $wanted])->test(Listing::class);

        $this->assertSame([$wanted], $component->get('curations_from_submitters'));
        $this->assertSame($wanted, $component->get('submitterFilter'));
        $this->assertCount(1, $component->viewData('genes'));
        $this->assertTrue($component->get('hasActiveFilters'));
    }

    /**
     * @test
     *
     * "None selected" cannot be spelled as an empty string, since that is how
     * "no filter" is spelled — it needs the explicit sentinel to survive a
     * round trip.
     */
    public function selecting_no_submitters_round_trips_as_the_none_sentinel()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');

        $selected = Livewire::test(Listing::class)->call('selectNoSubmitters');
        $this->assertSame('none', $selected->get('submitterFilter'));

        $fromUrl = Livewire::withQueryParams(['submitters' => 'none'])->test(Listing::class);

        $this->assertSame([], $fromUrl->get('curations_from_submitters'));
        $this->assertCount(0, $fromUrl->viewData('genes'));
    }

    /**
     * @test
     *
     * A stale bookmark naming a submitter that no longer exists should fall back
     * to showing everything, not to an empty listing the user cannot explain.
     */
    public function an_unknown_submitter_in_the_url_is_ignored()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');

        $component = Livewire::withQueryParams(['submitters' => 'GENCC_NO_SUCH_SUBMITTER'])
            ->test(Listing::class);

        $this->assertCount(1, $component->viewData('genes'));
    }

    /** @test */
    public function clearing_all_filters_restores_the_full_listing()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');
        $this->createGeneWithSubmission('BRCA1');

        $component = Livewire::withQueryParams(['title' => 'GJB2', 'definitive' => '0'])
            ->test(Listing::class);

        $this->assertTrue($component->get('hasActiveFilters'));

        $component->call('clearAllFilters');

        $this->assertFalse($component->get('hasActiveFilters'));
        $this->assertSame('', $component->get('title'));
        $this->assertSame('', $component->get('submitterFilter'));
        $this->assertCount(2, $component->viewData('genes'));
    }

    /** @test */
    public function the_active_filter_banner_only_shows_when_a_filter_is_set()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');

        Livewire::test(Listing::class)
            ->assertDontSee('Filters are active');

        Livewire::withQueryParams(['title' => 'GJB2'])->test(Listing::class)
            ->assertSee('Filters are active');
    }

    /**
     * @test
     *
     * The pre-existing ?curations_from_submitters[]= form is still linked from
     * elsewhere, so it has to keep working alongside the new ?submitters= form.
     */
    public function the_legacy_submitter_parameter_still_works()
    {
        $this->shimRegexpSubstrForSqlite();
        $first = $this->createGeneWithSubmission('GJB2');
        $this->createGeneWithSubmission('BRCA1');

        $wanted = Submission::where('gene_id', $first->id)->first()->submitter->ident;

        $component = Livewire::withQueryParams(['curations_from_submitters' => [$wanted]])
            ->test(Listing::class);

        $this->assertCount(1, $component->viewData('genes'));
    }

    /**
     * See GenesListingSelectAllTest for why the classification is reused rather
     * than minted per gene.
     */
    private function createGeneWithSubmission(string $symbol): Gene
    {
        $gene = Gene::factory()->create(['symbol' => $symbol, 'title' => $symbol]);

        $classification = Classification::first() ?: Classification::factory()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => Disease::factory()->create()->id,
            'classification_id' => $classification->id,
            'submitter_id' => Submitter::factory()->create()->id,
            'inheritance_id' => Inheritance::factory()->create()->id,
        ]);

        return $gene;
    }
}
