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

class SubmitterModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function submitter_can_be_created_with_factory()
    {
        $submitter = Submitter::factory()->create();

        $this->assertDatabaseHas('submitters', ['id' => $submitter->id]);
    }

    /** @test */
    public function submitter_has_curie_scope()
    {
        $submitter = Submitter::factory()->create(['curie' => 'GENCC:000001']);

        $found = Submitter::curie('GENCC:000001')->first();

        $this->assertNotNull($found);
        $this->assertEquals($submitter->id, $found->id);
    }

    /** @test */
    public function submitter_has_ident_scope()
    {
        // Note: scopeIdent queries by 'ident' column (gencc-sub schema)
        $submitter = Submitter::factory()->create(['ident' => 'test-ident-123']);

        $found = Submitter::ident('test-ident-123')->first();

        $this->assertNotNull($found);
        $this->assertEquals($submitter->id, $found->id);
    }

    /** @test */
    public function submitter_uuid_accessor_returns_ident()
    {
        // Note: gencc-sub uses 'ident' column, accessor maps uuid->ident
        $submitter = Submitter::factory()->create(['ident' => 'test-uuid-456']);

        $this->assertEquals('test-uuid-456', $submitter->uuid);
    }

    /** @test */
    public function submitter_has_submissions_relationship()
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

        $this->assertCount(1, $submitter->submissions);
    }

    /** @test */
    public function submitter_submissions_only_returns_current()
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

        $this->assertCount(1, $submitter->submissions);
    }

    /** @test */
    public function submitter_can_be_active()
    {
        $submitter = Submitter::factory()->create(['status' => 1]);

        $this->assertEquals(1, $submitter->status);
    }

    /** @test */
    public function submitter_can_be_inactive()
    {
        $submitter = Submitter::factory()->inactive()->create();

        $this->assertEquals(0, $submitter->status);
    }

    /** @test */
    public function submitter_can_be_member()
    {
        $submitter = Submitter::factory()->create(['member' => true]);

        $this->assertTrue($submitter->member);
    }

    /** @test */
    public function submitter_can_be_non_member()
    {
        $submitter = Submitter::factory()->nonMember()->create();

        $this->assertFalse($submitter->member);
    }

    /** @test */
    public function submitter_has_required_fields()
    {
        // Note: gencc-sub uses 'name' and 'description' columns, accessor maps title->name
        $submitter = Submitter::factory()->create([
            'name' => 'Test Consortium',
            'website' => 'https://example.com',
            'description' => 'Test description',
        ]);

        $this->assertEquals('Test Consortium', $submitter->title);
        $this->assertEquals('https://example.com', $submitter->website);
        $this->assertEquals('Test description', $submitter->text_descriptions);
    }

    /** @test */
    public function submitter_title_accessor_returns_name()
    {
        // Note: gencc-sub uses 'name' column, accessor maps title->name
        $submitter = Submitter::factory()->create(['name' => 'Test Organization']);

        $this->assertEquals('Test Organization', $submitter->title);
    }

    /** @test */
    public function submitter_downloadable_flag_works()
    {
        $downloadable = Submitter::factory()->create(['downloadable' => true]);
        $notDownloadable = Submitter::factory()->create(['downloadable' => false]);

        $this->assertTrue($downloadable->downloadable);
        $this->assertFalse($notDownloadable->downloadable);
    }
}
