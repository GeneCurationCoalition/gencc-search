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
        return [
            'uuid' => $this->faker->uuid,
            'order' => $this->faker->numberBetween(1, 1000),
            'gene_id' => Gene::factory(),
            'disease_id' => $disease->id,
            'disease_original_id' => $disease->id,
            'classification_id' => Classification::factory(),
            'submitter_id' => Submitter::factory(),
            'moi_id' => Inheritance::factory(),
            'submitted_as_date' => $this->faker->date(),
            'submitted_as_public_report_url' => $this->faker->url,
            'submitted_as_notes' => $this->faker->paragraph(),
            'submitted_as_pmids' => (string) $this->faker->numberBetween(10000000, 39999999),
            'submitted_as_assertion_criteria_url' => $this->faker->url,
            'submitted_run_date' => $this->faker->dateTimeThisYear(),
            'version_number' => 1,
            'is_current' => true,
        ];
    }

    public function unpublished()
    {
        return $this->state(fn () => [
            'is_current' => false,
            'unpublished_at' => now(),
        ]);
    }

    public function version(int $version)
    {
        return $this->state(fn () => ['version_number' => $version]);
    }

    public function notCurrent()
    {
        return $this->state(fn () => ['is_current' => false]);
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
