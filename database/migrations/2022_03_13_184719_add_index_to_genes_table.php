<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToGenesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('genes', function (Blueprint $table) {
            $table->index('uuid');
            $table->index('curie');
            $table->index('title');
            $table->index('hgnc_id');
            $table->index('symbol');
            $table->index('name');
            $table->index('curations_definitive');
            $table->index('curations_strong');
            $table->index('curations_moderate');
            $table->index('curations_limited');
            $table->index('curations_disputed');
            $table->index('curations_refuted');
            $table->index('curations_animal');
            $table->index('curations_supportive');
        });

        //Copy the data across to the new column:
       // DB::table('genes')->update([
        //    'ident' => (string) Uuid::generate(4)
        //]);

        //DB::statement("ALTER TABLE gennes MODIFY COLUMN prev_symbol json AFTER locus_type");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('genes', function (Blueprint $table) {
            //
        });
    }
}
