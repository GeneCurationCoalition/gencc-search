<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Gene;
use App\Disease;
use App\Classification;
use App\Submitter;
use App\Submission;
use App\Inheritance;

class ClassificationModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function classification_can_be_created_with_factory()
    {
        $classification = Classification::factory()->create();

        $this->assertDatabaseHas('classifications', ['id' => $classification->id]);
    }

    /** @test */
    public function classification_has_curie_scope()
    {
        $classification = Classification::factory()->create(['curie' => 'GENCC:0000001']);

        $found = Classification::curie('GENCC:0000001')->first();

        $this->assertNotNull($found);
        $this->assertEquals($classification->id, $found->id);
    }

    /** @test */
    public function classification_title_accessor_returns_name()
    {
        // Note: gencc-sub uses 'name' column, accessor maps title->name
        $classification = Classification::factory()->create(['name' => 'Test Classification']);

        $this->assertEquals('Test Classification', $classification->title);
    }

    /** @test */
    public function classification_has_slug_scope()
    {
        $classification = Classification::factory()->definitive()->create();

        $found = Classification::slug('definitive')->first();

        $this->assertNotNull($found);
        $this->assertEquals($classification->id, $found->id);
    }

    /** @test */
    public function classification_has_submissions_relationship()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
        ]);

        $this->assertCount(1, $classification->submissions);
    }

    /** @test */
    public function classification_submissions_only_returns_current()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        // Live submission (visible)
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
            'is_live' => true,
        ]);

        // Non-live submission (historical or unpublished)
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'inheritance_id' => $inheritance->id,
            'is_live' => false,
        ]);

        $this->assertCount(1, $classification->submissions);
    }

    /** @test */
    public function classification_definitive_factory_state_works()
    {
        $classification = Classification::factory()->definitive()->create();

        $this->assertEquals('Definitive', $classification->title);
        $this->assertEquals('DEF', $classification->abbreviation);
        $this->assertEquals('definitive', $classification->slug);
        $this->assertEquals(10, $classification->order);
    }

    /** @test */
    public function classification_strong_factory_state_works()
    {
        $classification = Classification::factory()->strong()->create();

        $this->assertEquals('Strong', $classification->title);
        $this->assertEquals('STR', $classification->abbreviation);
        $this->assertEquals('strong', $classification->slug);
        $this->assertEquals(20, $classification->order);
    }

    /** @test */
    public function classification_has_display_properties()
    {
        // Note: gencc-sub uses 'name' column, accessor maps title->name
        $classification = Classification::factory()->create([
            'name' => 'Definitive',
            'abbreviation' => 'DEF',
            'hex_color' => '#276749',
            'curie' => 'GENCC:100001',
        ]);

        $this->assertEquals('Definitive', $classification->title);
        $this->assertEquals('DEF', $classification->abbreviation);
        $this->assertEquals('#276749', $classification->hex_color);
        $this->assertEquals('gencc-definitive', $classification->css_class);
    }

    /** @test */
    public function known_classification_metadata_is_derived_from_its_curie_not_its_database_id()
    {
        $classification = Classification::factory()->create([
            'id' => 814,
            'curie' => 'GENCC:100009',
            'slug' => null,
            'css_class' => null,
            'href' => null,
        ]);

        $this->assertSame('supportive', $classification->slug);
        $this->assertSame('gencc-supportive', $classification->css_class);
        $this->assertSame('curations_supportive', $classification->href);
        $this->assertSame('supportive', $classification->filter_param);

        parse_str($classification->only_filter_query, $params);
        $this->assertSame('1', $params['supportive']);
        $this->assertSame('0', $params['limited']);
        $this->assertCount(9, $params);
    }

    /** @test */
    public function conflict_sides_are_explicitly_mapped_by_curie()
    {
        foreach (['GENCC:100001', 'GENCC:100002', 'GENCC:100003'] as $curie) {
            $this->assertSame('strong', Classification::conflictSide($curie));
        }

        foreach (['GENCC:100004', 'GENCC:100005', 'GENCC:100006', 'GENCC:100008'] as $curie) {
            $this->assertSame('other', Classification::conflictSide($curie));
        }

        foreach (['GENCC:100009', 'GENCC:100007', 'GENCC:199999'] as $curie) {
            $this->assertNull(Classification::conflictSide($curie));
        }
    }

    /** @test */
    public function statistics_bar_width_is_scaled_but_always_bounded()
    {
        $classification = new Classification();

        $this->assertSame(0, $classification->displayStatChartBarPercent(0, 10));
        $this->assertSame(35.0, $classification->displayStatChartBarPercent(100, 25));
        $this->assertSame(100, $classification->displayStatChartBarPercent(140, 100));
        $this->assertSame(100, $classification->displayStatChartBarPercent(100, 80));
    }

    /** @test */
    public function classification_order_determines_sort_order()
    {
        // Note: gencc-sub uses 'name' column, accessor maps title->name
        $definitive = Classification::factory()->create(['order' => 1, 'name' => 'Definitive']);
        $strong = Classification::factory()->create(['order' => 2, 'name' => 'Strong']);
        $moderate = Classification::factory()->create(['order' => 3, 'name' => 'Moderate']);

        $ordered = Classification::orderBy('order')->get();

        $this->assertEquals('Definitive', $ordered[0]->title);
        $this->assertEquals('Strong', $ordered[1]->title);
        $this->assertEquals('Moderate', $ordered[2]->title);
    }
}
