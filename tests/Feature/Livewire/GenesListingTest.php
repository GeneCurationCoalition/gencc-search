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

        // After render, filters should be reset to 1
        $this->assertEquals(1, $component->get('curations_definitive'));
        $this->assertEquals(1, $component->get('curations_strong'));
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
     * @test
     * @dataProvider paddedTitleProvider
     *
     * Regression for #207, covering the gene-symbol filter on /genes.
     *
     * Two entry points reach the same LIKE pattern and both are asserted here:
     * the Livewire-bound filter box (`set('title', ...)`) and the `?title=`
     * query parameter that `mount()` copies onto the component. The `?title=`
     * form is the one the search box in gene-headline.blade.php builds, and
     * while that Blade template now trims client-side, a bookmarked, shared, or
     * hand-edited URL skips the JavaScript entirely — server-side
     * normalization in render() is the only thing left protecting it.
     */
    public function genes_listing_ignores_surrounding_whitespace_in_title($pad)
    {
        $this->shimRegexpSubstrForSqlite();

        $this->createGeneWithSubmission('GJB2');
        $this->createGeneWithSubmission('BRCA1');

        // Entry point 1: term typed or pasted into the filter box.
        $fromFilterBox = Livewire::test(Listing::class)
            ->set('title', $pad . 'GJB2' . $pad);

        $this->assertCount(1, $fromFilterBox->viewData('genes'));
        $fromFilterBox->assertSee('GJB2')->assertDontSee('BRCA1');

        // Entry point 2: same term arriving as ?title=, which mount() reads.
        $fromUrl = Livewire::withQueryParams(['title' => $pad . 'GJB2' . $pad])
            ->test(Listing::class);

        $this->assertCount(1, $fromUrl->viewData('genes'));
        $fromUrl->assertSee('GJB2')->assertDontSee('BRCA1');
    }

    public function paddedTitleProvider(): array
    {
        return [
            'space'        => [' '],
            'tab'          => ["\t"],
            'newline'      => ["\n"],
            'non-breaking' => ["\u{00A0}"],
        ];
    }

    /**
     * @test
     *
     * Normalizing the term must not widen the match: a padded term that does
     * not exist should still return nothing, rather than collapsing to an
     * empty pattern that matches every gene.
     */
    public function genes_listing_still_excludes_non_matching_title()
    {
        $this->shimRegexpSubstrForSqlite();

        $this->createGeneWithSubmission('GJB2');

        $component = Livewire::test(Listing::class)
            ->set('title', ' NOSUCHGENE ');

        $this->assertCount(0, $component->viewData('genes'));
    }

    /**
     * @test
     *
     * A whitespace-only term normalizes to '', which builds the pattern '%%'
     * and is intended to behave as "no filter" rather than "no results".
     */
    public function genes_listing_treats_whitespace_only_title_as_no_filter()
    {
        $this->shimRegexpSubstrForSqlite();

        $this->createGeneWithSubmission('GJB2');
        $this->createGeneWithSubmission('BRCA1');

        $component = Livewire::test(Listing::class)
            ->set('title', "  \t ");

        $this->assertCount(2, $component->viewData('genes'));
    }

    /**
     * Create a gene with a known symbol plus one submission, so it survives the
     * component's whereHas('submissions') filter.
     */
    private function createGeneWithSubmission(string $symbol): Gene
    {
        $gene = Gene::factory()->create(['symbol' => $symbol, 'title' => $symbol]);

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => Disease::factory()->create()->id,
            'classification_id' => Classification::factory()->create()->id,
            'submitter_id' => Submitter::factory()->create()->id,
            'inheritance_id' => Inheritance::factory()->create()->id,
        ]);

        return $gene;
    }

    /**
     * render() uses REGEXP_SUBSTR in one place only: the orderByRaw that
     * natural-sorts gene symbols, so GJB2 precedes GJB10. MySQL has that
     * function and SQLite does not, which is why the rendering tests above skip
     * themselves on SQLite — but CI runs SQLite only (.github/workflows/
     * tests.yaml), so skipping means never running. Register a PHP equivalent
     * so the query can execute; the assertions here are about which rows come
     * back, not what order they come back in.
     *
     * Ports the MySQL signature REGEXP_SUBSTR(subject, pattern[, position[,
     * occurrence]]) — 1-indexed character position (hence mb_substr), NULL when
     * the Nth occurrence is absent. The 5th match_type argument is unused by
     * this query and not implemented.
     *
     * This alone does not reproduce production row order, since ORDER BY still
     * collates differently on the two engines. A test that asserts on ordering
     * needs further SQLite patching to match MySQL, or should be restricted to
     * MySQL and skipped here like the tests above.
     */
    private function shimRegexpSubstrForSqlite(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::connection()->getPdo()->sqliteCreateFunction(
            'REGEXP_SUBSTR',
            function ($subject, $pattern, $position = 1, $occurrence = 1) {
                if ($subject === null || $pattern === null) {
                    return null;
                }

                $offset = max(0, ((int) $position) - 1);
                $haystack = mb_substr((string) $subject, $offset);

                if (!preg_match_all('/' . $pattern . '/u', $haystack, $matches)) {
                    return null;
                }

                return $matches[0][((int) $occurrence) - 1] ?? null;
            }
        );
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
