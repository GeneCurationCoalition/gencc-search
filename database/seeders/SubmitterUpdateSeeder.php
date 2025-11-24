<?php
/*
 * Do not include in DatabaseSeeder - should only be executed standalone.
 * Execute with: php artisan db:seed --class=SubmitterUpdateSeeder
 */
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class SubmitterUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('submitters')->insert([
            [
                'uuid' => "GENCC_000120",
                'curie' => "GENCC:000120",
                'title' => "ClinPGx",
                'website' => "https://www.clinpgx.org/",
                'text_descriptions' => "The Pharmacogenomics Knowledge Base (PharmGKB) is a publicly available resource for pharmacogenomics discovery and implementation. PharmGKB collects, curates and disseminates knowledge about the impact of human genetic variation on drug response phenotypes. Its content encompasses information to catalyze scientific research such as variant annotations and drug-centered pathways, and clinically relevant information including high-level clinical annotations, clinical guideline annotations, and drug label annotations.",
                'member' => 1,
                'status' => 1,
            ],
            [
                'uuid' => "GENCC_000121",
                'curie' => "GENCC:000121",
                'title' => "HGNC",
                'website' => "https://www.genenames.org/",
                'text_descriptions' => "The HGNC (HUGO Gene Nomenclature Committee) is the sole worldwide authority for providing approved symbols and names for human genes.",
                'member' => 1,
                'status' => 1,
            ],
        ]);
    }
}
