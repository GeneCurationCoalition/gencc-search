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

/** Facet, filtering, sorting, and pagination behavior for the conflict viewer. */
class ConflictViewerListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ConflictFinder::flush();
    }

    /** Build a known classification while allowing deliberately misleading DB order values. */
    protected function classification(string $name, int $order): Classification
    {
        $curies = [
            'Definitive' => 'GENCC:100001',
            'Limited' => 'GENCC:100004',
            'Refuted Evidence' => 'GENCC:100006',
        ];

        if ($existing = Classification::where('curie', $curies[$name])->first()) {
            return $existing;
        }

        return Classification::factory()->create([
            'curie' => $curies[$name],
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
     * assertion per entry in $submitters (submitter name => [name, order]).
     *
     * @param  string  $symbol
     * @param  string  $diseaseName
     * @param  array  $submitters
     * @return void
     */
    protected function conflict(string $symbol, string $diseaseName, array $submitters)
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

        foreach ($submitters as $submitterName => [$classificationName, $order]) {
            Submission::factory()->create($base + [
                'classification_id' => $this->classification($classificationName, $order)->id,
                'submitter_id'      => $this->submitter($submitterName)->id,
            ]);
        }
    }

    /** Three eligible conflict rows, each with a single distinct other-side submitter. */
    protected function conflictRows()
    {
        $this->conflict('AGL', 'glycogen storage disease III', ['Orphanet' => ['Limited', 50]]);
        $this->conflict('BRCA1', 'breast cancer', ['Ambry Genetics' => ['Limited', 50]]);
        $this->conflict('TTN', 'dilated cardiomyopathy', ['Illumina' => ['Refuted Evidence', 70]]);
    }

    /** @test */
    public function hiding_a_submitter_only_drops_rows_where_it_is_the_only_other_side_submitter()
    {
        // ATM has two other-side submitters; KCNQ1 has only Orphanet.
        $this->conflict('ATM', 'ataxia telangiectasia', [
            'Orphanet'       => ['Refuted Evidence', 70],
            'Ambry Genetics' => ['Limited', 50],
        ]);
        $this->conflict('KCNQ1', 'long QT syndrome', ['Orphanet' => ['Limited', 50]]);

        Livewire::test(Listing::class)
            ->assertSee('ATM')
            ->assertSee('KCNQ1')
            ->set('hideSubmitters', 'orphanet')
            // Ambry still dissents on ATM, so ATM survives...
            ->assertSee('ATM')
            // ...and Orphanet's pill is still rendered there: the facet filters
            // rows, not cells.
            ->assertSee('Orphanet')
            // KCNQ1 had no other submitter in the facet.
            ->assertDontSee('KCNQ1')
            // Hiding both submitters drops ATM as well.
            ->set('hideSubmitters', 'ambry-genetics,orphanet')
            ->assertDontSee('ATM')
            ->assertDontSee('KCNQ1');
    }

    /** @test */
    public function conflict_pill_colors_are_derived_from_curies_when_database_names_change()
    {
        $classification = $this->classification('Limited', 50);
        $classification->name = 'Limited display label';
        $classification->save();
        $this->conflict('BRCA1', 'breast cancer', [
            'Ambry Genetics' => ['Limited', 50],
        ]);

        Livewire::test(Listing::class)
            ->assertSee('Limited display label')
            ->assertSee('gencc-limited', false);
    }

    /** @test */
    public function the_simplified_headers_tooltips_and_submitter_label_render()
    {
        Livewire::test(Listing::class)
            ->assertSee('D/S/M')
            ->assertSee('title="D/S/M: Definitive, Strong, Moderate"', false)
            ->assertSee('L/P/R/N')
            ->assertSee('title="L/P/R/N: Limited, Disputed, Refuted, No Known Disease Relationship (P denotes Disputed)"', false)
            ->assertSee('Submitters')
            ->assertDontSee('Dissenting submitters')
            ->assertDontSee('Strong Evidence')
            ->assertDontSee('Other Evidence')
            ->assertDontSee('toggleTier', false)
            ->assertDontSee('Strong evidence vs');
    }

    /** @test */
    public function hiding_every_submitter_renders_the_empty_state_rather_than_erroring()
    {
        $this->conflictRows();

        Livewire::test(Listing::class)
            ->set('hideSubmitters', 'orphanet,ambry-genetics,illumina')
            ->assertOk()
            ->assertSee('alert alert-info', false)
            ->assertDontSee('AGL');
    }

    /** @test */
    public function submitter_counts_ignore_hidden_submitters()
    {
        $this->conflictRows();

        $component = Livewire::test(Listing::class);

        $this->assertSame(
            ['ambry-genetics' => 1, 'illumina' => 1, 'orphanet' => 1],
            $this->countsBySlug($component->viewData('submitter_options'))
        );

        // A facet never filters its own residual counts.
        $component->set('hideSubmitters', 'orphanet');
        $this->assertSame(
            ['ambry-genetics' => 1, 'illumina' => 1, 'orphanet' => 1],
            $this->countsBySlug($component->viewData('submitter_options'))
        );
    }

    /** @test */
    public function submitter_counts_respect_the_text_filters()
    {
        $this->conflictRows();

        $component = Livewire::test(Listing::class)->set('gene', 'BRCA');

        $this->assertSame(
            ['ambry-genetics' => 1, 'illumina' => 0, 'orphanet' => 0],
            $this->countsBySlug($component->viewData('submitter_options'))
        );
    }

    /** @test */
    public function gene_and_disease_filters_normalize_pasted_whitespace_but_preserve_the_bound_value()
    {
        $this->conflictRows();

        $gene = "\u{00A0}BRCA1\u{00A0}";
        $component = Livewire::test(Listing::class)->set('gene', $gene);

        $component->assertSee('BRCA1')->assertDontSee('AGL')->assertDontSee('TTN');
        $this->assertSame($gene, $component->get('gene'));

        $disease = "  glycogen\u{00A0}storage   disease III  ";
        $component = Livewire::test(Listing::class)->set('disease', $disease);

        $component->assertSee('AGL')->assertDontSee('BRCA1')->assertDontSee('TTN');
        $this->assertSame($disease, $component->get('disease'));

        $component->set('disease', 'breast')
            ->assertSee('BRCA1')
            ->assertDontSee('AGL')
            ->assertDontSee('TTN');
    }

    /** @test */
    public function malformed_and_non_positive_pages_use_the_same_canonical_first_page()
    {
        $this->conflictRows();

        foreach ([0, -3] as $invalidPage) {
            $component = Livewire::test(Listing::class)->set('page', $invalidPage);

            $this->assertSame(1, $component->get('page'));
            $this->assertSame(1, $component->viewData('conflicts')->currentPage());
        }
    }

    /** @test */
    public function malformed_page_query_returns_200_on_page_one()
    {
        $this->conflictRows();

        $this->get('/conflict-viewer?page=abc')
            ->assertOk()
            ->assertSee('AGL');
    }

    /** @test */
    public function a_submitter_option_stays_listed_while_it_is_hidden()
    {
        $this->conflictRows();

        // Options come from the unfiltered set, so an unchecked submitter is
        // always re-checkable rather than vanishing from the dropdown.
        $options = Livewire::test(Listing::class)
            ->set('hideSubmitters', 'orphanet,ambry-genetics,illumina')
            ->viewData('submitter_options');

        $slugs = collect($options)->pluck('slug')->sort()->values()->all();

        $this->assertSame(['ambry-genetics', 'illumina', 'orphanet'], $slugs);
    }

    /** @test */
    public function toggling_a_submitter_resets_pagination_to_the_first_page()
    {
        $this->conflictRows();

        // updating() does not fire for wire:click methods, so each toggle has to
        // reset the page itself.
        Livewire::test(Listing::class)
            ->set('page', 3)
            ->call('toggleSubmitter', 'orphanet')
            ->assertSet('page', 1);
    }

    /** @test */
    public function clearing_filters_resets_every_filter_and_the_page()
    {
        $this->conflictRows();

        Livewire::test(Listing::class)
            ->set('gene', 'BRCA')
            ->set('disease', 'breast')
            ->set('hideSubmitters', 'orphanet')
            ->set('page', 2)
            ->call('clearFilters')
            ->assertSet('gene', '')
            ->assertSet('disease', '')
            ->assertSet('hideSubmitters', '')
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
    public function submitter_state_is_seeded_from_the_query_string()
    {
        $this->conflictRows();

        // Livewire::test cannot seed $queryString in v2 (withQueryParams is v3),
        // so this covers the URL round-trip end to end through a real request.
        $this->get('/conflict-viewer?hideSubmitters=orphanet')
            ->assertStatus(200)
            ->assertDontSee('AGL')
            ->assertSee('BRCA1');
    }

    /** @test */
    public function array_valued_exclusions_return_200_warn_and_use_the_unfiltered_default()
    {
        $this->conflictRows();

        $this->get('/conflict-viewer?' . http_build_query(['hideSubmitters' => ['orphanet']]))
            ->assertOk()
            ->assertSee('Invalid URL filters were ignored.')
            ->assertSee('role="alert"', false)
            ->assertSee('href="' . route('conflict-viewer') . '"', false)
            ->assertSee('AGL')
            ->assertSee('BRCA1')
            ->assertSee('TTN');
    }

    /** @test */
    public function exclusions_are_trimmed_deduplicated_intersected_and_canonicalized()
    {
        $this->conflictRows();

        Livewire::test(Listing::class)
            ->set('hideSubmitters', ' orphanet,orphanet,missing ')
            ->assertSet('hideSubmitters', 'orphanet')
            ->assertSee('Invalid URL filters were ignored.')
            ->assertDontSee('AGL')
            ->assertSee('BRCA1')
            ->assertSee('TTN')
            ->assertDontSee('-1 of');
    }

    /** @test */
    public function invalid_only_exclusions_become_the_unfiltered_default()
    {
        $this->conflictRows();

        Livewire::test(Listing::class)
            ->set('hideSubmitters', 'missing')
            ->assertSet('hideSubmitters', '')
            ->assertSee('Invalid URL filters were ignored.')
            ->assertSee('AGL')
            ->assertSee('BRCA1')
            ->assertSee('TTN');
    }

    /** @test */
    public function valid_exclusions_are_sorted_without_a_warning()
    {
        $this->conflictRows();

        Livewire::test(Listing::class)
            ->set('hideSubmitters', 'orphanet,ambry-genetics')
            ->assertSet('hideSubmitters', 'ambry-genetics,orphanet')
            ->assertDontSee('Invalid URL filters were ignored.');
    }

    /** @test */
    public function toggle_actions_reject_unknown_and_non_scalar_values()
    {
        $this->conflictRows();

        Livewire::test(Listing::class)
            ->call('toggleSubmitter', 'missing')
            ->call('toggleSubmitter', ['orphanet'])
            ->assertSet('hideSubmitters', '');
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
