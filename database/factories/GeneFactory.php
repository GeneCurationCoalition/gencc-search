<?php

namespace Database\Factories;

use App\Gene;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeneFactory extends Factory
{
    protected $model = Gene::class;

    public function definition()
    {
        $hgncId = $this->faker->unique()->numberBetween(1, 50000);
        $symbol = strtoupper($this->faker->lexify('????'));

        return [
            'ident' => $this->faker->uuid,
            'type' => 0,
            'name' => $this->faker->words(3, true),
            'symbol' => $symbol,
            'hgnc_id' => 'HGNC:' . $hgncId,
            'location' => $this->faker->numberBetween(1, 22) . 'p' . $this->faker->numberBetween(1, 3) . '.' . $this->faker->numberBetween(1, 9),
            'locus_group' => 'protein-coding gene',
            'locus_type' => 'gene with protein product',
            'alias_symbols' => [$this->faker->lexify('???')],
            'previous_symbols' => [],
            'alias_names' => [],
            'previous_names' => [],
            'coordinates' => [],
            'xrefs' => [],
            'scores' => [],
            'counts' => ['total' => 0, 'by_classification' => []],
            'activity' => [],
            'events' => [],
            'status' => 0,
        ];
    }
}
