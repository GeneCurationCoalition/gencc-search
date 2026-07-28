<?php

namespace Tests\Feature\Livewire;

use App\Classification;
use App\Disease;
use App\Gene;
use App\Http\Livewire\ConflictViewer\Listing;
use App\Inheritance;
use App\Services\ConflictFinder;
use App\Submission;
use App\Submitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Facet behaviour for the conflict viewer listing.
 *
 * The fixtures here are synthetic and small. The corresponding check against the
 * real data, on snapshot gencc_sub_20260720-050000, is:
 *
 *   - default view: 2,898 rows
 *   - hiding only the supportive tier:  1,094 rows
 *   - hiding only Orphanet:             1,094 rows
 *
 * The same number by two routes, because Orphanet is the sole dissenter on exactly
 * the 1,804 supportive-tier rows. That equality is a property of this snapshot, NOT
 * an invariant: if a future snapshot has any supportive-tier row with a non-Orphanet
 * dissenter, the two numbers diverge and that is correct, not a regression. Of the
 * 1,094 surviving rows with Orphanet hidden, 209 still show an Orphanet pill because
 * a second, visible submitter also dissents there.
 */
class ConflictViewerListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ConflictFinder::flush();
    }

    /**
     * ClassificationFactory defaults to order 1..6, but production ranks with
     * 10..90 and STRONG_MAX_ORDER is 30 — so always set order explicitly.
     */
    protected function classification(string $name, int $order): Classification
    {
        return Classification::factory()->create([
            'name'  => $name,
            'title' => $name,
            'order' => $order,
        ]);
    }

    protected function submitter(string $name): Submitter
    {
        return Submitter::factory()->create(['name' => $name]);
    }

    /**
     * Build one conflicting triple: a Definitive from ClinGen, plus one weak
     * assertion per entry in $dissenters (submitter name => [name, order]).
     *
     * @param  string  $symbol
     * @param  string  $diseaseName
     * @param  array  $dissenters
     * @return void
     */
    protected function conflict(string $symbol, string $diseaseName, array $dissenters)
    {
        $gene    = Gene::factory()->create(['symbol' => $symbol]);
        $disease = Disease::factory()->create(['name' => $diseaseName]);
        $moi     = Inheritance::factory()->create();

        $base = [
            'gene_id'        => $gene->id,
            'disease_id'     => $disease->id,
            'inheritance_id' => $moi->id,
            'is_live'        => true,
            'status'         => Submission::STATUS_PUBLISHED,
        ];

        Submission::factory()->create($base + [
            'classification_id' => $this->classification('Definitive', 10)->id,
            'submitter_id'      => $this->submitter('ClinGen')->id,
        ]);

        foreach ($dissenters as $submitterName => [$classificationName, $order]) {
            Submission::factory()->create($base + [
                'classification_id' => $this->classification($classificationName, $order)->id,
                'submitter_id'      => $this->submitter($submitterName)->id,
            ]);
        }
    }

    /** One row per tier, each with a single distinct dissenter. */
    protected function oneRowPerTier()
    {
        $this->conflict('AGL', 'glycogen storage disease III', ['Orphanet' => ['Supportive', 40]]);
        $this->conflict('BRCA1', 'breast cancer', ['Ambry Genetics' => ['Limited', 50]]);
        $this->conflict('TTN', 'dilated cardiomyopathy', ['Illumina' => ['Refuted Evidence', 70]]);
    }

    /** @test */
    public function hiding_a_tier_removes_exactly_those_rows_and_hiding_two_tiers_composes()
    {
        $this->oneRowPerTier();

        Livewire::test(Listing::class)
            ->assertSee('AGL')
            ->assertSee('BRCA1')
            ->assertSee('TTN')
            ->set('hideTiers', 'supportive')
            ->assertDontSee('AGL')
            ->assertSee('BRCA1')
            ->assertSee('TTN')
            ->set('hideTiers', 'contradictory,supportive')
            ->assertDontSee('AGL')
            ->assertSee('BRCA1')
            ->assertDontSee('TTN');
    }

    /** @test */
    public function toggling_a_tier_adds_then_removes_it_from_the_exclusion_list()
    {
        Livewire::test(Listing::class)
            ->assertSet('hideTiers', '')
            ->call('toggleTier', 'supportive')
            ->assertSet('hideTiers', 'supportive')
            ->call('toggleTier', 'limited')
            // Sorted, so the same selection always produces the same URL.
            ->assertSet('hideTiers', 'limited,supportive')
            ->call('toggleTier', 'supportive')
            ->assertSet('hideTiers', 'limited');
    }

    /** @test */
    public function hiding_a_submitter_only_drops_rows_where_it_is_the_sole_dissenter()
    {
        // ATM has two dissenters, KCNQ1 has only Orphanet.
        $this->conflict('ATM', 'ataxia telangiectasia', [
            'Orphanet'       => ['Supportive', 40],
            'Ambry Genetics' => ['Limited', 50],
        ]);
        $this->conflict('KCNQ1', 'long QT syndrome', ['Orphanet' => ['Supportive', 40]]);

        Livewire::test(Listing::class)
            ->assertSee('ATM')
            ->assertSee('KCNQ1')
            ->set('hideDissenters', 'orphanet')
            // Ambry still dissents on ATM, so ATM survives...
            ->assertSee('ATM')
            // ...and Orphanet's pill is still rendered there: the facet filters
            // rows, not cells.
            ->assertSee('Orphanet')
            // KCNQ1 had no other dissenter.
            ->assertDontSee('KCNQ1')
            // Hiding both dissenters drops ATM as well.
            ->set('hideDissenters', 'ambry-genetics,orphanet')
            ->assertDontSee('ATM')
            ->assertDontSee('KCNQ1');
    }

    /** @test */
    public function hiding_every_tier_renders_the_empty_state_rather_than_erroring()
    {
        $this->oneRowPerTier();

        Livewire::test(Listing::class)
            ->set('hideTiers', 'supportive,limited,contradictory')
            ->assertOk()
            ->assertSee('alert alert-info', false)
            ->assertDontSee('AGL');
    }

    /** @test */
    public function hiding_every_submitter_renders_the_empty_state_rather_than_erroring()
    {
        $this->oneRowPerTier();

        Livewire::test(Listing::class)
            ->set('hideDissenters', 'orphanet,ambry-genetics,illumina')
            ->assertOk()
            ->assertSee('alert alert-info', false)
            ->assertDontSee('AGL');
    }

    /** @test */
    public function tier_counts_respond_to_hidden_submitters_but_ignore_hidden_tiers()
    {
        $this->oneRowPerTier();

        $component = Livewire::test(Listing::class);

        $this->assertSame(
            ['supportive' => 1, 'limited' => 1, 'contradictory' => 1],
            $component->viewData('tier_counts')
        );

        // A facet never filters itself: hiding the supportive tier must not zero
        // its own count, or the user could not tell what re-enabling it would do.
        $component->set('hideTiers', 'supportive');
        $this->assertSame(
            ['supportive' => 1, 'limited' => 1, 'contradictory' => 1],
            $component->viewData('tier_counts')
        );

        // Hiding Orphanet removes the only supportive-tier row.
        $component->set('hideTiers', '')->set('hideDissenters', 'orphanet');
        $this->assertSame(
            ['supportive' => 0, 'limited' => 1, 'contradictory' => 1],
            $component->viewData('tier_counts')
        );
    }

    /** @test */
    public function submitter_counts_respond_to_hidden_tiers_but_ignore_hidden_submitters()
    {
        $this->oneRowPerTier();

        $component = Livewire::test(Listing::class);

        $this->assertSame(
            ['ambry-genetics' => 1, 'illumina' => 1, 'orphanet' => 1],
            $this->countsBySlug($component->viewData('dissenter_options'))
        );

        // Hiding a submitter does not change its own count...
        $component->set('hideDissenters', 'orphanet');
        $this->assertSame(
            ['ambry-genetics' => 1, 'illumina' => 1, 'orphanet' => 1],
            $this->countsBySlug($component->viewData('dissenter_options'))
        );

        // ...but hiding a tier does.
        $component->set('hideDissenters', '')->set('hideTiers', 'supportive');
        $this->assertSame(
            ['ambry-genetics' => 1, 'illumina' => 1, 'orphanet' => 0],
            $this->countsBySlug($component->viewData('dissenter_options'))
        );
    }

    /** @test */
    public function both_facet_counts_respect_the_text_filters()
    {
        $this->oneRowPerTier();

        $component = Livewire::test(Listing::class)->set('gene', 'BRCA');

        $this->assertSame(
            ['supportive' => 0, 'limited' => 1, 'contradictory' => 0],
            $component->viewData('tier_counts')
        );

        $this->assertSame(
            ['ambry-genetics' => 1, 'illumina' => 0, 'orphanet' => 0],
            $this->countsBySlug($component->viewData('dissenter_options'))
        );
    }

    /** @test */
    public function a_submitter_option_stays_listed_while_it_is_hidden()
    {
        $this->oneRowPerTier();

        // Options come from the unfiltered set, so an unchecked submitter is
        // always re-checkable rather than vanishing from the dropdown.
        $options = Livewire::test(Listing::class)
            ->set('hideDissenters', 'orphanet,ambry-genetics,illumina')
            ->viewData('dissenter_options');

        $slugs = collect($options)->pluck('slug')->sort()->values()->all();

        $this->assertSame(['ambry-genetics', 'illumina', 'orphanet'], $slugs);
    }

    /** @test */
    public function toggling_a_facet_resets_pagination_to_the_first_page()
    {
        // updating() does not fire for wire:click methods, so each toggle has to
        // reset the page itself.
        Livewire::test(Listing::class)
            ->set('page', 3)
            ->call('toggleTier', 'supportive')
            ->assertSet('page', 1);

        Livewire::test(Listing::class)
            ->set('page', 3)
            ->call('toggleDissenter', 'orphanet')
            ->assertSet('page', 1);
    }

    /** @test */
    public function clearing_filters_resets_every_filter_and_the_page()
    {
        $this->oneRowPerTier();

        Livewire::test(Listing::class)
            ->set('gene', 'BRCA')
            ->set('disease', 'breast')
            ->set('hideTiers', 'supportive')
            ->set('hideDissenters', 'orphanet')
            ->set('page', 2)
            ->call('clearFilters')
            ->assertSet('gene', '')
            ->assertSet('disease', '')
            ->assertSet('hideTiers', '')
            ->assertSet('hideDissenters', '')
            ->assertSet('page', 1)
            ->assertSee('AGL')
            ->assertSee('BRCA1')
            ->assertSee('TTN');
    }

    /** @test */
    public function the_removed_columns_are_no_longer_sortable()
    {
        $component = Livewire::test(Listing::class)
            ->call('sortBy', 'other_count')
            ->assertSet('sortField', 'total_count')
            ->call('sortBy', 'min_order')
            ->assertSet('sortField', 'total_count')
            ->call('sortBy', 'strong_count')
            ->assertSet('sortField', 'total_count');

        // The headers themselves no longer offer the interaction.
        $component->assertDontSee("sortBy('other_count')", false)
            ->assertDontSee("sortBy('min_order')", false)
            ->assertDontSee("sortBy('strong_count')", false);
    }

    /** @test */
    public function filter_state_is_seeded_from_the_query_string_on_a_full_page_load()
    {
        $this->oneRowPerTier();

        // Livewire::test cannot seed $queryString in v2 (withQueryParams is v3),
        // so this covers the URL round-trip end to end through a real request.
        $this->get('/conflict-viewer?hideTiers=supportive')
            ->assertStatus(200)
            ->assertDontSee('AGL')
            ->assertSee('BRCA1')
            ->assertSee('TTN');

        $this->get('/conflict-viewer?hideDissenters=orphanet')
            ->assertStatus(200)
            ->assertDontSee('AGL')
            ->assertSee('BRCA1');
    }

    /**
     * Reduce the option list to slug => count, sorted by slug for stable asserts.
     *
     * @param  array  $options
     * @return array
     */
    protected function countsBySlug(array $options): array
    {
        $counts = [];

        foreach ($options as $option) {
            $counts[$option['slug']] = $option['count'];
        }

        ksort($counts);

        return $counts;
    }
}
