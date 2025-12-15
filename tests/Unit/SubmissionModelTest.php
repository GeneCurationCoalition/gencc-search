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

class SubmissionModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function submission_can_be_created_with_factory()
    {
        $submission = Submission::factory()->create();

        $this->assertDatabaseHas('submissions', ['id' => $submission->id]);
    }

    /** @test */
    public function submission_has_uuid_scope()
    {
        $submission = Submission::factory()->create(['uuid' => 'test-uuid-123']);

        $found = Submission::uuid('test-uuid-123')->first();

        $this->assertNotNull($found);
        $this->assertEquals($submission->id, $found->id);
    }

    /** @test */
    public function submission_belongs_to_gene()
    {
        $gene = Gene::factory()->create(['title' => 'BRCA1']);
        $submission = Submission::factory()->create(['gene_id' => $gene->id]);

        $this->assertEquals('BRCA1', $submission->gene->title);
    }

    /** @test */
    public function submission_belongs_to_disease()
    {
        $disease = Disease::factory()->create(['title' => 'Test Disease']);
        $submission = Submission::factory()->create(['disease_id' => $disease->id]);

        $this->assertEquals('Test Disease', $submission->disease->title);
    }

    /** @test */
    public function submission_belongs_to_classification()
    {
        $classification = Classification::factory()->definitive()->create();
        $submission = Submission::factory()->create(['classification_id' => $classification->id]);

        $this->assertEquals('Definitive', $submission->classification->title);
    }

    /** @test */
    public function submission_belongs_to_submitter()
    {
        $submitter = Submitter::factory()->create(['title' => 'Test Lab']);
        $submission = Submission::factory()->create(['submitter_id' => $submitter->id]);

        $this->assertEquals('Test Lab', $submission->submitter->title);
    }

    /** @test */
    public function submission_belongs_to_inheritance()
    {
        $inheritance = Inheritance::factory()->autosomalDominant()->create();
        $submission = Submission::factory()->create(['moi_id' => $inheritance->id]);

        $this->assertEquals('Autosomal dominant', $submission->inheritance->title);
    }

    /** @test */
    public function submission_can_be_current()
    {
        $submission = Submission::factory()->create(['is_current' => true]);

        $this->assertTrue($submission->is_current);
    }

    /** @test */
    public function submission_can_be_non_current()
    {
        $submission = Submission::factory()->create(['is_current' => false]);

        $this->assertFalse($submission->is_current);
    }

    /** @test */
    public function submission_factory_creates_all_related_models()
    {
        $submission = Submission::factory()->create();

        $this->assertNotNull($submission->gene);
        $this->assertNotNull($submission->disease);
        $this->assertNotNull($submission->classification);
        $this->assertNotNull($submission->submitter);
        $this->assertNotNull($submission->inheritance);
    }

    /** @test */
    public function submission_can_have_disease_original()
    {
        $originalDisease = Disease::factory()->create(['title' => 'Original Disease']);
        $mappedDisease = Disease::factory()->create(['title' => 'Mapped Disease']);

        $submission = Submission::factory()->create([
            'disease_id' => $mappedDisease->id,
            'disease_original_id' => $originalDisease->id,
        ]);

        $this->assertEquals('Mapped Disease', $submission->disease->title);
        $this->assertEquals('Original Disease', $submission->disease_original->title);
    }

    /** @test */
    public function submission_has_submitted_as_fields()
    {
        $submission = Submission::factory()->create([
            'submitted_as_date' => '2024-01-15',
            'submitted_as_pmids' => '12345678',
            'submitted_as_notes' => 'Test notes',
        ]);

        $this->assertEquals('2024-01-15', $submission->submitted_as_date);
        $this->assertEquals('12345678', $submission->submitted_as_pmids);
        $this->assertEquals('Test notes', $submission->submitted_as_notes);
    }
}
