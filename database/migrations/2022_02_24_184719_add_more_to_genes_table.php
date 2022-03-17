<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreToGenesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('genes', function (Blueprint $table) {
            $table->string('ident')->unique()->nullable()->after('id');
            $table->string('chr')->nullable()->after('location');
            $table->json('grch37')->nullable()->after('chr');
            $table->json('grch38')->nullable()->after('grch37');
            $table->json('chm13')->nullable()->after('grch38');
            $table->json('mane_select')->nullable()->after('chm13');
            $table->json('mane_plus')->nullable()->after('mane_select');
            $table->text('function')->nullable()->after('mane_plus');
            $table->string('date_symbol_changed')->nullable()->after('alias_symbol');

            $table->jsonb('gene_group')->nullable()->after('locus_group');

            $table->string('uniprot_id')->nullable()->after('omim_id');

            //$table->json('omim_id')->nullable();

            $table->json('lsdb')->nullable()->after('locus_type');
			$table->string('loeuf')->nullable()->after('lsdb');
			$table->string('pli')->nullable()->after('loeuf');
            $table->string('hi')->nullable()->after('pli');
			$table->string('haplo')->nullable()->after('hi');
			$table->string('triplo')->nullable()->after('haplo');

            $table->boolean('is_acmgsf3')->default(false)->after('triplo');
            $table->boolean('is_morbid')->default(false)->after('is_acmgsf3');

            $table->json('curation_status')->nullable()->after('is_morbid');
            $table->timestamp('date_last_curated')->nullable()->after('curation_status');

			$table->mediumText('notes')->nullable()->after('count_unique_submitters');
			$table->tinyInteger('nstatus')->default(0)->after('notes');
            $table->json('alias_symbol')->change();
            $table->json('prev_symbol')->nullable()->after('date_last_curated');
            $table->softDeletes();

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
