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
        DB::table('submitters')
            ->where("uuid", "GENCC_000107")
            ->update(
                [
                    'path_logo' => '/brand/submitters/logo_lmm.png',
                    'website' => "https://www.massgeneralbrigham.org/en/research-and-innovation/centers-and-programs/personalized-medicine/molecular-medicine",
                    'text_descriptions' => "The Laboratory for Molecular Medicine (LMM) is a CLIA-certified molecular diagnostic laboratory, operated by Mass General Brigham Personalized Medicine. The LMM is led by a group of Harvard Medical School-affiliated faculty, geneticists, clinicians, and researchers from Brigham and Women’s Hospital and Massachusetts General Hospital, Mass General Brigham’s founding members. Our mission is to bridge the gap between research and clinical medicine.",
                    'text_contact' => "Kalotina Machini<br>Email: kmachini@bwh.harvard.edu",
                ]
            );
        DB::table('submitters')
            ->where("uuid", "GENCC_000106")
            ->update(
                [
                    'title' => "Labcorp Genetics (formerly Invitae)",
                    'text_descriptions' => "Labcorp Genetics (formerly Invitae) is a CLIA-certified fee-for-service clinical testing laboratory.",
                    'text_contact' => "Yunyun Jiang, Coordinator <br>Phone: 800-436-3037<br>Email: yunyun.jiang@invitae.com",
                ]
            );
    }
}
