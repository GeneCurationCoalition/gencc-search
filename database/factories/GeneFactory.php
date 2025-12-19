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
            'uuid' => $this->faker->uuid,
            'curie' => 'HGNC:' . $hgncId,
            'type' => 'gene',
            'title' => $symbol,
            'name' => $this->faker->words(3, true),
            'symbol' => $symbol,
            'hgnc_id' => 'HGNC:' . $hgncId,
            'hgnc_uuid' => $this->faker->uuid,
            'location' => $this->faker->numberBetween(1, 22) . 'p' . $this->faker->numberBetween(1, 3) . '.' . $this->faker->numberBetween(1, 9),
            'locus_group' => 'protein-coding gene',
            'locus_type' => 'gene with protein product',
            'entrez_id' => (string) $this->faker->unique()->numberBetween(1, 100000),
            'ensembl_gene_id' => 'ENSG' . str_pad($this->faker->unique()->numberBetween(1, 999999), 11, '0', STR_PAD_LEFT),
            'ucsc_id' => 'uc' . $this->faker->lexify('???') . '.' . $this->faker->numberBetween(1, 9),
            // These are stored as JSON strings in the migration, not arrays
            'omim_id' => json_encode([(string) $this->faker->numberBetween(100000, 699999)]),
            'alias_symbol' => $this->faker->lexify('???'),
            'status' => '0',
        ];
    }
}
