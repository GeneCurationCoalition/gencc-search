<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiseaseSubmissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disease_submission', function (Blueprint $table) {
            $table->unsignedBigInteger('disease_id')->unsigned()->index()->nullable();
            $table->foreign('disease_id')->references('id')->on('diseases')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('submission_id')->unsigned()->index()->nullable();
            $table->foreign('submission_id')->references('id')->on('submissions')->onDelete('cascade')->nullable();
            $table->string('type')->nullable();
            $table->string('ontology')->nullable();
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
        Schema::dropIfExists('disease_submission');
    }
}
