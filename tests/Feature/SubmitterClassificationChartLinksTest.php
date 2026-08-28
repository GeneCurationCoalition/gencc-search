<?php

namespace Tests\Feature;

use App\Classification;
use App\Disease;
use App\Gene;
use App\Http\Livewire\Genes\Listing;
use App\Inheritance;
use App\Submission;
use App\Submitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ShimsSqliteFunctions;
use Tests\TestCase;

class SubmitterClassificationChartLinksTest extends TestCase
{
    use RefreshDatabase;
    use ShimsSqliteFunctions;

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

    /** @test */
    public function every_submitter_chart_anchor_uses_and_applies_the_canonical_single_classification_query()
    {
        $this->shimRegexpSubstrForSqlite();

        $counts = [];
        $submitter = Submitter::factory()->create([
            'curie' => 'GENCC:000777',
            'counts' => [],
        ]);

        foreach (Classification::VOCABULARY as $curie => $metadata) {
            Classification::factory()->create([
                'id' => self::IDS[$curie],
                'curie' => $curie,
                'name' => $metadata['title'],
                'order' => 100 - $metadata['priority'],
            ]);

            // Leave one term at zero so the zero-count link is covered too.
            if ($curie === 'GENCC:100008') {
                continue;
            }

            $symbol = 'GENE' . self::IDS[$curie];
            $gene = Gene::factory()->create(['symbol' => $symbol]);
            Submission::factory()->create([
                'gene_id' => $gene->id,
                'disease_id' => Disease::factory()->create()->id,
                'classification_id' => self::IDS[$curie],
                'submitter_id' => $submitter->id,
                'inheritance_id' => Inheritance::factory()->create()->id,
                'is_live' => true,
                'status' => Submission::STATUS_PUBLISHED,
            ]);
            $counts[$metadata['title']] = ['count' => 1];
        }

        $submitter->update([
            'counts' => [
                'total' => 8,
                'by_classification' => $counts,
            ],
        ]);

        $html = $this->get(route('member-show', $submitter->curie))->getContent();
        preg_match_all('/href="[^"]*\/genes\?([^"]*)"/', $html, $matches);
        $queries = array_map(fn ($query) => html_entity_decode($query, ENT_QUOTES), $matches[1]);

        $this->assertCount(26, $queries);
        $this->assertCount(9, array_unique($queries));

        foreach (Classification::VOCABULARY as $curie => $metadata) {
            $query = Classification::curie($curie)->firstOrFail()->only_filter_query;
            $this->assertContains($query, $queries);
            $this->assertStringNotContainsString('curations_', $query);

            parse_str($query, $params);
            $symbols = collect(Livewire::withQueryParams($params)->test(Listing::class)
                ->viewData('genes')->items())
                ->pluck('symbol')
                ->all();

            $expected = $curie === 'GENCC:100008' ? [] : ['GENE' . self::IDS[$curie]];
            $this->assertSame($expected, $symbols, "Unexpected genes for {$metadata['title']} link.");
        }
    }
}
