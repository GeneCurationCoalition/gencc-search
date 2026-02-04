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
            ['title' => 'Definitive', 'abbreviation' => 'DEF', 'slug' => 'definitive', 'order' => 1, 'hex_color' => '#276749', 'css_class' => 'classification-definitive'],
            ['title' => 'Strong', 'abbreviation' => 'STR', 'slug' => 'strong', 'order' => 2, 'hex_color' => '#38a169', 'css_class' => 'classification-strong'],
            ['title' => 'Moderate', 'abbreviation' => 'MOD', 'slug' => 'moderate', 'order' => 3, 'hex_color' => '#68d391', 'css_class' => 'classification-moderate'],
            ['title' => 'Limited', 'abbreviation' => 'LIM', 'slug' => 'limited', 'order' => 4, 'hex_color' => '#f6e05e', 'css_class' => 'classification-limited'],
            ['title' => 'Disputed', 'abbreviation' => 'DIS', 'slug' => 'disputed', 'order' => 5, 'hex_color' => '#ed8936', 'css_class' => 'classification-disputed'],
            ['title' => 'Refuted', 'abbreviation' => 'REF', 'slug' => 'refuted', 'order' => 6, 'hex_color' => '#e53e3e', 'css_class' => 'classification-refuted'],
        ];

        $classification = $this->faker->randomElement($classifications);

        return [
            'curie' => 'GENCC:' . str_pad($this->faker->unique()->numberBetween(1, 999999), 7, '0', STR_PAD_LEFT),
            'uuid' => 'class-' . $this->faker->uuid(),
            'ident' => 'class-' . $this->faker->uuid(),
            'name' => $classification['title'],
            'title' => $classification['title'],
            'abbreviation' => $classification['abbreviation'],
            'slug' => $classification['slug'],
            'order' => $classification['order'],
            'hex_color' => $classification['hex_color'],
            'css_class' => $classification['css_class'],
            'description' => $this->faker->sentence(),
            'status' => 1,
        ];
    }

    public function definitive()
    {
        return $this->state(fn () => [
            'name' => 'Definitive',
            'title' => 'Definitive',
            'abbreviation' => 'DEF',
            'slug' => 'definitive',
            'order' => 1,
        ]);
    }

    public function strong()
    {
        return $this->state(fn () => [
            'name' => 'Strong',
            'title' => 'Strong',
            'abbreviation' => 'STR',
            'slug' => 'strong',
            'order' => 2,
        ]);
    }
}
