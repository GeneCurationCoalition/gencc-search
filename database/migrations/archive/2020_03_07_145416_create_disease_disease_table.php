<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiseaseDiseaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disease_disease', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->unsigned()->index()->nullable();
            $table->foreign('parent_id')->references('id')->on('diseases')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('child_id')->unsigned()->index()->nullable();
            $table->foreign('child_id')->references('id')->on('diseases')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('disease_id')->unsigned()->index()->nullable();
            $table->foreign('disease_id')->references('id')->on('diseases')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('xref_id')->unsigned()->index()->nullable();
            $table->foreign('xref_id')->references('id')->on('diseases')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('synonym_id')->unsigned()->index()->nullable();
            $table->foreign('synonym_id')->references('id')->on('diseases')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('equiv_id')->unsigned()->index()->nullable();
            $table->foreign('equiv_id')->references('id')->on('diseases')->onDelete('cascade')->nullable();
            $table->string('type')->nullable();
            $table->string('predicate')->nullable();
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
        Schema::dropIfExists('disease_disease');
    }
}
