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
use Illuminate\Support\Facades\DB;
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
        $this->assertSame([], $this->pathQuery($component));
    }

    /** @test */
    public function classification_url_state_contains_only_non_default_toggles()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');

        $component = Livewire::withQueryParams(['definitive' => '0'])->test(Listing::class);

        $this->assertSame(['definitive' => '0'], $this->pathQuery($component));

        $none = array_fill_keys(array_column(Classification::VOCABULARY, 'query'), '0');
        $component = Livewire::withQueryParams($none)->test(Listing::class);
        $this->assertSame(
            $none,
            $this->pathQuery($component)
        );

        $component = Livewire::withQueryParams(
            array_fill_keys(array_column(Classification::VOCABULARY, 'query'), '1')
        )->test(Listing::class);
        $this->assertSame([], $this->pathQuery($component));
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

        $submitter = Submission::where('gene_id', $first->id)->first()->submitter;

        $component = Livewire::withQueryParams(['submitters' => $submitter->curie])->test(Listing::class);

        $this->assertSame([$submitter->ident], $component->get('curations_from_submitters'));
        $this->assertSame($submitter->curie, $component->get('submitterFilter'));
        $this->assertSame(['submitters' => $submitter->curie], $this->pathQuery($component));
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
        $component->assertSee('Invalid URL filters were ignored.')
            ->assertSee('role="alert"', false)
            ->assertSee('Reset filters');
        $this->assertSame([], $this->pathQuery($component));
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
        $submitter = Submitter::where('ident', $wanted)->firstOrFail();
        $this->assertSame(['submitters' => $submitter->curie], $this->pathQuery($component));
    }

    /** @test */
    public function malformed_url_filters_are_discarded_without_losing_valid_submitters()
    {
        $this->shimRegexpSubstrForSqlite();
        $wantedGene = $this->createGeneWithSubmission('GJB2');
        $this->createGeneWithSubmission('BRCA1');
        $submitter = Submission::where('gene_id', $wantedGene->id)->first()->submitter;

        $component = Livewire::withQueryParams([
            'submitters' => $submitter->curie . ',GENCC:999999',
            'definitive' => '2',
            'title' => ['GJB2'],
        ])->test(Listing::class);

        $this->assertSame([$submitter->ident], $component->get('curations_from_submitters'));
        $this->assertSame('1', $component->get('curations_definitive'));
        $this->assertSame('', $component->get('title'));
        $this->assertCount(1, $component->viewData('genes'));
        $component->assertSee('Invalid URL filters were ignored.')
            ->assertDontSee('GENCC:999999');
        $this->assertSame(['submitters' => $submitter->curie], $this->pathQuery($component));
    }

    /** @test */
    public function array_shaped_submitters_and_malformed_toggles_restore_defaults()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');

        $component = Livewire::withQueryParams([
            'submitters' => ['GENCC:000001'],
            'strong' => ['0'],
            'moderate' => false,
        ])->test(Listing::class);

        $this->assertSame('1', $component->get('curations_strong'));
        $this->assertSame('1', $component->get('curations_moderate'));
        $this->assertCount(1, $component->viewData('genes'));
        $component->assertSee('Invalid URL filters were ignored.');
        $this->assertSame([], $this->pathQuery($component));
    }

    /** @test */
    public function legacy_submitters_are_trimmed_deduplicated_and_checked_against_known_idents()
    {
        $this->shimRegexpSubstrForSqlite();
        $wantedGene = $this->createGeneWithSubmission('GJB2');
        $this->createGeneWithSubmission('BRCA1');
        $submitter = Submission::where('gene_id', $wantedGene->id)->first()->submitter;

        $component = Livewire::withQueryParams(['curations_from_submitters' => [
            ' ' . $submitter->ident . ' ',
            $submitter->ident,
            ['nested'],
            'STALE_IDENT',
        ]])->test(Listing::class);

        $this->assertSame([$submitter->ident], $component->get('curations_from_submitters'));
        $this->assertCount(1, $component->viewData('genes'));
        $component->assertSee('Invalid URL filters were ignored.');
        $this->assertSame(['submitters' => $submitter->curie], $this->pathQuery($component));
    }

    /** @test */
    public function submitters_with_submissions_are_loaded_once_per_component_request()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');
        $matches = 0;

        DB::listen(function ($query) use (&$matches) {
            $sql = strtolower($query->sql);

            if (strpos($sql, 'from "submitters"') !== false && strpos($sql, 'exists') !== false) {
                $matches++;
            }
        });

        $component = Livewire::test(Listing::class);
        $this->assertSame(1, $matches);

        $matches = 0;
        $component->call('selectNoClassifications');
        $this->assertSame(1, $matches);
    }

    /**
     * See GenesListingSelectAllTest for why the classification is reused rather
     * than minted per gene.
     */
    private function createGeneWithSubmission(string $symbol): Gene
    {
        $gene = Gene::factory()->create(['symbol' => $symbol]);

        $classification = Classification::first() ?: Classification::factory()->definitive()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => Disease::factory()->create()->id,
            'classification_id' => $classification->id,
            'submitter_id' => Submitter::factory()->create()->id,
            'inheritance_id' => Inheritance::factory()->create()->id,
        ]);

        return $gene;
    }

    private function pathQuery($component): array
    {
        $query = parse_url(data_get($component->payload, 'effects.path', ''), PHP_URL_QUERY);
        parse_str($query ?? '', $params);

        return $params;
    }
}
