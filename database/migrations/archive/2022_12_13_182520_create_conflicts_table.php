<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConflictsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conflicts', function (Blueprint $table) {
            $table->id();
            $table->string('ident')->unique();
            $table->string('hgnc_id');
            $table->string('gene_symbol');
            $table->string('mondo_id');
            $table->string('disease');
            $table->string('moi');
            $table->integer('weak')->default(0);
            $table->integer('strong')->default(0);
            $table->json('submitters')->nullable();
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
        Schema::dropIfExists('conflicts');
    }
}
