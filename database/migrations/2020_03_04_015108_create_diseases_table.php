<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiseasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diseases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->nullable();
            $table->string('curie')->nullable();
            $table->string('type')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->nullable();
            $table->text('description')->nullable();
            $table->string('children_sync')->nullable();
            $table->string('related_exactMatch')->nullable();
            $table->string('related_closeMatch')->nullable();
            $table->string('synonyms_exact')->nullable();
            $table->string('synonyms_related')->nullable();
            $table->string('xrefs')->nullable();
            $table->string('meta_parents')->nullable();
            $table->integer('count_child_submissions')->default(0);
            $table->integer('curations_definitive')->default(0);
            $table->integer('curations_strong')->default(0);
            $table->integer('curations_moderate')->default(0);
            $table->integer('curations_supportive')->default(0);
            $table->integer('curations_limited')->default(0);
            $table->integer('curations_disputed')->default(0);
            $table->integer('curations_refuted')->default(0);
            $table->integer('curations_animal')->default(0);
            $table->integer('curations_noknown')->default(0);
            $table->integer('curations_nul')->default(0);
            $table->integer('count_clinical_above')->default(0);
            $table->integer('count_clinical_neutral')->default(0);
            $table->integer('count_clinical_below')->default(0);
            $table->integer('count_submissions')->default(0);
            $table->integer('count_unique_genes')->default(0);
            $table->integer('count_unique_submitters')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('diseases');
    }
}
