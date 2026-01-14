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
    public function submission_can_be_published()
    {
        $submission = Submission::factory()->create([
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ]);

        $this->assertTrue($submission->is_live);
        $this->assertEquals(Submission::STATUS_PUBLISHED, $submission->status);
        $this->assertTrue($submission->isPublished());
    }

    /** @test */
    public function submission_can_be_unpublished_status()
    {
        $submission = Submission::factory()->create([
            'is_live' => true,
            'status' => Submission::STATUS_UNPUBLISHED,
        ]);

        $this->assertTrue($submission->is_live);
        $this->assertEquals(Submission::STATUS_UNPUBLISHED, $submission->status);
        $this->assertTrue($submission->isUnpublished());
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

    /** @test */
    public function submission_can_be_live()
    {
        $submission = Submission::factory()->create(['is_live' => true]);

        $this->assertTrue($submission->is_live);
    }

    /** @test */
    public function submission_can_be_not_live()
    {
        $submission = Submission::factory()->create(['is_live' => false]);

        $this->assertFalse($submission->is_live);
    }

    /** @test */
    public function submission_can_have_released_at()
    {
        $releaseDate = now();
        $submission = Submission::factory()->create(['released_at' => $releaseDate]);

        $this->assertNotNull($submission->released_at);
    }

    /** @test */
    public function submission_live_scope_only_returns_live_published_submissions()
    {
        // Create a live published submission (should be returned)
        Submission::factory()->create([
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ]);
        // Create a live unpublished submission (should NOT be returned)
        Submission::factory()->create([
            'is_live' => true,
            'status' => Submission::STATUS_UNPUBLISHED,
        ]);
        // Create a historical submission (should NOT be returned)
        Submission::factory()->create([
            'is_live' => false,
            'status' => Submission::STATUS_PUBLISHED,
        ]);

        $liveSubmissions = Submission::live()->get();

        $this->assertCount(1, $liveSubmissions);
        $this->assertTrue($liveSubmissions->first()->is_live);
        $this->assertEquals(Submission::STATUS_PUBLISHED, $liveSubmissions->first()->status);
    }

    /** @test */
    public function submission_most_recent_scope_returns_all_current_versions()
    {
        // Create a live published submission (is_live=true)
        Submission::factory()->create([
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ]);
        // Create a live unpublished submission (is_live=true)
        Submission::factory()->create([
            'is_live' => true,
            'status' => Submission::STATUS_UNPUBLISHED,
        ]);
        // Create a historical submission (is_live=false)
        Submission::factory()->create([
            'is_live' => false,
            'status' => Submission::STATUS_PUBLISHED,
        ]);

        // mostRecent() should return all is_live=true submissions (both published and unpublished)
        $mostRecentSubmissions = Submission::mostRecent()->get();

        $this->assertCount(2, $mostRecentSubmissions);
        $this->assertTrue($mostRecentSubmissions->every(fn ($s) => $s->is_live));
    }

    /** @test */
    public function submission_is_unpublished_returns_true_when_live_and_status_unpublished()
    {
        $submission = Submission::factory()->create([
            'is_live' => true,
            'status' => Submission::STATUS_UNPUBLISHED,
        ]);

        $this->assertTrue($submission->isUnpublished());
    }

    /** @test */
    public function submission_is_unpublished_returns_false_when_status_published()
    {
        $submission = Submission::factory()->create([
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ]);

        $this->assertFalse($submission->isUnpublished());
    }

    /** @test */
    public function submission_is_historical_returns_true_when_not_live()
    {
        $submission = Submission::factory()->create([
            'is_live' => false,
        ]);

        $this->assertTrue($submission->isHistorical());
    }

    /** @test */
    public function submission_is_historical_returns_false_when_live()
    {
        $submission = Submission::factory()->create([
            'is_live' => true,
        ]);

        $this->assertFalse($submission->isHistorical());
    }

    /** @test */
    public function submission_factory_unpublished_state_creates_correct_values()
    {
        $submission = Submission::factory()->unpublished()->create();

        $this->assertTrue($submission->is_live);      // is most recent version
        $this->assertEquals(Submission::STATUS_UNPUBLISHED, $submission->status);
        $this->assertNotNull($submission->released_at);
    }

    /** @test */
    public function submission_factory_historical_state_creates_correct_values()
    {
        $submission = Submission::factory()->historical()->create();

        $this->assertFalse($submission->is_live);  // not the most recent version
    }

    /** @test */
    public function submission_is_published_returns_true_when_live_and_status_published()
    {
        $submission = Submission::factory()->create([
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
        ]);

        $this->assertTrue($submission->isPublished());
    }

    /** @test */
    public function submission_is_published_returns_false_when_status_unpublished()
    {
        $submission = Submission::factory()->create([
            'is_live' => true,
            'status' => Submission::STATUS_UNPUBLISHED,
        ]);

        $this->assertFalse($submission->isPublished());
    }
}
