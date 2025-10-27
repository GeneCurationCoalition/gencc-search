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
                'uuid' => "GENCC_000119",
                'curie' => "GENCC:000119",
                'title' => "Stanford Center for Undiagnosed Diseases",
                'website' => "https://gregor.stanford.edu",
                'text_descriptions' => "The Stanford Center for Undiagnosed Diseases at Stanford University is a member of the GREGoR Consortium (Genomics Research to Elucidate the Genetics of Rare Disease) and a clinical site of the Undiagnosed Diseases Network. The Stanford Center for Undiagnosed Diseases aims to identify and provide answers for patients with complex, undiagnosed medical conditions through a collaborative, multidisciplinary approach. Its objectives include leveraging advanced genomic technologies to diagnose suspected Mendelian disease through gene discovery, functional validation, and piloting new diagnostic tools.",
                'text_contact' => "gregorsite@stanford.edu",
                'text_assertions' => "https://www.clinicalgenome.org/docs/gene-disease-validity-standard-operating-procedure/"
            ],
        ]);
    }
}
