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
    const IDS = [
        'GENCC:100001' => 81,
        'GENCC:100002' => 12,
        'GENCC:100003' => 63,
        'GENCC:100009' => 4,
        'GENCC:100004' => 55,
        'GENCC:100005' => 26,
        'GENCC:100007' => 97,
        'GENCC:100006' => 38,
        'GENCC:100008' => 9,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (Classification::VOCABULARY as $curie => $metadata) {
            Classification::factory()->create([
                'id' => self::IDS[$curie],
                'curie' => $curie,
                'name' => $metadata['title'],
                'title' => $metadata['title'],
            ]);
        }
    }

    /**
     * @test
     *
     * The drift guard. Vocabulary query names must be exactly the aliases the
     * listing binds its toggles to; if
     * either side is renamed the links go quietly dead again.
     */
    public function every_filter_param_matches_the_alias_the_listing_binds()
    {
        $queryString = (new Listing())->getQueryString();

        $aliases = [];

        foreach ($queryString as $property => $options) {
            if (isset($options['as'])) {
                $aliases[$property] = $options['as'];
            }
        }

        foreach (Classification::filterParams() as $param) {
            $this->assertContains(
                $param,
                $aliases,
                "Classification filter '{$param}' is not bound by the listing."
            );
        }

        $this->assertCount(9, Classification::filterParams());
    }

    /**
     * @test
     *
     * '?definitive=1' on its own means "all nine on", so a link naming only the
     * term it points at would filter nothing. The other eight must be off.
     */
    public function the_only_filter_query_switches_the_other_eight_terms_off()
    {
        $query = Classification::curie('GENCC:100001')->firstOrFail()->only_filter_query;

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
     * Every submissions-chart link has to be a real single-classification filter.
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

        // The submissions chart links every term.
        $this->assertCount(9, array_unique($queries));
    }

    /** @test */
    public function submissions_chart_keeps_links_but_the_entire_by_gene_chart_has_none()
    {
        $this->seedOneGenePerClassification();
        $html = $this->get('/statistics')->getContent();

        [, $afterByGeneHeading] = explode('Classifications Visualized by Gene', $html, 2);
        [$byGeneChart] = explode('GenCC Submitters Stats', $afterByGeneHeading, 2);
        [$submissionsChart] = explode('Classifications Visualized by Gene', $html, 2);

        $this->assertStringContainsString('<a ', $submissionsChart);
        $this->assertStringNotContainsString('<a ', $byGeneChart);
    }

    /** @test */
    public function submissions_chart_uses_the_canonical_curie_order()
    {
        Classification::factory()->create(['curie' => 'GENCC:000000', 'name' => 'Not Classified']);
        Classification::factory()->create(['curie' => 'GENCC:199999', 'name' => 'Future Term']);

        $titles = $this->get('/statistics')->viewData('classifications')->pluck('title')->all();

        $this->assertSame(array_column(Classification::VOCABULARY, 'title'), $titles);
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

        $definitive = $this->geneWithSubmission('DEFGENE', 'GENCC:100001');
        $limited = $this->geneWithSubmission('LIMGENE', 'GENCC:100004');

        $html = $this->get('/statistics')->getContent();
        $queries = $this->genesLinkQueries($html);

        $definitiveQuery = Classification::curie('GENCC:100001')->firstOrFail()->only_filter_query;
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

        $this->geneWithSubmission('DEFGENE', 'GENCC:100001');
        $this->geneWithSubmission('LIMGENE', 'GENCC:100004');

        parse_str(Classification::curie('GENCC:100004')->firstOrFail()->only_filter_query, $params);

        $component = Livewire::withQueryParams($params)->test(Listing::class);

        $symbols = collect($component->viewData('genes')->items())
            ->pluck('symbol')
            ->all();

        $this->assertSame(['LIMGENE'], $symbols);
    }

    /** @test */
    public function both_statistics_charts_cap_dominant_bars_at_the_container_edge()
    {
        $this->geneWithSubmission('ONLYGENE', 'GENCC:100001');

        $html = $this->get('/statistics')->getContent();
        preg_match_all('/style="width:([0-9.]+)%"/', $html, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $width) {
            $this->assertGreaterThanOrEqual(0, (float) $width);
            $this->assertLessThanOrEqual(100, (float) $width);
        }

        $this->assertGreaterThanOrEqual(2, count(array_filter(
            $matches[1],
            fn ($width) => (float) $width === 100.0
        )));
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
        foreach (array_keys(Classification::VOCABULARY) as $curie) {
            $this->geneWithSubmission('GENE' . self::IDS[$curie], $curie);
        }
    }

    private function geneWithSubmission(string $symbol, string $classificationCurie): Gene
    {
        $gene = Gene::factory()->create(['symbol' => $symbol, 'title' => $symbol]);

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => Disease::factory()->create()->id,
            'classification_id' => self::IDS[$classificationCurie],
            'submitter_id' => Submitter::factory()->create()->id,
            'inheritance_id' => Inheritance::factory()->create()->id,
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ]);

        return $gene;
    }
}
