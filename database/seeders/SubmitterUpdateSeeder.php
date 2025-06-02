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
            ->where("uuid", "GENCC_000112")
            ->update(
                [
                    'text_assertions' => "https://www.ebi.ac.uk/gene2phenotype/about/terminology"
                ]
            );
    }
}
