<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionTrioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submission_trio', function (Blueprint $table) {
            $table->unsignedBigInteger('submission_id')->unsigned()->index();
            $table->foreign('submission_id')->references('id')->on('submissions')->onDelete('cascade');
            $table->unsignedBigInteger('trio_id')->unsigned()->index();
            $table->foreign('trio_id')->references('id')->on('trios')->onDelete('cascade');
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
        Schema::dropIfExists('submission_trio');
    }
}
