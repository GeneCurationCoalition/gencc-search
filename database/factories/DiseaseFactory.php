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
            'type' => 1,
            'name' => $name,
            'description' => $this->faker->sentence(),
            'synonyms' => [],
            'xrefs' => [],
            'scores' => [],
            'counts' => ['total' => 0, 'by_classification' => []],
            'activity' => [],
            'events' => [],
            'status' => 1,
        ];
    }

    public function omim()
    {
        return $this->state(function (array $attributes) {
            $omimId = $this->faker->unique()->numberBetween(100000, 699999);
            return [
                'curie' => 'OMIM:' . $omimId,
                'ident' => 'OMIM_' . $omimId,
                'type' => Disease::TYPE_OMIM,
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
                'type' => Disease::TYPE_ORPHANET,
            ];
        });
    }
}
