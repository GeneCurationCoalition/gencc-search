<?php

namespace Database\Factories;

use App\Disease;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiseaseFactory extends Factory
{
    protected $model = Disease::class;

    public function definition()
    {
        $mondoId = $this->faker->unique()->numberBetween(1, 999999);

        return [
            'curie' => 'MONDO:' . str_pad($mondoId, 7, '0', STR_PAD_LEFT),
            'uuid' => 'MONDO_' . str_pad($mondoId, 7, '0', STR_PAD_LEFT),
            'type' => 'MONDO',
            'title' => $this->faker->words(4, true) . ' syndrome',
            'description' => $this->faker->sentence(),
            'status' => 'active',
        ];
    }

    public function omim()
    {
        return $this->state(function (array $attributes) {
            $omimId = $this->faker->unique()->numberBetween(100000, 699999);
            return [
                'curie' => 'OMIM:' . $omimId,
                'uuid' => 'OMIM_' . $omimId,
                'type' => 'OMIM',
            ];
        });
    }

    public function orphanet()
    {
        return $this->state(function (array $attributes) {
            $orphaId = $this->faker->unique()->numberBetween(1, 999999);
            return [
                'curie' => 'Orphanet:' . $orphaId,
                'uuid' => 'Orphanet_' . $orphaId,
                'type' => 'Orphanet',
            ];
        });
    }
}
