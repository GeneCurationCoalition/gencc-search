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
    public function gene_has_uuid_scope()
    {
        $gene = Gene::factory()->create(['uuid' => 'test-uuid-123']);

        $found = Gene::uuid('test-uuid-123')->first();

        $this->assertNotNull($found);
        $this->assertEquals($gene->id, $found->id);
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
    public function gene_has_ensembl_scope()
    {
        $gene = Gene::factory()->create(['ensembl_gene_id' => 'ENSG00000012048']);

        $found = Gene::ensembl('ENSG00000012048')->first();

        $this->assertNotNull($found);
        $this->assertEquals($gene->id, $found->id);
    }

    /** @test */
    public function gene_has_entrez_scope()
    {
        $gene = Gene::factory()->create(['entrez_id' => '672']);

        $found = Gene::entrez('672')->first();

        $this->assertNotNull($found);
        $this->assertEquals($gene->id, $found->id);
    }

    /**
     * @test
     * @group mysql-only
     */
    public function gene_has_omim_scope()
    {
        // Skip on SQLite - JSON contains not supported
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite does not support JSON contains operations');
        }

        $gene = Gene::factory()->create(['omim_id' => ['113705']]);
        $found = Gene::omim('113705')->first();

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
            'is_current' => true,
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

        // Current submission
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'is_current' => true,
        ]);

        // Non-current submission (superseded or unpublished)
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'is_current' => false,
        ]);

        $this->assertCount(1, $gene->submissions);
    }

    /** @test */
    public function gene_display_aliases_returns_message_when_empty()
    {
        $gene = Gene::factory()->create(['alias_symbol' => null]);

        $this->assertEquals('No aliases found', $gene->display_aliases);
    }

    /** @test */
    public function gene_display_previous_returns_message_when_empty()
    {
        $gene = Gene::factory()->create(['prev_symbol' => null]);

        $this->assertEquals('No previous names found', $gene->display_previous);
    }

    /** @test */
    public function gene_rosetta_resolves_by_name()
    {
        $gene = Gene::factory()->create(['name' => 'BRCA1']);

        $found = Gene::rosetta('BRCA1');

        $this->assertNotNull($found);
        $this->assertEquals($gene->id, $found->id);
    }

    /** @test */
    public function gene_rosetta_resolves_by_hgnc_prefix()
    {
        $gene = Gene::factory()->create(['hgnc_id' => 'HGNC:1100']);

        $found = Gene::rosetta('HGNC:1100');

        $this->assertNotNull($found);
        $this->assertEquals($gene->id, $found->id);
    }

    /** @test */
    public function gene_rosetta_resolves_by_entrez_prefix()
    {
        $gene = Gene::factory()->create(['entrez_id' => '672']);

        $found = Gene::rosetta('ENTREZ:672');

        $this->assertNotNull($found);
        $this->assertEquals($gene->id, $found->id);
    }

    /** @test */
    public function gene_rosetta_returns_null_for_empty_input()
    {
        $found = Gene::rosetta('');

        $this->assertNull($found);
    }

    /** @test */
    public function gene_search_list_returns_empty_for_invalid_type()
    {
        $result = Gene::searchList([
            'type' => 'invalid',
            'region' => 'chr17:43000000-43200000',
        ]);

        $this->assertEquals(0, $result->count);
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
