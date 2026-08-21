<?php

namespace Tests\Feature;

use App\Gene;
use App\Disease;
use App\Classification;
use App\Submitter;
use App\Submission;
use App\Inheritance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the by-gene classification chart on the statistics page (#210).
 *
 * Each gene is counted once, in the bucket for its strongest assertion. The four
 * worked examples from the issue are asserted directly.
 */
class GenesByClassificationChartTest extends TestCase
{
    use RefreshDatabase;

    /** Deliberately unrelated IDs prove semantics come from CURIEs. */
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

    /** @test */
    public function the_statistics_page_shows_the_by_gene_chart()
    {
        $response = $this->get('/statistics');

        $response->assertStatus(200);
        $response->assertSee('Classifications Visualized by Gene');
        // The submission-based chart stays; the issue asked for an additional view.
        $response->assertSee('Classifications Visualized');
    }

    /**
     * @test
     *
     * Gene A: 1 Definitive, 1 Strong, 1 Moderate — counted once, under Definitive.
     */
    public function a_gene_is_counted_once_under_its_strongest_classification()
    {
        $gene = $this->gene('GENEA');
        $this->submission($gene, 'GENCC:100001');
        $this->submission($gene, 'GENCC:100002');
        $this->submission($gene, 'GENCC:100003');

        $counts = $this->chartCounts();

        $this->assertSame(1, $counts['Definitive']);
        $this->assertSame(0, $counts['Strong']);
        $this->assertSame(0, $counts['Moderate']);
    }

    /**
     * @test
     *
     * Gene B: 3 Limited — counted once, under Limited.
     */
    public function repeated_assertions_of_one_classification_count_once()
    {
        $gene = $this->gene('GENEB');
        $this->submission($gene, 'GENCC:100004');
        $this->submission($gene, 'GENCC:100004');
        $this->submission($gene, 'GENCC:100004');

        $this->assertSame(1, $this->chartCounts()['Limited']);
    }

    /**
     * @test
     *
     * Supportive ranks above Limited.
     */
    public function supportive_outranks_limited()
    {
        $gene = $this->gene('GENEC');
        $this->submission($gene, 'GENCC:100004');
        $this->submission($gene, 'GENCC:100009');

        $counts = $this->chartCounts();

        $this->assertSame(0, $counts['Limited']);
        $this->assertSame(1, $counts['Supportive']);
    }

    /** @test */
    public function moderate_still_outranks_supportive()
    {
        $gene = $this->gene('GENED');
        $this->submission($gene, 'GENCC:100009');
        $this->submission($gene, 'GENCC:100003');

        $counts = $this->chartCounts();
        $this->assertSame(1, $counts['Moderate']);
        $this->assertSame(0, $counts['Supportive']);
    }

    /** @test */
    public function genes_are_spread_across_buckets_independently()
    {
        $definitive = $this->gene('GENE1');
        $this->submission($definitive, 'GENCC:100001');
        $this->submission($definitive, 'GENCC:100004');

        $limited = $this->gene('GENE2');
        $this->submission($limited, 'GENCC:100004');

        $supportive = $this->gene('GENE3');
        $this->submission($supportive, 'GENCC:100009');

        $counts = $this->chartCounts();

        $this->assertSame(1, $counts['Definitive']);
        $this->assertSame(1, $counts['Limited']);
        $this->assertSame(1, $counts['Supportive']);
        $this->assertSame(0, $counts['Strong']);
    }

    /**
     * @test
     *
     * Only publicly visible submissions count, matching the submission chart
     * above it.
     */
    public function unpublished_and_superseded_submissions_are_excluded()
    {
        $gene = $this->gene('GENEX');
        $this->submission($gene, 'GENCC:100001', ['status' => Submission::STATUS_UNPUBLISHED]);
        $this->submission($gene, 'GENCC:100004', ['is_live' => false]);
        $this->submission($gene, 'GENCC:100003');

        $counts = $this->chartCounts();

        $this->assertSame(0, $counts['Definitive']);
        $this->assertSame(0, $counts['Limited']);
        $this->assertSame(1, $counts['Moderate']);
    }

    /** @test */
    public function chart_order_places_animal_model_before_refuted_evidence()
    {
        $titles = collect($this->get('/statistics')->viewData('genesByClassification'))
            ->pluck('classification.title')
            ->values()
            ->all();

        $this->assertLessThan(
            array_search('Refuted Evidence', $titles, true),
            array_search('Animal Model Only', $titles, true)
        );
    }

    /** @test */
    public function unknown_classifications_do_not_change_a_known_strongest_bucket()
    {
        $gene = $this->gene('GENEUNKNOWN');
        $this->submission($gene, 'GENCC:100004');
        $unknown = Classification::factory()->create(['curie' => 'GENCC:199999', 'name' => 'Future term']);

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => Disease::factory()->create()->id,
            'classification_id' => $unknown->id,
            'submitter_id' => Submitter::factory()->create()->id,
            'inheritance_id' => Inheritance::factory()->create()->id,
        ]);

        $this->assertSame(1, $this->chartCounts()['Limited']);
    }

    /**
     * Pull the by-gene counts out of the rendered view, keyed by term title, so
     * the assertions read like the examples in the issue.
     */
    private function chartCounts(): array
    {
        $rows = $this->get('/statistics')->viewData('genesByClassification');

        $counts = [];

        foreach ($rows as $row) {
            $counts[$row['classification']->title] = $row['genes_count'];
        }

        return $counts;
    }

    private function gene(string $symbol): Gene
    {
        return Gene::factory()->create(['symbol' => $symbol, 'title' => $symbol]);
    }

    /**
     * Create a live, published submission using a stable classification CURIE.
     */
    private function submission(Gene $gene, string $classificationCurie, array $overrides = []): Submission
    {
        return Submission::factory()->create(array_merge([
            'gene_id' => $gene->id,
            'disease_id' => Disease::factory()->create()->id,
            'classification_id' => self::IDS[$classificationCurie],
            'submitter_id' => Submitter::factory()->create()->id,
            'inheritance_id' => Inheritance::factory()->create()->id,
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ], $overrides));
    }
}
