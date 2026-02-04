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
            'uuid' => $this->faker->uuid,
            'curie' => 'HGNC:' . $hgncId,
            'type' => 'gene',
            'title' => $symbol,
            'name' => $this->faker->words(3, true),
            'symbol' => $symbol,
            'hgnc_id' => 'HGNC:' . $hgncId,
            'location' => $this->faker->numberBetween(1, 22) . 'p' . $this->faker->numberBetween(1, 3) . '.' . $this->faker->numberBetween(1, 9),
            'locus_group' => 'protein-coding gene',
            'locus_type' => 'gene with protein product',
            'entrez_id' => (string) $this->faker->unique()->numberBetween(1, 100000),
            'ensembl_gene_id' => 'ENSG' . str_pad($this->faker->unique()->numberBetween(1, 999999), 11, '0', STR_PAD_LEFT),
            'ucsc_id' => 'uc' . $this->faker->lexify('???') . '.' . $this->faker->numberBetween(1, 9),
            // Text columns (not JSON)
            'alias_symbol' => $this->faker->lexify('???'),
            'prev_symbol' => null,
            // Individual count columns (not JSON)
            'count_submissions' => 0,
            'count_unique_submitters' => 0,
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
            'status' => '0',
        ];
    }
}
