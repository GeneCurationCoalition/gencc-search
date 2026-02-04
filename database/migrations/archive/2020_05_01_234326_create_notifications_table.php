<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->unsigned()->index()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('submitter_id')->unsigned()->index()->nullable();
            $table->foreign('submitter_id')->references('id')->on('submitters')->onDelete('cascade');
            $table->string('ref')->nullable();
            $table->string('uuid')->nullable();
            $table->string('label')->nullable();
            $table->text('message')->nullable();
            $table->text('meta')->nullable();
            $table->integer('count')->default('0');
            $table->integer('type')->default('0');
            $table->integer('running')->default('0');
            $table->integer('output')->default('0');
            $table->integer('status')->default('0');
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
        Schema::dropIfExists('notifications');
    }
}
