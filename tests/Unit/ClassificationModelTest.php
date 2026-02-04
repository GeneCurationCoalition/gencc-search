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
    public function classification_title_accessor_returns_title()
    {
        // Note: gencc-sub uses 'title' column directly (not 'name')
        $classification = Classification::factory()->create(['title' => 'Test Classification']);

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
            'moi_id' => $inheritance->id,
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
            'moi_id' => $inheritance->id,
            'is_live' => true,
        ]);

        // Non-live submission (historical or unpublished)
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
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
        $this->assertEquals(1, $classification->order);
    }

    /** @test */
    public function classification_strong_factory_state_works()
    {
        $classification = Classification::factory()->strong()->create();

        $this->assertEquals('Strong', $classification->title);
        $this->assertEquals('STR', $classification->abbreviation);
        $this->assertEquals('strong', $classification->slug);
        $this->assertEquals(2, $classification->order);
    }

    /** @test */
    public function classification_has_display_properties()
    {
        // Note: gencc-sub uses 'title' column directly
        $classification = Classification::factory()->create([
            'title' => 'Definitive',
            'abbreviation' => 'DEF',
            'hex_color' => '#276749',
            'css_class' => 'classification-definitive',
        ]);

        $this->assertEquals('Definitive', $classification->title);
        $this->assertEquals('DEF', $classification->abbreviation);
        $this->assertEquals('#276749', $classification->hex_color);
        $this->assertEquals('classification-definitive', $classification->css_class);
    }

    /** @test */
    public function classification_order_determines_sort_order()
    {
        // Note: gencc-sub uses 'title' column directly
        $definitive = Classification::factory()->create(['order' => 1, 'title' => 'Definitive']);
        $strong = Classification::factory()->create(['order' => 2, 'title' => 'Strong']);
        $moderate = Classification::factory()->create(['order' => 3, 'title' => 'Moderate']);

        $ordered = Classification::orderBy('order')->get();

        $this->assertEquals('Definitive', $ordered[0]->title);
        $this->assertEquals('Strong', $ordered[1]->title);
        $this->assertEquals('Moderate', $ordered[2]->title);
    }
}
