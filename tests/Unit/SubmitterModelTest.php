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
    public function submitter_has_uuid_scope()
    {
        $submitter = Submitter::factory()->create(['uuid' => 'test-uuid-123']);

        $found = Submitter::uuid('test-uuid-123')->first();

        $this->assertNotNull($found);
        $this->assertEquals($submitter->id, $found->id);
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
            'status' => 1,
        ]);

        $this->assertCount(1, $submitter->submissions);
    }

    /** @test */
    public function submitter_submissions_only_returns_published()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        // Published submission
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'status' => 1,
        ]);

        // Unpublished submission
        Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'status' => 0,
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
        $submitter = Submitter::factory()->create(['member' => 1]);

        $this->assertEquals(1, $submitter->member);
    }

    /** @test */
    public function submitter_can_be_non_member()
    {
        $submitter = Submitter::factory()->nonMember()->create();

        $this->assertEquals(0, $submitter->member);
    }

    /** @test */
    public function submitter_has_required_fields()
    {
        $submitter = Submitter::factory()->create([
            'title' => 'Test Consortium',
            'website' => 'https://example.com',
            'text_descriptions' => 'Test description',
            'text_contact' => 'test@example.com',
        ]);

        $this->assertEquals('Test Consortium', $submitter->title);
        $this->assertEquals('https://example.com', $submitter->website);
        $this->assertEquals('Test description', $submitter->text_descriptions);
        $this->assertEquals('test@example.com', $submitter->text_contact);
    }

    /** @test */
    public function submitter_downloadable_flag_works()
    {
        $downloadable = Submitter::factory()->create(['downloadable' => 1]);
        $notDownloadable = Submitter::factory()->create(['downloadable' => 0]);

        $this->assertEquals(1, $downloadable->downloadable);
        $this->assertEquals(0, $notDownloadable->downloadable);
    }
}
