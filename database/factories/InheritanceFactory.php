<?php

namespace Database\Factories;

use App\Inheritance;
use Illuminate\Database\Eloquent\Factories\Factory;

class InheritanceFactory extends Factory
{
    protected $model = Inheritance::class;

    public function definition()
    {
        $inheritances = [
            ['title' => 'Autosomal dominant', 'abbreviation' => 'AD'],
            ['title' => 'Autosomal recessive', 'abbreviation' => 'AR'],
            ['title' => 'X-linked', 'abbreviation' => 'XL'],
            ['title' => 'X-linked dominant', 'abbreviation' => 'XLD'],
            ['title' => 'X-linked recessive', 'abbreviation' => 'XLR'],
            ['title' => 'Mitochondrial', 'abbreviation' => 'MT'],
            ['title' => 'Semidominant', 'abbreviation' => 'SD'],
        ];

        $inheritance = $this->faker->randomElement($inheritances);

        return [
            'curie' => 'HP:' . str_pad($this->faker->unique()->numberBetween(1, 999999), 7, '0', STR_PAD_LEFT),
            'ident' => 'moi-' . $this->faker->uuid(),
            'type' => 0,
            'name' => $inheritance['title'],
            'abbreviation' => $inheritance['abbreviation'],
            'description' => $this->faker->sentence(),
            'status' => 1,
        ];
    }

    public function autosomalDominant()
    {
        return $this->state(fn () => [
            'name' => 'Autosomal dominant',
            'abbreviation' => 'AD',
        ]);
    }

    public function autosomalRecessive()
    {
        return $this->state(fn () => [
            'name' => 'Autosomal recessive',
            'abbreviation' => 'AR',
        ]);
    }
}
