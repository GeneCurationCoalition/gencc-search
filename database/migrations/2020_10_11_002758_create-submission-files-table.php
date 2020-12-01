<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submission_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->nullable();
            $table->string('name')->nullable();
            $table->text('body')->nullable();
            $table->string('path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_name_original')->nullable();
            $table->string('file_type')->nullable();
            $table->string('file_type_original')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('file_size_human')->nullable();
            $table->text('log')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('submitter_id')->unsigned()->index()->nullable();
            $table->foreign('submitter_id')->references('id')->on('submitters')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('created_by_user')->nullable();
            $table->foreign('created_by_user')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submission_files', function (Blueprint $table) {
            //
        });
    }
}
