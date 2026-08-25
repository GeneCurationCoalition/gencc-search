<?php

namespace Database\Factories;

use App\Submission;
use App\Gene;
use App\Disease;
use App\Classification;
use App\Submitter;
use App\Inheritance;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition()
    {
        $disease = Disease::factory()->create();
        $pmid = (string) $this->faker->numberBetween(10000000, 39999999);

        return [
            'ident' => $this->faker->uuid,
            'type' => 0,
            'sid' => 'SGC-' . $this->faker->unique()->numberBetween(100000, 999999),
            'job_id' => 1,
            'user_id' => 1,
            'gene_id' => Gene::factory(),
            'disease_id' => $disease->id,
            'original_disease_id' => $disease->id,
            'classification_id' => Classification::factory(),
            'submitter_id' => Submitter::factory(),
            'inheritance_id' => Inheritance::factory(),
            'report_date' => $this->faker->date(),
            'report_url' => $this->faker->url,
            'evidence' => [
                'pmids' => [$pmid],
            ],
            'submission_data' => [
                'notes' => ['display' => $this->faker->paragraph()],
            ],
            'original_submission_data' => [
                'gene' => ['id' => null, 'symbol' => null],
                'disease' => ['id' => null, 'name' => null],
                'moi' => ['id' => null, 'name' => null],
                'classification' => ['id' => null, 'name' => null],
                'additional_information' => ['submitter_curie' => null, 'submitter_title' => null],
                'report' => ['ext_url' => null],
                'criteria' => ['url' => null],
                'notes' => ['display' => null],
            ],
            'normalized_pmids' => $pmid,
            'version_number' => 1,
            'is_most_recent' => true,
            'is_live' => true,            // Most recent version
            'status' => 'published',      // Publicly visible
            'released_at' => now(),
        ];
    }

    /**
     * Create an unpublished submission (most recent version, but hidden from public).
     * is_live=true (most recent), status='unpublished' (hidden)
     */
    public function unpublished()
    {
        return $this->state(fn () => [
            'is_live' => true,            // Most recent version
            'status' => 'unpublished',    // Hidden from public
            'released_at' => now(),
        ]);
    }

    public function version(int $version)
    {
        return $this->state(fn () => ['version_number' => $version]);
    }

    /**
     * Create a live published submission (most recent, publicly visible).
     */
    public function live()
    {
        return $this->state(fn () => [
            'is_live' => true,
            'status' => 'published',
        ]);
    }

    /**
     * Create a non-live submission (historical, superseded by newer version).
     */
    public function notLive()
    {
        return $this->state(fn () => ['is_live' => false]);
    }

    /**
     * Create a historical submission (superseded by a newer version).
     * is_live=false means this is not the most recent version.
     */
    public function historical()
    {
        return $this->state(fn () => [
            'is_live' => false,           // Not the most recent version
            'status' => 'published',      // Was published before being superseded
        ]);
    }

    public function forGene(Gene $gene)
    {
        return $this->state(fn () => ['gene_id' => $gene->id]);
    }

    public function forDisease(Disease $disease)
    {
        return $this->state(fn () => ['disease_id' => $disease->id]);
    }

    public function forSubmitter(Submitter $submitter)
    {
        return $this->state(fn () => ['submitter_id' => $submitter->id]);
    }

    public function forClassification(Classification $classification)
    {
        return $this->state(fn () => ['classification_id' => $classification->id]);
    }
}
