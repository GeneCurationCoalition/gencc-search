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
 * Covers the select-all / select-none controls on the genes listing (#203).
 *
 * The interesting part is not the buttons but the empty selection they make
 * reachable. render() previously could not distinguish "never set" from
 * "deliberately turned off" — a loose == 0 comparison saw '' and '0' alike — so
 * it reset an all-off selection back to all-on, and separately skipped the
 * whereIn clause when the enabled list came out empty, widening "match nothing"
 * into "match everything". Both are asserted here.
 */
class GenesListingSelectAllTest extends TestCase
{
    use RefreshDatabase;
    use ShimsSqliteFunctions;

    /** @test */
    public function select_all_classifications_turns_every_toggle_on()
    {
        $component = new Listing();

        $component->selectNoClassifications();
        $this->assertSame('0', $component->curations_definitive);
        $this->assertSame('0', $component->curations_noknown);

        $component->selectAllClassifications();
        $this->assertSame('1', $component->curations_definitive);
        $this->assertSame('1', $component->curations_noknown);
    }

    /** @test */
    public function selecting_all_classifications_resets_pagination()
    {
        $component = new Listing();

        $component->setPage(6);
        $component->selectAllClassifications();
        $this->assertSame(1, $component->page);

        $component->setPage(6);
        $component->selectNoClassifications();
        $this->assertSame(1, $component->page);
    }

    /**
     * @test
     *
     * A fresh load still defaults to everything on, which is the behaviour the
     * old all-off reset was really providing.
     */
    public function fresh_load_defaults_every_classification_on()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');

        $component = Livewire::test(Listing::class);

        $this->assertEquals(1, $component->get('curations_definitive'));
        $this->assertEquals(1, $component->get('curations_noknown'));
        $this->assertCount(1, $component->viewData('genes'));
    }

    /**
     * @test
     *
     * The regression the old reset hid: turning every classification off has to
     * stay off and show nothing, otherwise "select none" is a no-op and the user
     * cannot clear the boxes before picking the two they want.
     */
    public function select_no_classifications_sticks_and_shows_no_genes()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');

        $component = Livewire::test(Listing::class)
            ->call('selectNoClassifications');

        $this->assertEquals(0, $component->get('curations_definitive'));
        $this->assertEquals(0, $component->get('curations_noknown'));
        $this->assertCount(0, $component->viewData('genes'));
    }

    /** @test */
    public function select_all_classifications_shows_genes_again_after_selecting_none()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');

        $component = Livewire::test(Listing::class)
            ->call('selectNoClassifications')
            ->call('selectAllClassifications');

        $this->assertCount(1, $component->viewData('genes'));
    }

    /** @test */
    public function select_no_submitters_sticks_and_shows_no_genes()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');

        $component = Livewire::test(Listing::class)
            ->call('selectNoSubmitters');

        $this->assertSame([], $component->get('curations_from_submitters'));
        $this->assertTrue($component->get('filtering_by_submitter'));
        $this->assertCount(0, $component->viewData('genes'));
    }

    /** @test */
    public function select_all_submitters_reselects_everyone()
    {
        $this->shimRegexpSubstrForSqlite();
        $this->createGeneWithSubmission('GJB2');
        $this->createGeneWithSubmission('BRCA1');

        $component = Livewire::test(Listing::class)
            ->call('selectNoSubmitters')
            ->call('selectAllSubmitters');

        $this->assertCount(2, $component->get('curations_from_submitters'));
        $this->assertFalse($component->get('filtering_by_submitter'));
        $this->assertCount(2, $component->viewData('genes'));
    }

    /**
     * Create a gene with one submission so it survives the component's
     * whereHas('submissions') filter.
     *
     * The known classification is reused across calls because its CURIE, rather
     * than its database ID, carries the filter semantics.
     */
    private function createGeneWithSubmission(string $symbol): Gene
    {
        $gene = Gene::factory()->create(['symbol' => $symbol, 'title' => $symbol]);

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
}
