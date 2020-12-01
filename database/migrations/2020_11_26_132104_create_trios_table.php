<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTriosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->nullable();
            $table->string('name')->nullable();
            $table->integer('status')->default(0);
            $table->unsignedBigInteger('gene_id')->unsigned()->index()->nullable();
            $table->foreign('gene_id')->references('id')->on('genes')->onDelete('cascade');
            $table->unsignedBigInteger('disease_id')->unsigned()->index()->nullable();
            $table->foreign('disease_id')->references('id')->on('diseases')->onDelete('cascade');
            $table->unsignedBigInteger('moi_id')->unsigned()->index()->nullable();
            $table->foreign('moi_id')->references('id')->on('inheritances')->onDelete('cascade');
            $table->unsignedBigInteger('classification_id')->unsigned()->index()->nullable();
            $table->foreign('classification_id')->references('id')->on('classifications')->onDelete('cascade');
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
        Schema::dropIfExists('trios');
    }
}
