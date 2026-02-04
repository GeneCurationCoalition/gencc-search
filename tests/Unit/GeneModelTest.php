<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Gene;
use App\Submission;
use App\Disease;
use App\Classification;
use App\Submitter;
use App\Inheritance;
use Illuminate\Support\Facades\DB;

class GeneModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function gene_can_be_created_with_factory()
    {
        $gene = Gene::factory()->create();

        $this->assertDatabaseHas('genes', ['id' => $gene->id]);
    }

    /** @test */
    public function gene_has_curie_scope()
    {
        $gene = Gene::factory()->create(['curie' => 'HGNC:12345']);

        $found = Gene::curie('HGNC:12345')->first();

        $this->assertNotNull($found);
        $this->assertEquals($gene->id, $found->id);
    }

    /** @test */
    public function gene_has_ident_scope()
    {
        $gene = Gene::factory()->create(['ident' => 'test-ident-123']);

        $found = Gene::ident('test-ident-123')->first();

        $this->assertNotNull($found);
        $this->assertEquals($gene->id, $found->id);
    }

    /** @test */
    public function gene_uuid_accessor_returns_ident()
    {
        $gene = Gene::factory()->create(['ident' => 'test-ident-456']);

        $this->assertEquals('test-ident-456', $gene->uuid);
    }

    /** @test */
    public function gene_has_symbol_scope()
    {
        $gene = Gene::factory()->create(['title' => 'BRCA1']);

        $found = Gene::symbol('BRCA1')->first();

        $this->assertNotNull($found);
        $this->assertEquals($gene->id, $found->id);
    }

    /** @test */
    public function gene_has_hgnc_scope()
    {
        $gene = Gene::factory()->create(['hgnc_id' => 'HGNC:1100']);

        $found = Gene::hgnc('HGNC:1100')->first();

        $this->assertNotNull($found);
        $this->assertEquals($gene->id, $found->id);
    }

    /** @test */
    public function gene_has_name_scope()
    {
        $gene = Gene::factory()->create(['name' => 'breast cancer 1']);

        $found = Gene::name('breast cancer 1')->first();

        $this->assertNotNull($found);
        $this->assertEquals($gene->id, $found->id);
    }

    /** @test */
    public function gene_has_submissions_relationship()
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

        $this->assertCount(1, $gene->submissions);
    }

    /** @test */
    public function gene_submissions_only_returns_current()
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

        $this->assertCount(1, $gene->submissions);
    }

    /** @test */
    public function gene_alias_symbol_accessor_returns_alias_symbols()
    {
        // gencc-sub uses 'alias_symbol' column (not alias_symbols)
        $gene = Gene::factory()->create(['alias_symbol' => 'ABC,DEF']);

        $this->assertEquals(['ABC', 'DEF'], $gene->alias_symbol);
    }

    /** @test */
    public function gene_alias_symbol_accessor_returns_empty_array_when_null()
    {
        $gene = Gene::factory()->create(['alias_symbol' => null]);

        $this->assertEquals([], $gene->alias_symbol);
    }

    /** @test */
    public function gene_prev_symbol_accessor_returns_previous_symbols()
    {
        // gencc-sub uses 'prev_symbol' column (not previous_symbols)
        $gene = Gene::factory()->create(['prev_symbol' => 'OLD1,OLD2']);

        $this->assertEquals(['OLD1', 'OLD2'], $gene->prev_symbol);
    }

    /** @test */
    public function gene_prev_symbol_accessor_returns_empty_array_when_null()
    {
        $gene = Gene::factory()->create(['prev_symbol' => null]);

        $this->assertEquals([], $gene->prev_symbol);
    }

    /** @test */
    public function gene_curations_accessors_read_from_individual_columns()
    {
        // gencc-sub uses individual columns (not JSON counts)
        $gene = Gene::factory()->create([
            'curations_definitive' => 5,
            'curations_strong' => 3,
            'curations_moderate' => 2,
            'curations_limited' => 1,
            'curations_disputed' => 0,
            'curations_refuted' => 0,
        ]);

        $this->assertEquals(5, $gene->curations_definitive);
        $this->assertEquals(3, $gene->curations_strong);
        $this->assertEquals(2, $gene->curations_moderate);
        $this->assertEquals(1, $gene->curations_limited);
        $this->assertEquals(0, $gene->curations_disputed);
        $this->assertEquals(0, $gene->curations_refuted);
    }

    /** @test */
    public function gene_has_required_attributes()
    {
        $gene = Gene::factory()->create([
            'curie' => 'HGNC:99999',
            'title' => 'TEST',
            'name' => 'Test Gene',
        ]);

        $this->assertEquals('HGNC:99999', $gene->curie);
        $this->assertEquals('TEST', $gene->title);
        $this->assertEquals('Test Gene', $gene->name);
    }
}
