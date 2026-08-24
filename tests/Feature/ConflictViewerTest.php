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

    /** Build a known classification while allowing deliberately misleading DB order values. */
    protected function classification(string $name, int $order): Classification
    {
        $curies = [
            'Definitive' => 'GENCC:100001',
            'Strong' => 'GENCC:100002',
            'Moderate' => 'GENCC:100003',
            'Supportive' => 'GENCC:100009',
            'Limited' => 'GENCC:100004',
            'Disputed' => 'GENCC:100005',
            'Disputed Evidence' => 'GENCC:100005',
            'Animal Model Only' => 'GENCC:100007',
            'Refuted' => 'GENCC:100006',
            'Refuted Evidence' => 'GENCC:100006',
            'No Known Disease Relationship' => 'GENCC:100008',
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
        $this->assertArrayHasKey('ClinGen', $row['strong']);
        $this->assertArrayHasKey('Orphanet', $row['other']);
        $this->assertSame(['Definitive'], array_column($row['strong']['ClinGen'], 'label'));
        $this->assertSame(['Limited'], array_column($row['other']['Orphanet'], 'label'));
        $this->assertSame(['GENCC:100001'], array_column($row['strong']['ClinGen'], 'curie'));
        $this->assertSame(['gencc-limited'], array_column($row['other']['Orphanet'], 'css_class'));
    }

    /** @test */
    public function contradictory_assertions_from_one_submitter_are_a_conflict()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $moi = Inheritance::factory()->create();
        $submitter = Submitter::factory()->create(['name' => 'Ambry Genetics']);

        foreach (['Definitive', 'Limited'] as $name) {
            $this->submission([
                'gene_id' => $gene->id,
                'disease_id' => $disease->id,
                'inheritance_id' => $moi->id,
                'classification_id' => $this->classification($name, 10)->id,
                'submitter_id' => $submitter->id,
            ]);
        }

        $row = ConflictFinder::conflicts()->first();

        $this->assertNotNull($row);
        $this->assertArrayHasKey('Ambry Genetics', $row['strong']);
        $this->assertArrayHasKey('Ambry Genetics', $row['other']);
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
    public function severity_tiers_are_derived_from_explicit_curie_buckets()
    {
        $this->assertSame(ConflictFinder::TIER_SUPPORTIVE, ConflictFinder::tierFor('GENCC:100009'));
        $this->assertSame(ConflictFinder::TIER_LIMITED, ConflictFinder::tierFor('GENCC:100004'));

        foreach (['GENCC:100005', 'GENCC:100007', 'GENCC:100006', 'GENCC:100008'] as $curie) {
            $this->assertSame(ConflictFinder::TIER_CONTRADICTORY, ConflictFinder::tierFor($curie));
        }

        $this->assertNull(ConflictFinder::tierFor('GENCC:199999'));
    }

    /** @test */
    public function curies_control_conflict_membership_and_severity_when_database_orders_are_permuted()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $moi = Inheritance::factory()->create();

        $base = [
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'inheritance_id' => $moi->id,
        ];

        $this->submission($base + [
            'classification_id' => $this->classification('Moderate', 999)->id,
            'submitter_id' => Submitter::factory()->create(['name' => 'Strong Lab'])->id,
        ]);
        $this->submission($base + [
            'classification_id' => $this->classification('Animal Model Only', -10)->id,
            'submitter_id' => Submitter::factory()->create(['name' => 'Other Lab'])->id,
        ]);

        $row = ConflictFinder::conflicts()->first();

        $this->assertSame(['Strong Lab'], array_keys($row['strong']));
        $this->assertSame(['Other Lab'], array_keys($row['other']));
        $this->assertSame(ConflictFinder::TIER_CONTRADICTORY, $row['severity_tier']);
    }

    /** @test */
    public function unknown_classifications_never_participate_in_conflicts_or_counts()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $moi = Inheritance::factory()->create();
        $unknown = Classification::factory()->create([
            'curie' => 'GENCC:199999',
            'name' => 'Future Term',
            'order' => 0,
        ]);
        $limited = $this->classification('Limited', 0);

        foreach ([$unknown, $limited] as $classification) {
            $this->submission([
                'gene_id' => $gene->id,
                'disease_id' => $disease->id,
                'inheritance_id' => $moi->id,
                'classification_id' => $classification->id,
                'submitter_id' => Submitter::factory()->create()->id,
            ]);
        }

        $this->assertCount(0, ConflictFinder::conflicts());
    }

    /** @test */
    public function unknown_classifications_are_omitted_from_an_otherwise_valid_conflict()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $moi = Inheritance::factory()->create();
        $classifications = [
            $this->classification('Definitive', 900),
            $this->classification('Limited', -20),
            Classification::factory()->create([
                'curie' => 'GENCC:199999',
                'name' => 'Future Term',
                'order' => 0,
            ]),
        ];

        foreach ($classifications as $classification) {
            $this->submission([
                'gene_id' => $gene->id,
                'disease_id' => $disease->id,
                'inheritance_id' => $moi->id,
                'classification_id' => $classification->id,
                'submitter_id' => Submitter::factory()->create()->id,
            ]);
        }

        $row = ConflictFinder::conflicts()->first();

        $this->assertSame(2, $row['total_count']);
        $this->assertSame(1, $row['strong_count']);
        $this->assertSame(1, $row['other_count']);
        $this->assertStringNotContainsString('Future Term', json_encode($row));
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
    public function each_side_is_ordered_by_evidence_strength_then_by_submitter_name()
    {
        $gene    = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $moi     = Inheritance::factory()->create();

        // Inserted in an order that contradicts the expected output on both axes,
        // so passing cannot be an artefact of submissions.id.
        $inserted = [
            ['Zeta Labs', 'Definitive', 10],
            ['Aardvark Labs', 'Disputed Evidence', 60],
            ['Middle Labs', 'Moderate', 30],
            ['Beta Labs', 'Limited', 50],
            ['Alpha Labs', 'Definitive', 10],
        ];

        foreach ($inserted as [$submitter, $classification, $order]) {
            $this->submission([
                'gene_id'           => $gene->id,
                'disease_id'        => $disease->id,
                'inheritance_id'    => $moi->id,
                'classification_id' => $this->classification($classification, $order)->id,
                'submitter_id'      => Submitter::factory()->create(['name' => $submitter])->id,
            ]);
        }

        $row = ConflictFinder::conflicts()->first();

        // Definitive (10) outranks Moderate (30); the two Definitives tie and fall
        // back to the alphabet, which is also where Zeta loses its insertion lead.
        $this->assertSame(['Alpha Labs', 'Zeta Labs', 'Middle Labs'], array_keys($row['strong']));

        // Strength beats the alphabet: Limited (50) precedes Disputed Evidence (60)
        // even though Aardvark sorts first by name and was inserted first.
        $this->assertSame(['Beta Labs', 'Aardvark Labs'], array_keys($row['other']));
    }

    /** @test */
    public function a_submitters_own_classifications_are_listed_strongest_first()
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

        // One dissenter asserting three different weak classifications, inserted
        // weakest-first.
        $ambry = Submitter::factory()->create(['name' => 'Ambry Genetics'])->id;

        foreach ([['Refuted Evidence', 1], ['Animal Model Only', 999], ['Disputed Evidence', 60], ['Limited', 50]] as [$name, $order]) {
            $this->submission([
                'gene_id'           => $gene->id,
                'disease_id'        => $disease->id,
                'inheritance_id'    => $moi->id,
                'classification_id' => $this->classification($name, $order)->id,
                'submitter_id'      => $ambry,
            ]);
        }

        $row = ConflictFinder::conflicts()->first();

        $this->assertSame(
            ['Limited', 'Disputed Evidence', 'Animal Model Only', 'Refuted Evidence'],
            array_column($row['other']['Ambry Genetics'], 'label')
        );
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
