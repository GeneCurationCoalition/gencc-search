<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // NOTE:For the counts to work the classifications slugs need to matchs what's in the counter functions
        DB::table('classifications')->insert([
            [
                'uuid' => "GENCC_000000",
                'curie' => "GENCC:000000",
                'title' => "Undefined",
                'description' => "Undefined Classification",
                'abbreviation' => "NUL.",
                'hex_color' => "#12345",
                'css_class' => "gencc-nul",
                'slug' => "nul",
                'href' => "curations_nul",
                'order' => "1000"
            ],
            [
                'uuid' => "GENCC_100001",
                'curie' => "GENCC:100001",
                'title' => "Definitive",
                'description' => "Definitive classification",
                'abbreviation' => "DEF.",
                'hex_color' => "#12345",
                'css_class' => "gencc-definitive",
                'slug' => "definitive",
                'href' => "curations_definitive",
                'order' => "10"
            ],
            [
                'uuid' => "GENCC_100002",
                'curie' => "GENCC:100002",
                'title' => "Strong",
                'description' => "Strong classification",
                'abbreviation' => "STR.",
                'hex_color' => "#12345",
                'css_class' => "gencc-strong",
                'slug' => "strong",
                'href' => "curations_strong",
                'order' => "20"
            ],
            [
                'uuid' => "GENCC_100003",
                'curie' => "GENCC:100003",
                'title' => "Moderate",
                'description' => "Moderate classification",
                'abbreviation' => "MOD.",
                'hex_color' => "#12345",
                'css_class' => "gencc-moderate",
                'slug' => "moderate",
                'href' => "curations_moderate",
                'order' => "30"
            ],
            [
                'uuid' => "GENCC_100009",
                'curie' => "GENCC:100009",
                'title' => "Supportive",
                'description' => "Supportive",
                'abbreviation' => "SUP.",
                'hex_color' => "#12345",
                'css_class' => "gencc-supportive",
                'slug' => "supportive",
                'href' => "curations_supportive",
                'order' => "40"
            ],
            [
                'uuid' => "GENCC_100004",
                'curie' => "GENCC:100004",
                'title' => "Limited",
                'description' => "Limited classification",
                'abbreviation' => "LMT.",
                'hex_color' => "#12345",
                'css_class' => "gencc-limited",
                'slug' => "limited",
                'href' => "curations_limited",
                'order' => "50"
            ],
            [
                'uuid' => "GENCC_100005",
                'curie' => "GENCC:100005",
                'title' => "Disputed Evidence",
                'description' => "Disputed evidence classification",
                'abbreviation' => "DE.",
                'hex_color' => "#12345",
                'css_class' => "gencc-disputed",
                'slug' => "disputed",
                'href' => "curations_disputed",
                'order' => "60"
            ],
            [
                'uuid' => "GENCC_100006",
                'curie' => "GENCC:100006",
                'title' => "Refuted Evidence",
                'description' => "Refuted evidence classification",
                'abbreviation' => "DE.",
                'hex_color' => "#12345",
                'css_class' => "gencc-refute",
                'slug' => "refuted",
                'href' => "curations_refuted",
                'order' => "70"
            ],
            [
                'uuid' => "GENCC_100007",
                'curie' => "GENCC:100007",
                'title' => "Animal Model Only",
                'description' => "Animal model only classification",
                'abbreviation' => "AMO.",
                'hex_color' => "#12345",
                'css_class' => "gencc-animalmodelonly",
                'slug' => "animal-model-only",
                'href' => "curations_animal",
                'order' => "80"
            ],
            [
                'uuid' => "GENCC_100008",
                'curie' => "GENCC:100008",
                'title' => "No Known Disease Relationship",
                'description' => "No known disease relationship classification",
                'abbreviation' => "NKD",
                'hex_color' => "#12345",
                'css_class' => "gencc-noknown",
                'slug' => "no-known",
                'href' => "curations_noknown",
                'order' => "90"
            ],
        ]);
    }
}



// DEFINITIVE
// STRONG
// MODERATE
// LIMITED
// DISPUTED EVIDENCE
// REFUTED EVIDENCE
// ANIMAL MODEL ONLY
// NO KNOWN DISEASE RELATIONSHIP