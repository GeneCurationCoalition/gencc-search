<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInheritancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inheritances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid');
            $table->string('curie');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('info_text')->nullable();
            $table->string('abbreviation')->nullable();
            $table->string('hex_color')->nullable();
            $table->string('css_class')->nullable();
            $table->integer('status')->default(1);
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
        Schema::dropIfExists('inheritances');
    }
}
