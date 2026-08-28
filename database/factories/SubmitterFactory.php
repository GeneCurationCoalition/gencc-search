<?php

namespace Database\Factories;

use App\Submitter;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmitterFactory extends Factory
{
    protected $model = Submitter::class;

    public function definition()
    {
        $title = $this->faker->company . ' ' . $this->faker->randomElement(['Consortium', 'Institute', 'Laboratory', 'Center']);
        $ident = 'GENCC_' . str_pad($this->faker->unique()->numberBetween(1, 999), 6, '0', STR_PAD_LEFT);
        return [
            'curie' => 'GENCC:' . str_pad($this->faker->unique()->numberBetween(1, 999), 6, '0', STR_PAD_LEFT),
            'ident' => $ident,
            'type' => 0,
            'name' => $title,
            'website' => $this->faker->url,
            'description' => $this->faker->paragraph(),
            'assertion' => $this->faker->sentence(),
            'status' => 1,
            'allow_submissions' => true,
            'downloadable' => true,
            'counts' => ['total' => 0, 'by_classification' => []],
            'activity' => [],
            'contacts' => [],
        ];
    }

    public function inactive()
    {
        return $this->state(fn () => ['status' => 0]);
    }

    public function nonMember()
    {
        return $this->state(fn () => ['allow_submissions' => false]);
    }
}
