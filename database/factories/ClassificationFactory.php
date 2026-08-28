<?php

namespace Database\Factories;

use App\Classification;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassificationFactory extends Factory
{
    protected $model = Classification::class;

    public function definition()
    {
        $classifications = [
            ['curie' => 'GENCC:100001', 'name' => 'Definitive', 'abbreviation' => 'DEF', 'order' => 10],
            ['curie' => 'GENCC:100002', 'name' => 'Strong', 'abbreviation' => 'STR', 'order' => 20],
            ['curie' => 'GENCC:100003', 'name' => 'Moderate', 'abbreviation' => 'MOD', 'order' => 30],
            ['curie' => 'GENCC:100009', 'name' => 'Supportive', 'abbreviation' => 'SUP', 'order' => 40],
            ['curie' => 'GENCC:100004', 'name' => 'Limited', 'abbreviation' => 'LIM', 'order' => 50],
            ['curie' => 'GENCC:100005', 'name' => 'Disputed Evidence', 'abbreviation' => 'DIS', 'order' => 60],
            ['curie' => 'GENCC:100006', 'name' => 'Refuted Evidence', 'abbreviation' => 'REF', 'order' => 70],
            ['curie' => 'GENCC:100007', 'name' => 'Animal Model Only', 'abbreviation' => 'ANI', 'order' => 80],
            ['curie' => 'GENCC:100008', 'name' => 'No Known Disease Relationship', 'abbreviation' => 'NOK', 'order' => 90],
        ];

        $classification = $this->faker->randomElement($classifications);

        return [
            'ident' => 'class-' . $this->faker->uuid(),
            'type' => 0,
            'curie' => $classification['curie'],
            'name' => $classification['name'],
            'abbreviation' => $classification['abbreviation'],
            'order' => $classification['order'],
            'description' => $this->faker->sentence(),
            'status' => 1,
        ];
    }

    public function definitive()
    {
        return $this->state(fn () => [
            'curie' => 'GENCC:100001',
            'name' => 'Definitive',
            'abbreviation' => 'DEF',
            'order' => 10,
        ]);
    }

    public function strong()
    {
        return $this->state(fn () => [
            'curie' => 'GENCC:100002',
            'name' => 'Strong',
            'abbreviation' => 'STR',
            'order' => 20,
        ]);
    }
}
