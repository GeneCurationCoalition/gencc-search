<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the static_file_headers table as an append-only log for tracking
     * HTTP headers of external static files. The file_identifier is indexed
     * but not unique, allowing multiple historical records per file.
     */
    public function up(): void
    {
        Schema::create('static_file_headers', function (Blueprint $table) {
            $table->id();
            $table->string('file_identifier')->index();
            $table->string('content_length')->nullable();
            $table->string('last_modified')->nullable();
            $table->string('etag')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('static_file_headers');
    }
};
