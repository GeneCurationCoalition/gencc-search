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
        $name = $this->faker->words(4, true) . ' syndrome';

        return [
            'curie' => 'MONDO:' . str_pad($mondoId, 7, '0', STR_PAD_LEFT),
            'ident' => 'MONDO_' . str_pad($mondoId, 7, '0', STR_PAD_LEFT),
            'uuid' => $this->faker->uuid,
            'type' => 'MONDO',
            'title' => $name,
            'name' => $name,
            'description' => $this->faker->sentence(),
            'status' => 'active',
            // Individual count columns (not JSON)
            'count_submissions' => 0,
            'count_unique_genes' => 0,
            'count_unique_submitters' => 0,
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

    public function omim()
    {
        return $this->state(function (array $attributes) {
            $omimId = $this->faker->unique()->numberBetween(100000, 699999);
            return [
                'curie' => 'OMIM:' . $omimId,
                'ident' => 'OMIM_' . $omimId,
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
                'ident' => 'Orphanet_' . $orphaId,
                'type' => 'Orphanet',
            ];
        });
    }
}
