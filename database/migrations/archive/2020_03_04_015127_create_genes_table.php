<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGenesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('genes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->nullable();
            $table->string('curie')->nullable();
            $table->string('type')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->string('date_modified')->nullable();
            $table->string('hgnc_uuid')->nullable();
            $table->string('hgnc_id')->nullable();
            $table->string('symbol')->nullable();
            $table->string('name')->nullable();
            $table->string('location')->nullable();
            $table->string('locus_group')->nullable();
            $table->string('locus_type')->nullable();
            $table->string('alias_symbol')->nullable();
            $table->text('omim_id')->nullable();
            $table->string('ucsc_id')->nullable();
            $table->string('ensembl_gene_id')->nullable();
            $table->string('entrez_id')->nullable();
            $table->string('date_approved_reserved')->nullable();
            $table->integer('curations_definitive')->default(0);
            $table->integer('curations_strong')->default(0);
            $table->integer('curations_moderate')->default(0);
            $table->integer('curations_limited')->default(0);
            $table->integer('curations_disputed')->default(0);
            $table->integer('curations_refuted')->default(0);
            $table->integer('curations_animal')->default(0);
            $table->integer('curations_noknown')->default(0);
            $table->integer('curations_supportive')->default(0);
            $table->integer('curations_nul')->default(0);
            $table->integer('count_submissions')->default(0);
            $table->integer('count_unique_diseases')->default(0);
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
        Schema::dropIfExists('genes');
    }
}
