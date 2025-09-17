<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateBaylorSubmitterAssertionCriteria extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('submitters')
            ->where('id', 16)
            ->update([
                'text_assertions' => 'https://www.clinicalgenome.org/docs/gene-disease-validity-standard-operating-procedure/'
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('submitters')
            ->where('id', 16)
            ->update([
                'text_assertions' => 'ClinGen SOP'
            ]);
    }
}
