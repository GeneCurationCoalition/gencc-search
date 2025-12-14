<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Disease;
use App\Submission;
use App\Gene;
use App\Classification;
use App\Submitter;
use App\Inheritance;

class DiseaseModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function disease_can_be_created_with_factory()
    {
        $disease = Disease::factory()->create();

        $this->assertDatabaseHas('diseases', ['id' => $disease->id]);
    }

    /** @test */
    public function disease_has_curie_scope()
    {
        $disease = Disease::factory()->create(['curie' => 'MONDO:0000001']);

        $found = Disease::curie('MONDO:0000001')->first();

        $this->assertNotNull($found);
        $this->assertEquals($disease->id, $found->id);
    }

    /** @test */
    public function disease_has_uuid_scope()
    {
        $disease = Disease::factory()->create(['uuid' => 'MONDO_0000001']);

        $found = Disease::uuid('MONDO_0000001')->first();

        $this->assertNotNull($found);
        $this->assertEquals($disease->id, $found->id);
    }

    /** @test */
    public function disease_can_be_created_as_mondo_type()
    {
        $disease = Disease::factory()->create();

        $this->assertEquals('MONDO', $disease->type);
        $this->assertStringStartsWith('MONDO:', $disease->curie);
    }

    /** @test */
    public function disease_can_be_created_as_omim_type()
    {
        $disease = Disease::factory()->omim()->create();

        $this->assertEquals('OMIM', $disease->type);
        $this->assertStringStartsWith('OMIM:', $disease->curie);
    }

    /** @test */
    public function disease_can_be_created_as_orphanet_type()
    {
        $disease = Disease::factory()->orphanet()->create();

        $this->assertEquals('Orphanet', $disease->type);
        $this->assertStringStartsWith('Orphanet:', $disease->curie);
    }

    /** @test */
    public function disease_has_submissions_relationship()
    {
        $gene = Gene::factory()->create();
        $disease = Disease::factory()->create();
        $classification = Classification::factory()->create();
        $submitter = Submitter::factory()->create();
        $inheritance = Inheritance::factory()->create();

        $submission = Submission::factory()->create([
            'gene_id' => $gene->id,
            'disease_id' => $disease->id,
            'classification_id' => $classification->id,
            'submitter_id' => $submitter->id,
            'moi_id' => $inheritance->id,
            'status' => 1,
        ]);

        // Sync the disease to the submission through the pivot table
        $submission->diseases()->attach($disease->id);

        $this->assertCount(1, $disease->submissions);
    }

    /** @test */
    public function disease_title_attribute_returns_title_field()
    {
        $disease = Disease::factory()->create(['title' => 'Test Disease']);

        $this->assertEquals('Test Disease', $disease->title);
    }

    /** @test */
    public function disease_uses_soft_deletes()
    {
        $disease = Disease::factory()->create();
        $diseaseId = $disease->id;

        $disease->delete();

        $this->assertSoftDeleted('diseases', ['id' => $diseaseId]);
    }

    /** @test */
    public function disease_can_have_mondo_parent()
    {
        $parentDisease = Disease::factory()->create(['curie' => 'MONDO:0000001']);
        $childDisease = Disease::factory()->create([
            'curie' => 'OMIM:123456',
            'mondo_id' => $parentDisease->id,
        ]);

        $this->assertEquals($parentDisease->id, $childDisease->mondo_id);
    }
}
