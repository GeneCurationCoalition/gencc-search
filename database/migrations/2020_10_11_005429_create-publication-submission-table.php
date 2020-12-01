<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePublicationSubmissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('publication_submission', function (Blueprint $table) {
            $table->unsignedBigInteger('publication_id')->unsigned()->index();
            $table->foreign('publication_id')->references('id')->on('publications');
            $table->unsignedBigInteger('submission_id')->unsigned()->index();
            $table->foreign('submission_id')->references('id')->on('submissions');
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
        Schema::table('publication_submission', function (Blueprint $table) {
            //
        });
    }
}
