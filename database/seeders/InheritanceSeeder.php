<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class InheritanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        //
        DB::table('inheritances')->insert([
            [
                'uuid' => "HP_0000005",
                'curie' => "HP:0000005",
                'title' => "Unset Inheritance",
                'description' => "Mode of inheritance HP:0000005",
                'abbreviation' => "unset",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-unset",
            ],
            [
                'uuid' => "HP_0000006",
                'curie' => "HP:0000006",
                'title' => "Autosomal dominant",
                'description' => "Autosomal dominant inheritance HP:0000006",
                'abbreviation' => "AD",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-ad",
            ],
            [
                'uuid' => "HP_0010985",
                'curie' => "HP:0010985",
                'title' => "Gonosomal",
                'description' => "Gonosomal inheritance HP:0010985",
                'abbreviation' => "GS",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-gs",
            ],
            [
                'uuid' => "HP_0001426",
                'curie' => "HP:0001426",
                'title' => "Multifactorial",
                'description' => "Multifactorial inheritance HP:0001426",
                'abbreviation' => "MF",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-mf",
            ],
            [
                'uuid' => "HP_0032382",
                'curie' => "HP:0032382",
                'title' => "Uniparental disomy",
                'description' => "Uniparental disomy HP:0032382",
                'abbreviation' => "UNP",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-unp",
            ],
            [
                'uuid' => "HP_0001428",
                'curie' => "HP:0001428",
                'title' => "Somatic mutation",
                'description' => "Somatic mutation HP:0001428",
                'abbreviation' => "SOM",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-som",
            ],
            [
                'uuid' => "HP-0000007",
                'curie' => "HP:0000007",
                'title' => "Autosomal recessive",
                'description' => "Autosomal recessive inheritance HP:0000007",
                'abbreviation' => "AR",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-ar",
            ],
            [
                'uuid' => "HP_0001466",
                'curie' => "HP:0001466",
                'title' => "Contiguous gene syndrome",
                'description' => "Contiguous gene syndrome HP:0001466",
                'abbreviation' => "CGS",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-cgs",
            ],
            [
                'uuid' => "HP_0003743",
                'curie' => "HP:0003743",
                'title' => "Genetic anticipation",
                'description' => "Genetic anticipation HP:0003743",
                'abbreviation' => "GA",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-ga",
            ],
            [
                'uuid' => "HP_0001425",
                'curie' => "HP:0001425",
                'title' => "Heterogeneous",
                'description' => "Heterogeneous HP:0001425",
                'abbreviation' => "HET",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-het",
            ],
            [
                'uuid' => "HP_0001427",
                'curie' => "HP:0001427",
                'title' => "Mitochondrial",
                'description' => "Mitochondrial inheritance HP:0001427",
                'abbreviation' => "MIT",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-mit",
            ],
            [
                'uuid' => "HP-0032113",
                'curie' => "HP:0032113",
                'title' => "Semidominant",
                'description' => "Semidominant mode of inheritance HP:0032113",
                'abbreviation' => "SD",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-de",
            ],
            [
                'uuid' => "HP_0003745",
                'curie' => "HP:0003745",
                'title' => "Sporadic",
                'description' => "Sporadic HP:0003745",
                'abbreviation' => "SPR",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-spr",
            ],
            [
                'uuid' => "HP_0001417",
                'curie' => "HP:0001417",
                'title' => "X-linked",
                'description' => "X-linked inheritance HP:0001417",
                'abbreviation' => "XL",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-xl",
            ],
            [
                'uuid' => "HP_0001419",
                'curie' => "HP:0001419",
                'title' => "X-linked recessive",
                'description' => "X-linked recessive inheritance HP:0001419",
                'abbreviation' => "XLR",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-xlr",
            ],
            [
                'uuid' => "HP_0001423",
                'curie' => "HP:0001423",
                'title' => "X-linked dominant",
                'description' => "X-linked dominant inheritance HP:0001423",
                'abbreviation' => "XLD",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-xld",
            ],
            [
                'uuid' => "HP_0001450",
                'curie' => "HP:0001450",
                'title' => "Y-linked inheritance",
                'description' => "Y-linked inheritance HP:0001450",
                'abbreviation' => "YLD",
                'hex_color' => "#12345",
                'css_class' => "inheritance-style-yld",
            ],
            [
                'uuid' => "HP_0001442",
                'curie' => "HP:0001442",
                'title' => "Somatic mosaicism",
                'description' => "Somatic mosaicism HP:0001442",
                'abbreviation' => "SM",
                'hex_color' => "#12345",
                'css_class' => "",
            ],
            [
                'uuid' => "HP_0012274",
                'curie' => "HP:0012274",
                'title' => "Autosomal dominant inheritance with paternal imprinting",
                'description' => "Autosomal dominant inheritance with paternal imprinting HP:0012274 ",
                'abbreviation' => "ADIPI",
                'hex_color' => "#12345",
                'css_class' => "",
            ],
            [
                'uuid' => "HP_0010984",
                'curie' => "HP:0010984",
                'title' => "Digenic inheritance HP:0010984",
                'description' => "Digenic inheritance",
                'abbreviation' => "DI",
                'hex_color' => "#12345",
                'css_class' => "",
            ],
            [
                'uuid' => "HP_0012275",
                'curie' => "HP:0012275",
                'title' => "Autosomal dominant inheritance with maternal imprinting HP:0012275",
                'description' => "Autosomal dominant inheritance with maternal imprinting",
                'abbreviation' => "ADIMI",
                'hex_color' => "#12345",
                'css_class' => "",
            ],
        ]);
    }
}
