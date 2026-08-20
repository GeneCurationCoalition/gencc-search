<?php

namespace Tests\Feature;

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
 * Covers the statistics page chart links.
 *
 * Both charts used to link with the legacy '?curations_definitive=1' spelling,
 * which the genes listing does not bind to, so every bar landed on an unfiltered
 * listing. These tests assert the links actually filter, rather than only that
 * the right words are on the page.
 */
class StatisticsChartLinksTest extends TestCase
{
    use RefreshDatabase;
    use ShimsSqliteFunctions;

    /**
     * The nine real GenCC terms, keyed by the IDs production uses, for the same
     * reason as GenesByClassificationChartTest: the filter-param map is keyed by
     * ID and ClassificationFactory covers only six terms with random titles.
     */
    const TERMS = [
        1 => 'Definitive',
        2 => 'Strong',
        3 => 'Moderate',
        4 => 'Supportive',
        5 => 'Limited',
        6 => 'Disputed',
        7 => 'Refuted',
        8 => 'Animal Model Only',
        9 => 'No known disease relationship',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TERMS as $id => $title) {
            Classification::factory()->create([
                'id' => $id,
                'name' => $title,
                'title' => $title,
            ]);
        }
    }

    /**
     * @test
     *
     * The drift guard. Classification::FILTER_PARAMS only works because its
     * values are exactly the 'as' aliases the listing binds its toggles to; if
     * either side is renamed the links go quietly dead again.
     */
    public function every_filter_param_matches_the_alias_the_listing_binds()
    {
        $queryString = (new \ReflectionClass(Listing::class))
            ->getDefaultProperties()['queryString'];

        $aliases = [];

        foreach ($queryString as $property => $options) {
            if (isset($options['as'])) {
                $aliases[$property] = $options['as'];
            }
        }

        foreach (Classification::FILTER_PARAMS as $id => $param) {
            $this->assertContains(
                $param,
                $aliases,
                "Classification {$id} filters on '{$param}', which the listing does not bind."
            );
        }

        $this->assertCount(9, Classification::FILTER_PARAMS);
    }

    /**
     * @test
     *
     * '?definitive=1' on its own means "all nine on", so a link naming only the
     * term it points at would filter nothing. The other eight must be off.
     */
    public function the_only_filter_query_switches_the_other_eight_terms_off()
    {
        $query = Classification::find(1)->only_filter_query;

        parse_str($query, $params);

        $this->assertSame('1', $params['definitive']);
        $this->assertSame('0', $params['strong']);
        $this->assertSame('0', $params['supportive']);
        $this->assertSame('0', $params['noknown']);
        $this->assertCount(9, $params);
    }

    /** @test */
    public function no_chart_link_uses_the_legacy_curations_spelling()
    {
        $this->seedOneGenePerClassification();

        $html = $this->get('/statistics')->getContent();

        foreach ($this->genesLinkQueries($html) as $query) {
            $this->assertStringNotContainsString('curations_', $query);
        }
    }

    /**
     * @test
     *
     * Every link on either chart has to be a real single-classification filter,
     * which also covers the by-gene chart #210 added.
     */
    public function every_chart_link_is_a_single_classification_filter()
    {
        $this->seedOneGenePerClassification();

        $html = $this->get('/statistics')->getContent();

        $expected = Classification::all()
            ->map(function ($classification) {
                return $classification->only_filter_query;
            })
            ->all();

        $queries = $this->genesLinkQueries($html);

        $this->assertNotEmpty($queries, 'The statistics page linked to no filtered listing at all.');

        foreach ($queries as $query) {
            $this->assertContains($query, $expected);
        }

        // Both charts link every term, so all nine turn up rather than just the
        // one that happens to sort first.
        $this->assertCount(9, array_unique($queries));
    }

    /**
     * @test
     *
     * The point of the whole exercise: follow a link and land on a listing that
     * really is narrowed to that one term.
     */
    public function following_a_chart_link_lands_on_a_filtered_listing()
    {
        $this->shimRegexpSubstrForSqlite();

        $definitive = $this->geneWithSubmission('DEFGENE', 1);
        $limited = $this->geneWithSubmission('LIMGENE', 5);

        $html = $this->get('/statistics')->getContent();
        $queries = $this->genesLinkQueries($html);

        $definitiveQuery = Classification::find(1)->only_filter_query;
        $this->assertContains($definitiveQuery, $queries);

        parse_str($definitiveQuery, $params);

        $component = Livewire::withQueryParams($params)->test(Listing::class);

        $symbols = collect($component->viewData('genes')->items())
            ->pluck('symbol')
            ->all();

        $this->assertSame(['DEFGENE'], $symbols);
        $this->assertTrue($component->get('hasActiveFilters'));
    }

    /** @test */
    public function following_a_chart_link_for_a_weaker_term_excludes_the_stronger_genes()
    {
        $this->shimRegexpSubstrForSqlite();

        $this->geneWithSubmission('DEFGENE', 1);
        $this->geneWithSubmission('LIMGENE', 5);

        parse_str(Classification::find(5)->only_filter_query, $params);

        $component = Livewire::withQueryParams($params)->test(Listing::class);

        $symbols = collect($component->viewData('genes')->items())
            ->pluck('symbol')
            ->all();

        $this->assertSame(['LIMGENE'], $symbols);
    }

    /**
     * Every genes-listing link on the page that carries a query string, with the
     * HTML entity encoding Blade applies to '&' undone.
     */
    private function genesLinkQueries(string $html): array
    {
        preg_match_all('/href="[^"]*\/genes\?([^"]*)"/', $html, $matches);

        return array_map(function ($query) {
            return html_entity_decode($query, ENT_QUOTES);
        }, $matches[1]);
    }

    private function seedOneGenePerClassification(): void
    {
        foreach (array_keys(self::TERMS) as $id) {
            $this->geneWithSubmission('GENE' . $id, $id);
        }
    }

    private function geneWithSubmission(string $symbol, int $classificationId): Gene
    {
        $gene = Gene::factory()->create(['symbol' => $symbol, 'title' => $symbol]);

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => Disease::factory()->create()->id,
            'classification_id' => $classificationId,
            'submitter_id' => Submitter::factory()->create()->id,
            'inheritance_id' => Inheritance::factory()->create()->id,
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ]);

        return $gene;
    }
}
