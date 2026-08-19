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

    /**
     * The nine real GenCC terms, keyed by the IDs production uses. The ranking and
     * the href/css_class maps are all keyed by ID, and ClassificationFactory
     * assigns a random title from a six-term subset, so these tests seed the terms
     * explicitly rather than trusting the factory.
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
        $this->submission($gene, 1);
        $this->submission($gene, 2);
        $this->submission($gene, 3);

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
        $this->submission($gene, 5);
        $this->submission($gene, 5);
        $this->submission($gene, 5);

        $this->assertSame(1, $this->chartCounts()['Limited']);
    }

    /**
     * @test
     *
     * Gene C: 1 Limited, 1 Supportive — counted under Limited, not Supportive.
     */
    public function supportive_loses_to_any_other_classification()
    {
        $gene = $this->gene('GENEC');
        $this->submission($gene, 5);
        $this->submission($gene, 4);

        $counts = $this->chartCounts();

        $this->assertSame(1, $counts['Limited']);
        $this->assertSame(0, $counts['Supportive']);
    }

    /**
     * @test
     *
     * Gene D: 1 Supportive only — counted under Supportive.
     */
    public function supportive_counts_when_it_is_the_only_assertion()
    {
        $gene = $this->gene('GENED');
        $this->submission($gene, 4);

        $this->assertSame(1, $this->chartCounts()['Supportive']);
    }

    /** @test */
    public function genes_are_spread_across_buckets_independently()
    {
        $definitive = $this->gene('GENE1');
        $this->submission($definitive, 1);
        $this->submission($definitive, 5);

        $limited = $this->gene('GENE2');
        $this->submission($limited, 5);

        $supportive = $this->gene('GENE3');
        $this->submission($supportive, 4);

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
        $this->submission($gene, 1, ['status' => Submission::STATUS_UNPUBLISHED]);
        $this->submission($gene, 5, ['is_live' => false]);
        $this->submission($gene, 3);

        $counts = $this->chartCounts();

        $this->assertSame(0, $counts['Definitive']);
        $this->assertSame(0, $counts['Limited']);
        $this->assertSame(1, $counts['Moderate']);
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
     * Create a live, published submission for $gene against a specific
     * classification ID, since the ranking is keyed by ID.
     */
    private function submission(Gene $gene, int $classificationId, array $overrides = []): Submission
    {
        return Submission::factory()->create(array_merge([
            'gene_id' => $gene->id,
            'disease_id' => Disease::factory()->create()->id,
            'classification_id' => $classificationId,
            'submitter_id' => Submitter::factory()->create()->id,
            'inheritance_id' => Inheritance::factory()->create()->id,
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ], $overrides));
    }
}
