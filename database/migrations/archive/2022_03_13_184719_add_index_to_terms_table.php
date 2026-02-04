<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToTermsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->index('name', 'value');

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
