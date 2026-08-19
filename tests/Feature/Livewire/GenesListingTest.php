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
            'inheritance_id' => $inheritance->id,
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
            'inheritance_id' => $inheritance->id,
        ]);

        Submission::factory()->create([
            'gene_id' => $gene2->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
        ]);

        $component = Livewire::test(Listing::class)
            ->set('title', 'BRCA');

        $component->assertSee('BRCA1');
    }

    /** @test */
    public function genes_listing_text_filters_reset_pagination_to_first_page()
    {
        $component = new Listing();

        $component->setPage(6);
        $this->assertSame(6, $component->page);

        $component->updating('title', 'A');
        $this->assertSame(1, $component->page);

        $component->setPage(6);
        $this->assertSame(6, $component->page);

        $component->updating('hasDisease', 'deaf');
        $this->assertSame(1, $component->page);
    }

    /** @test */
    public function genes_listing_submitter_filter_resets_pagination_to_first_page()
    {
        $component = new Listing();
        $component->curations_from_submitters = ['GENCC_SUBMITTER_1', 'GENCC_SUBMITTER_2'];

        $component->setPage(6);
        $this->assertSame(6, $component->page);

        $component->curationsFromSubmitters(['GENCC_SUBMITTER_1']);

        $this->assertSame(1, $component->page);
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
            'inheritance_id' => $inheritance->id,
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
            'inheritance_id' => $inheritance->id,
        ]);

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter2->id,
            'inheritance_id' => $inheritance->id,
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
    public function genes_listing_honours_an_explicit_all_off_classification_selection()
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
            'inheritance_id' => $inheritance->id,
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

        // An explicit all-off selection is now honoured rather than reset (#203),
        // so the toggles stay off and the listing shows nothing. Defaulting to
        // all-on still happens, but only on a fresh load — see
        // GenesListingSelectAllTest::fresh_load_defaults_every_classification_on.
        $this->assertEquals(0, $component->get('curations_definitive'));
        $this->assertEquals(0, $component->get('curations_strong'));
        $this->assertCount(0, $component->viewData('genes'));
    }

    /**
     * @test
     * @group mysql
     *
     * This test verifies the submitter filter correctly filters genes by submitter.
     * This is a regression test for the query optimization that uses submitter_id
     * directly instead of nested whereHas through the submitter relationship.
     */
    public function genes_listing_filters_correctly_by_submitter()
    {
        // Skip this test when using SQLite (uses REGEXP_SUBSTR for ordering)
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('This test requires MySQL for REGEXP_SUBSTR ordering');
        }

        // Create two submitters with different submissions
        $submitter1 = Submitter::factory()->create(['ident' => 'GENCC_TEST001']);
        $submitter2 = Submitter::factory()->create(['ident' => 'GENCC_TEST002']);

        // Create two genes - one for each submitter
        $gene1 = Gene::factory()->create(['symbol' => 'TESTGENE1', 'title' => 'TESTGENE1']);
        $gene2 = Gene::factory()->create(['symbol' => 'TESTGENE2', 'title' => 'TESTGENE2']);

        $disease = Disease::factory()->create();
        $classification = Classification::factory()->definitive()->create();
        $inheritance = Inheritance::factory()->create();

        // Gene 1 has submission from submitter 1 only
        Submission::factory()->create([
            'gene_id' => $gene1->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter1->id,
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        // Gene 2 has submission from submitter 2 only
        Submission::factory()->create([
            'gene_id' => $gene2->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter2->id,
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        // Filter by submitter 1 only using the ident (uuid)
        $component = Livewire::test(Listing::class)
            ->set('curations_from_submitters', [$submitter1->ident]);

        // Should show TESTGENE1 (has submission from submitter1)
        $component->assertSee('TESTGENE1');
        // Should NOT show TESTGENE2 (only has submission from submitter2)
        $component->assertDontSee('TESTGENE2');
    }

    /**
     * @test
     * @group mysql
     *
     * Verify that the submitter filtering uses the optimized query pattern.
     * This test ensures the fix using whereIn('submitter_id', ...) is preserved.
     */
    public function genes_listing_submitter_filter_returns_correct_results_with_multiple_submitters()
    {
        // Skip this test when using SQLite (uses REGEXP_SUBSTR for ordering)
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('This test requires MySQL for REGEXP_SUBSTR ordering');
        }

        // Create three submitters
        $submitter1 = Submitter::factory()->create(['ident' => 'GENCC_MULTI_001']);
        $submitter2 = Submitter::factory()->create(['ident' => 'GENCC_MULTI_002']);
        $submitter3 = Submitter::factory()->create(['ident' => 'GENCC_MULTI_003']);

        // Create three genes
        $geneA = Gene::factory()->create(['symbol' => 'MULTIGENE_A', 'title' => 'MULTIGENE_A']);
        $geneB = Gene::factory()->create(['symbol' => 'MULTIGENE_B', 'title' => 'MULTIGENE_B']);
        $geneC = Gene::factory()->create(['symbol' => 'MULTIGENE_C', 'title' => 'MULTIGENE_C']);

        $disease = Disease::factory()->create();
        $classification = Classification::factory()->definitive()->create();
        $inheritance = Inheritance::factory()->create();

        // Gene A: submitter 1
        Submission::factory()->create([
            'gene_id' => $geneA->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter1->id,
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        // Gene B: submitter 2
        Submission::factory()->create([
            'gene_id' => $geneB->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter2->id,
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        // Gene C: submitter 3
        Submission::factory()->create([
            'gene_id' => $geneC->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter3->id,
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
            'status' => 'published',
        ]);

        // Filter by submitters 1 and 2 only
        $component = Livewire::test(Listing::class)
            ->set('curations_from_submitters', [$submitter1->ident, $submitter2->ident]);

        // Should show genes A and B
        $component->assertSee('MULTIGENE_A');
        $component->assertSee('MULTIGENE_B');
        // Should NOT show gene C (only has submission from submitter3)
        $component->assertDontSee('MULTIGENE_C');
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
            'inheritance_id' => $inheritance->id,
        ]);
    }
}
