<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClassificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('classifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->string('curie')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('info_text')->nullable();
            $table->string('abbreviation')->nullable();
            $table->string('hex_color')->nullable();
            $table->string('css_class')->nullable();
            $table->string('slug')->nullable();
            $table->string('href')->nullable();
            $table->string('order');
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
        Schema::dropIfExists('classifications');
    }
}
