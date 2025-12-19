<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddMoreToGenesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Skip column changes on SQLite (testing) - they require doctrine/dbal with special handling
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('genes', function (Blueprint $table) {
                $table->string('ident')->nullable();
                $table->string('chr')->nullable();
                $table->text('grch37')->nullable();
                $table->text('grch38')->nullable();
                $table->text('chm13')->nullable();
                $table->text('mane_select')->nullable();
                $table->text('mane_plus')->nullable();
                $table->text('function')->nullable();
                $table->string('date_symbol_changed')->nullable();
                $table->text('gene_group')->nullable();
                $table->text('lsdb')->nullable();
                $table->string('loeuf')->nullable();
                $table->string('pli')->nullable();
                $table->string('hi')->nullable();
                $table->string('haplo')->nullable();
                $table->string('triplo')->nullable();
                $table->boolean('is_acmgsf3')->default(false);
                $table->boolean('is_morbid')->default(false);
                $table->text('curation_status')->nullable();
                $table->timestamp('date_last_curated')->nullable();
                $table->text('notes')->nullable();
                $table->tinyInteger('nstatus')->default(0);
                $table->text('prev_symbol')->nullable();
                $table->softDeletes();
            });
            return;
        }

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
