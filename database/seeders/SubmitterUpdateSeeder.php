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
                'uuid' => "GENCC_000117",
                'curie' => "GENCC:000117",
                'title' => "LiferaOmics",
                'path_logo' => '/brand/submitters/logo_lifera.png',
                'website' => "https://lifera.com.sa/omics",
                'text_descriptions' => "Under the leadership of Prof. Fowzan Alkuraya, as its Chief Medical and Genomics Officer, Lifera Omics aims to enhance the speed and accuracy of clinical diagnoses across health systems in Saudi Arabia, and to enable precision medicine for rare and common diseases through innovative, data-driven solutions, leveraging industry-leading diagnostic expertise and partnerships.<br/>Lifera Omics, a subsidiary of Lifera, was established in January 2024 to develop at-scale multiomic dry and wet lab capacity to the highest global standards and to help enable the genomics goals of Saudi Arabia's National Biotech Strategy.",
                'text_contact' => "Dr. Bader Alhaddad",
                'text_assertions' => "ACMG/AMP/ClinGen SVI guidelines"
            ],
            [
                'uuid' => "GENCC_000118",
                'curie' => "GENCC:000118",
                'title' => "University of Washington Center for Rare Disease Research (UW-CRDR)",
                'path_logo' => '/brand/submitters/logo_uw-crdr.png',
                'website' => "https://uwmendelian.org/#/",
                'text_descriptions' => "The University of Washington Center for Rare Disease Research (UW-CRDR) is one of five centers in the NHGRI Genomics Research to Elucidate the Genetics of Rare diseases (GREGoR) consortium with an overall goal of identifying the genetic basis of Mendelian conditions for which the underlying cause is unknown. The UW-CRDR strategies include generating and analyzing exome, genome (short-, long-, and targeted long-read), short- and long-read transcriptome, methylation, and emerging -omic data types for qualified investigators and Mendelian phenotypes.",
                'text_contact' => "Jessica Chong<br>Email: jxchong@uw.edu",
                'text_assertions' => "LMM Rapid Curation SOP"
            ]]);
    }
}
