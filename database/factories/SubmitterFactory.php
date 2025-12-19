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
            'uuid' => $this->faker->uuid,
            'title' => $this->faker->company . ' ' . $this->faker->randomElement(['Consortium', 'Institute', 'Laboratory', 'Center']),
            'website' => $this->faker->url,
            'text_descriptions' => $this->faker->paragraph(),
            'text_contact' => $this->faker->email,
            'text_assertions' => $this->faker->paragraph(),
            'text_disclaimer' => $this->faker->paragraph(),
            'status' => 1,
            'downloadable' => 1,
            'member' => 1,
        ];
    }

    public function inactive()
    {
        return $this->state(fn () => ['status' => 0]);
    }

    public function nonMember()
    {
        return $this->state(fn () => ['member' => 0]);
    }
}
