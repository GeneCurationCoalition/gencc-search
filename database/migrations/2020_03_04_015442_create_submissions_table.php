<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('gene_id')->unsigned()->index()->nullable();
            $table->foreign('gene_id')->references('id')->on('genes')->onDelete('cascade');
            $table->unsignedBigInteger('disease_id')->unsigned()->index()->nullable();
            $table->foreign('disease_id')->references('id')->on('diseases')->onDelete('cascade');
            $table->unsignedBigInteger('classification_id')->unsigned()->index()->nullable();
            $table->foreign('classification_id')->references('id')->on('classifications')->onDelete('cascade');
            $table->unsignedBigInteger('moi_id')->unsigned()->index()->nullable();
            $table->foreign('moi_id')->references('id')->on('inheritances')->onDelete('cascade');
            $table->unsignedBigInteger('submitter_id')->unsigned()->index()->nullable();
            $table->foreign('submitter_id')->references('id')->on('submitters')->onDelete('cascade');

            $table->string('uuid')->nullable();
            $table->integer('status')->default(1);
            $table->integer('workspace')->default(1);
            $table->string('submitted_as_submission_id')->nullable();
            $table->string('submitted_as_hgnc_id')->nullable();
            $table->string('submitted_as_hgnc_symbol')->nullable();
            $table->string('submitted_as_disease_id')->nullable();
            $table->string('submitted_as_disease_name')->nullable();
            $table->string('submitted_as_moi_id')->nullable();
            $table->string('submitted_as_moi_name')->nullable();
            $table->string('submitted_as_submitter_id')->nullable();
            $table->string('submitted_as_submitter_name')->nullable();
            $table->string('submitted_as_classification_id')->nullable();
            $table->string('submitted_as_classification_name')->nullable();
            $table->string('submitted_as_date')->nullable();
            $table->string('submitted_as_public_report_url')->nullable();
            $table->text('submitted_as_notes')->nullable();
            $table->text('submitted_as_pmids')->nullable();
            $table->string('submitted_as_assertion_criteria_url')->nullable();
            $table->string('submitted_as_status')->nullable();
            $table->string('submitted_as_action')->nullable();

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
        Schema::dropIfExists('submissions');
    }
}
