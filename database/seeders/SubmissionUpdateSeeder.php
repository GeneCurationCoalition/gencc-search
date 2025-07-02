<?php
/*
 * Do not include in DatabaseSeeder - should only be executed standalone.
 * Execute with: php artisan db:seed --class=SubmitterUpdateSeeder
 */
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class SubmissionUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('submissions')
            ->where("uuid", "GENCC_000101-HGNC_493-OMIM_600919-HP_0000006-GENCC_100002")
            ->update(
                [
                    'status' => 0,
                ]
            );
    }
}
