<?php

namespace Tests\Feature;

use App\Classification;
use App\Disease;
use App\Gene;
use App\Inheritance;
use App\Services\ConflictFinder;
use App\Submission;
use App\Submitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConflictViewerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The conflict set is cached; each test builds its own fixture data.
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

    protected function submission(array $attributes): Submission
    {
        return Submission::factory()->create($attributes + [
            'is_live' => true,
            'status'  => Submission::STATUS_PUBLISHED,
        ]);
    }

    /** @test */
    public function a_triple_with_strong_and_weak_assertions_is_a_conflict()
    {
        $gene       = Gene::factory()->create(['symbol' => 'AGL', 'hgnc_id' => 'HGNC:321']);
        $disease    = Disease::factory()->create(['name' => 'glycogen storage disease III']);
        $moi        = Inheritance::factory()->create(['name' => 'Autosomal recessive']);
        $definitive = $this->classification('Definitive', 10);
        $limited    = $this->classification('Limited', 50);

        $this->submission([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => $moi->id,
            'classification_id' => $definitive->id,
            'submitter_id'      => Submitter::factory()->create(['name' => 'ClinGen'])->id,
        ]);
        $this->submission([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => $moi->id,
            'classification_id' => $limited->id,
            'submitter_id'      => Submitter::factory()->create(['name' => 'Orphanet'])->id,
        ]);

        $conflicts = ConflictFinder::conflicts();

        $this->assertCount(1, $conflicts);

        $row = $conflicts->first();
        $this->assertSame('AGL', $row['gene_symbol']);
        $this->assertSame('glycogen storage disease III', $row['disease_name']);
        $this->assertSame('Autosomal recessive', $row['moi']);
        $this->assertSame(1, $row['strong_count']);
        $this->assertSame(1, $row['other_count']);
        $this->assertSame(2, $row['total_count']);
        $this->assertSame('Definitive', $row['strongest']);
        $this->assertSame('Limited', $row['weakest']);
        $this->assertArrayHasKey('ClinGen', $row['strong']);
        $this->assertArrayHasKey('Orphanet', $row['other']);
    }

    /** @test */
    public function two_strong_assertions_are_not_a_conflict()
    {
        $gene    = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $moi     = Inheritance::factory()->create();

        foreach ([['Definitive', 10], ['Strong', 20]] as [$name, $order]) {
            $this->submission([
                'gene_id'           => $gene->id,
                'disease_id'        => $disease->id,
                'inheritance_id'    => $moi->id,
                'classification_id' => $this->classification($name, $order)->id,
                'submitter_id'      => Submitter::factory()->create()->id,
            ]);
        }

        $this->assertCount(0, ConflictFinder::conflicts());
    }

    /** @test */
    public function two_weak_assertions_are_not_a_conflict()
    {
        $gene    = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $moi     = Inheritance::factory()->create();

        foreach ([['Limited', 50], ['Disputed', 60]] as [$name, $order]) {
            $this->submission([
                'gene_id'           => $gene->id,
                'disease_id'        => $disease->id,
                'inheritance_id'    => $moi->id,
                'classification_id' => $this->classification($name, $order)->id,
                'submitter_id'      => Submitter::factory()->create()->id,
            ]);
        }

        $this->assertCount(0, ConflictFinder::conflicts());
    }

    /** @test */
    public function moderate_still_counts_as_strong_evidence()
    {
        $gene    = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $moi     = Inheritance::factory()->create();

        $this->submission([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => $moi->id,
            'classification_id' => $this->classification('Moderate', 30)->id,
            'submitter_id'      => Submitter::factory()->create()->id,
        ]);
        $this->submission([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => $moi->id,
            'classification_id' => $this->classification('Supportive', 40)->id,
            'submitter_id'      => Submitter::factory()->create()->id,
        ]);

        $conflicts = ConflictFinder::conflicts();

        $this->assertCount(1, $conflicts);
        $this->assertSame(1, $conflicts->first()['strong_count']);
        $this->assertSame(1, $conflicts->first()['other_count']);
    }

    /** @test */
    public function unpublished_and_non_live_submissions_are_excluded()
    {
        $gene    = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $moi     = Inheritance::factory()->create();

        $this->submission([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => $moi->id,
            'classification_id' => $this->classification('Definitive', 10)->id,
            'submitter_id'      => Submitter::factory()->create()->id,
        ]);

        // The only weak assertions are hidden, so nothing is left to conflict with.
        Submission::factory()->unpublished()->create([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => $moi->id,
            'classification_id' => $this->classification('Limited', 50)->id,
            'submitter_id'      => Submitter::factory()->create()->id,
        ]);
        Submission::factory()->historical()->create([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => $moi->id,
            'classification_id' => $this->classification('Refuted', 70)->id,
            'submitter_id'      => Submitter::factory()->create()->id,
        ]);

        $this->assertCount(0, ConflictFinder::conflicts());
    }

    /** @test */
    public function differing_inheritance_splits_a_gene_disease_pair_into_separate_groups()
    {
        $gene    = Gene::factory()->create();
        $disease = Disease::factory()->create();

        $this->submission([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => Inheritance::factory()->autosomalDominant()->create()->id,
            'classification_id' => $this->classification('Definitive', 10)->id,
            'submitter_id'      => Submitter::factory()->create()->id,
        ]);
        $this->submission([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => Inheritance::factory()->autosomalRecessive()->create()->id,
            'classification_id' => $this->classification('Limited', 50)->id,
            'submitter_id'      => Submitter::factory()->create()->id,
        ]);

        $this->assertCount(0, ConflictFinder::conflicts());
    }

    /** @test */
    public function conflict_viewer_page_returns_200_and_renders_the_component()
    {
        $response = $this->get('/conflict-viewer');

        $response->assertStatus(200);
        $response->assertViewIs('conflict-viewer.index');
        $response->assertViewHas('page_meta');
        $response->assertSee('conflicting gene');
    }

    /** @test */
    public function the_listing_component_filters_by_gene_and_disease()
    {
        $moi        = Inheritance::factory()->create();
        $definitive = $this->classification('Definitive', 10);
        $limited    = $this->classification('Limited', 50);

        foreach ([['BRCA1', 'breast cancer'], ['AGL', 'glycogen storage disease III']] as [$symbol, $diseaseName]) {
            $gene    = Gene::factory()->create(['symbol' => $symbol]);
            $disease = Disease::factory()->create(['name' => $diseaseName]);

            foreach ([$definitive, $limited] as $classification) {
                $this->submission([
                    'gene_id'           => $gene->id,
                    'disease_id'        => $disease->id,
                    'inheritance_id'    => $moi->id,
                    'classification_id' => $classification->id,
                    'submitter_id'      => Submitter::factory()->create()->id,
                ]);
            }
        }

        Livewire::test(\App\Http\Livewire\ConflictViewer\Listing::class)
            ->assertSee('BRCA1')
            ->assertSee('AGL')
            ->set('gene', 'brca')          // case-insensitive
            ->assertSee('BRCA1')
            ->assertDontSee('glycogen storage disease III')
            ->set('gene', '')
            ->set('disease', 'GLYCOGEN')   // case-insensitive
            ->assertSee('AGL')
            ->assertDontSee('breast cancer');
    }

    /** @test */
    public function sorting_toggles_direction_and_ignores_unknown_columns()
    {
        $component = Livewire::test(\App\Http\Livewire\ConflictViewer\Listing::class)
            ->assertSet('sortField', 'total_count')
            ->assertSet('sortDirection', 'desc')
            ->call('sortBy', 'gene_symbol')
            ->assertSet('sortField', 'gene_symbol')
            ->assertSet('sortDirection', 'asc')
            ->call('sortBy', 'gene_symbol')
            ->assertSet('sortDirection', 'desc');

        $component->call('sortBy', 'not_a_column')
            ->assertSet('sortField', 'gene_symbol')
            ->assertSet('sortDirection', 'desc');
    }

    /** @test */
    public function filtering_resets_pagination_to_the_first_page()
    {
        Livewire::test(\App\Http\Livewire\ConflictViewer\Listing::class)
            ->set('page', 3)
            ->set('gene', 'BRCA')
            ->assertSet('page', 1);
    }

    /** @test */
    public function the_severity_tier_is_derived_from_the_weakest_classification_order()
    {
        // Supportive 40, Limited 50, then Disputed 60 / Refuted 70 / Animal 80 /
        // No Known 90 all collapse into the contradictory tier. 80 never occurs in
        // the production data, which is exactly why the boundary is a >= 60 test
        // rather than an enumeration of the orders that happen to be present.
        $this->assertSame(ConflictFinder::TIER_SUPPORTIVE, ConflictFinder::tierFor(40));
        $this->assertSame(ConflictFinder::TIER_LIMITED, ConflictFinder::tierFor(50));
        $this->assertSame(ConflictFinder::TIER_CONTRADICTORY, ConflictFinder::tierFor(60));
        $this->assertSame(ConflictFinder::TIER_CONTRADICTORY, ConflictFinder::tierFor(70));
        $this->assertSame(ConflictFinder::TIER_CONTRADICTORY, ConflictFinder::tierFor(80));
        $this->assertSame(ConflictFinder::TIER_CONTRADICTORY, ConflictFinder::tierFor(90));
    }

    /** @test */
    public function folded_rows_carry_a_severity_tier_and_the_dissenting_submitter_slugs()
    {
        $gene    = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $moi     = Inheritance::factory()->create();

        $this->submission([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => $moi->id,
            'classification_id' => $this->classification('Definitive', 10)->id,
            'submitter_id'      => Submitter::factory()->create(['name' => 'ClinGen'])->id,
        ]);

        // Two dissenters, one of them asserting twice — it must appear once.
        $limited = $this->classification('Limited', 50);
        $ambry   = Submitter::factory()->create(['name' => 'Ambry Genetics'])->id;

        foreach ([$limited->id, $this->classification('Disputed Evidence', 60)->id] as $classificationId) {
            $this->submission([
                'gene_id'           => $gene->id,
                'disease_id'        => $disease->id,
                'inheritance_id'    => $moi->id,
                'classification_id' => $classificationId,
                'submitter_id'      => $ambry,
            ]);
        }

        $this->submission([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => $moi->id,
            'classification_id' => $limited->id,
            'submitter_id'      => Submitter::factory()->create(['name' => 'Labcorp Genetics (formerly Invitae)'])->id,
        ]);

        $row = ConflictFinder::conflicts()->first();

        // Weakest present is Disputed Evidence (60).
        $this->assertSame(ConflictFinder::TIER_CONTRADICTORY, $row['severity_tier']);

        $this->assertSame([
            'ambry-genetics'                     => 'Ambry Genetics',
            'labcorp-genetics-formerly-invitae'  => 'Labcorp Genetics (formerly Invitae)',
        ], $row['other_slugs']);

        // The strong-side submitter is not a dissenter.
        $this->assertArrayNotHasKey('clingen', $row['other_slugs']);
    }

    /** @test */
    public function the_clear_cache_command_flushes_the_cached_conflicts()
    {
        $gene    = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $moi     = Inheritance::factory()->create();

        $this->submission([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => $moi->id,
            'classification_id' => $this->classification('Definitive', 10)->id,
            'submitter_id'      => Submitter::factory()->create()->id,
        ]);

        $this->assertCount(0, ConflictFinder::conflicts());

        $this->submission([
            'gene_id'           => $gene->id,
            'disease_id'        => $disease->id,
            'inheritance_id'    => $moi->id,
            'classification_id' => $this->classification('Limited', 50)->id,
            'submitter_id'      => Submitter::factory()->create()->id,
        ]);

        // Still the stale cached result...
        $this->assertCount(0, ConflictFinder::conflicts());

        $this->artisan('conflicts:clear-cache')->assertExitCode(0);

        $this->assertCount(1, ConflictFinder::conflicts());
    }
}
