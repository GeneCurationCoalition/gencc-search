<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SubmitterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('submitters')->insert([
            [
                'uuid' => "GENCC_000101",
                'curie' => "GENCC:000101",
                'title' => "Ambry Genetics",
                'path_logo' => '/brand/submitters/logo_ambry.png',
                'website' => "",
            ],
            [
                'uuid' => "GENCC_000102",
                'curie' => "GENCC:000102",
                'title' => "ClinGen",
                'path_logo' => '/brand/submitters/logo_clingen.png',
                'website' => "",
            ],
            [
                'uuid' => "GENCC_000103",
                'curie' => "GENCC:000103",
                'title' => "DECIPHER",
                'path_logo' => '/brand/submitters/logo_decipher.png',
                'website' => "",
            ],
            [
                'uuid' => "GENCC_000104",
                'curie' => "GENCC:000104",
                'title' => "Genomics England PanelApp",
                'path_logo' => '/brand/submitters/logo_panelapp-eng.png',
                'website' => "",
            ],
            [
                'uuid' => "GENCC_000105",
                'curie' => "GENCC:000105",
                'title' => "Illumina",
                'path_logo' => '/brand/submitters/logo_illumina.png',
                'website' => "",
            ],
            [
                'uuid' => "GENCC_000106",
                'curie' => "GENCC:000106",
                'title' => "Invitae",
                'path_logo' => '/brand/submitters/logo_invitae.png',
                'website' => "",
            ],
            [
                'uuid' => "GENCC_000107",
                'curie' => "GENCC:000107",
                'title' => "Laboratory for Molecular Medicine",
                'path_logo' => '/brand/submitters/logo_lmm.png',
                'website' => "",
            ],
            [
                'uuid' => "GENCC_000108",
                'curie' => "GENCC:000108",
                'title' => "Myriad Women’s Health",
                'path_logo' => '/brand/submitters/logo_myriad.png',
                'website' => "",
            ],
            [
                'uuid' => "GENCC_000109",
                'curie' => "GENCC:000109",
                'title' => "Online Mendelian Inheritance in Man (OMIM)",
                'path_logo' => '/brand/submitters/logo_omim.png',
                'website' => "",
            ],
            [
                'uuid' => "GENCC_000110",
                'curie' => "GENCC:000110",
                'title' => "Orphanet",
                'path_logo' => '/brand/submitters/logo_orphanet.png',
                'website' => "",
            ],
            [
                'uuid' => "GENCC_000111",
                'curie' => "GENCC:000111",
                'title' => "PanelApp Australia",
                'path_logo' => '/brand/submitters/logo_panelapp-aus.png',
                'website' => "",
            ],
            [
                'uuid' => "GENCC_000112",
                'curie' => "GENCC:000112",
                'title' => "TGMI|G2P",
                'path_logo' => '/brand/submitters/logo_tgmi-g2p.png',
                'website' => "",
            ],
        ]);
    }
}
