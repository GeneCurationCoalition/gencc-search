<?php

namespace Database\Factories;

use App\Submitter;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmitterFactory extends Factory
{
    protected $model = Submitter::class;

    public function definition()
    {
        return [
            'curie' => 'GENCC:' . str_pad($this->faker->unique()->numberBetween(1, 999), 6, '0', STR_PAD_LEFT),
            'uuid' => 'GENCC_' . str_pad($this->faker->unique()->numberBetween(1, 999), 6, '0', STR_PAD_LEFT),
            'title' => $this->faker->company . ' ' . $this->faker->randomElement(['Consortium', 'Institute', 'Laboratory', 'Center']),
            'website' => $this->faker->url,
            'text_descriptions' => $this->faker->paragraph(),
            'text_assertions' => $this->faker->sentence(),
            'text_contact' => $this->faker->email,
            'status' => 1,
            'downloadable' => true,
            'member' => true,
            // Individual count columns
            'count_submissions' => 0,
            'count_unique_genes' => 0,
            'count_unique_diseases' => 0,
            'curations_definitive' => 0,
            'curations_strong' => 0,
            'curations_moderate' => 0,
            'curations_limited' => 0,
            'curations_disputed' => 0,
            'curations_refuted' => 0,
            'curations_animal' => 0,
            'curations_noknown' => 0,
            'curations_supportive' => 0,
            'curations_nul' => 0,
        ];
    }

    public function inactive()
    {
        return $this->state(fn () => ['status' => 0]);
    }

    public function nonMember()
    {
        return $this->state(fn () => ['member' => false]);
    }
}
