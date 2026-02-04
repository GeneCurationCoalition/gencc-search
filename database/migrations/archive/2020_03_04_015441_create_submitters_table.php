<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmittersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submitters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->string('curie')->unique();
            $table->string('title');
            $table->string('website')->nullable();
            $table->string('path_logo')->nullable();
            $table->text('text_descriptions')->nullable();
            $table->text('text_contact')->nullable();
            $table->text('text_assertions')->nullable();
            $table->text('text_disclaimer')->nullable();
            $table->integer('downloadable')->default(0);
            $table->integer('status')->default(1);
            $table->integer('curations_definitive')->default(0);
            $table->integer('curations_strong')->default(0);
            $table->integer('curations_moderate')->default(0);
            $table->integer('curations_supportive')->default(0);
            $table->integer('curations_limited')->default(0);
            $table->integer('curations_disputed')->default(0);
            $table->integer('curations_refuted')->default(0);
            $table->integer('curations_nul')->default(0);
            $table->integer('curations_animal')->default(0);
            $table->integer('curations_noknown')->default(0);
            $table->integer('count_unique_diseases')->default(0);
            $table->integer('count_unique_genes')->default(0);
            $table->integer('count_submissions')->default(0);
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
        Schema::dropIfExists('submitters');
    }
}
