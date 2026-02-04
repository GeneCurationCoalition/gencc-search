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
use Tests\TestCase;

class GenesListingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function genes_listing_component_can_render()
    {
        // Skip this test when using SQLite (uses REGEXP_SUBSTR for ordering)
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('This test requires MySQL for REGEXP_SUBSTR ordering');
        }

        // Create test data
        $this->createTestSubmission();

        $component = Livewire::test(Listing::class);

        $component->assertStatus(200);
    }

    /**
     * @test
     * @group mysql
     *
     * This test requires MySQL due to JSON_EXTRACT queries in the component.
     * SQLite's json_extract behaves differently. Run with: php artisan test --group=mysql
     */
    public function genes_listing_shows_genes_with_submissions()
    {
        // Skip this test when using SQLite (default test database)
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support');
        }

        $gene = Gene::factory()->create([
            'title' => 'BRCA1',
            'counts' => [
                'definitive' => 1,
                'strong' => 0,
                'moderate' => 0,
                'limited' => 0,
                'disputed' => 0,
                'refuted' => 0,
                'animal' => 0,
                'noknown' => 0,
                'supportive' => 0,
            ],
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
            'moi_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        $component = Livewire::test(Listing::class);

        $component->assertSee('BRCA1');
    }

    /**
     * @test
     * @group mysql
     *
     * This test requires MySQL due to JSON_EXTRACT queries in the component.
     * SQLite's json_extract behaves differently. Run with: php artisan test --group=mysql
     */
    public function genes_listing_filters_by_title()
    {
        // Skip this test when using SQLite (default test database)
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support');
        }

        $counts = [
            'definitive' => 1,
            'strong' => 0,
            'moderate' => 0,
            'limited' => 0,
            'disputed' => 0,
            'refuted' => 0,
            'animal' => 0,
            'noknown' => 0,
            'supportive' => 0,
        ];
        $gene1 = Gene::factory()->create(['title' => 'BRCA1', 'counts' => $counts]);
        $gene2 = Gene::factory()->create(['title' => 'TP53', 'counts' => $counts]);
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        Submission::factory()->create([
            'gene_id' => $gene1->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
        ]);

        Submission::factory()->create([
            'gene_id' => $gene2->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
        ]);

        $component = Livewire::test(Listing::class)
            ->set('title', 'BRCA');

        $component->assertSee('BRCA1');
    }

    /** @test */
    public function genes_listing_initializes_submitters()
    {
        // Skip this test when using SQLite (uses REGEXP_SUBSTR for ordering)
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('This test requires MySQL for REGEXP_SUBSTR ordering');
        }

        $submitter = Submitter::factory()->create();
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $inheritance = Inheritance::factory()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
        ]);

        $component = Livewire::test(Listing::class);

        // Check that curations_from_submitters is populated
        $this->assertNotEmpty($component->get('curations_from_submitters'));
    }

    /** @test */
    public function genes_listing_curations_from_submitters_method_toggles_filter()
    {
        // Skip this test when using SQLite (uses REGEXP_SUBSTR for ordering)
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('This test requires MySQL for REGEXP_SUBSTR ordering');
        }

        // Need 2+ submitters to test toggle behavior properly
        // (when all are removed, the filter resets to include all)
        $submitter1 = Submitter::factory()->create();
        $submitter2 = Submitter::factory()->create();
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $inheritance = Inheritance::factory()->create();

        // Create submissions for both submitters
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter1->id,
            'moi_id' => $inheritance->id,
        ]);

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter2->id,
            'moi_id' => $inheritance->id,
        ]);

        $component = Livewire::test(Listing::class);

        // Initially all submitters are selected, so filtering_by_submitter is false
        $this->assertFalse($component->get('filtering_by_submitter'));

        // Call method to toggle a submitter (remove one)
        $component->call('curationsFromSubmitters', [$submitter1->uuid]);

        // Now filtering_by_submitter should be true since not all submitters are selected
        $this->assertTrue($component->get('filtering_by_submitter'));
    }

    /** @test */
    public function genes_listing_resets_curation_filters_when_all_off()
    {
        // Skip this test when using SQLite (uses REGEXP_SUBSTR for ordering)
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('This test requires MySQL for REGEXP_SUBSTR ordering');
        }

        $submitter = Submitter::factory()->create();
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $inheritance = Inheritance::factory()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
        ]);

        // Set all curation filters to 0
        $component = Livewire::test(Listing::class)
            ->set('curations_definitive', 0)
            ->set('curations_strong', 0)
            ->set('curations_moderate', 0)
            ->set('curations_limited', 0)
            ->set('curations_disputed', 0)
            ->set('curations_refuted', 0)
            ->set('curations_animal', 0)
            ->set('curations_noknown', 0)
            ->set('curations_supportive', 0);

        // After render, filters should be reset to 1
        $this->assertEquals(1, $component->get('curations_definitive'));
        $this->assertEquals(1, $component->get('curations_strong'));
    }

    /**
     * Helper to create a complete test submission
     */
    private function createTestSubmission(): Submission
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        return Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
        ]);
    }
}
