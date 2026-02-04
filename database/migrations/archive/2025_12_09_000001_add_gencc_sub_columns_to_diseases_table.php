<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds new columns from the gencc-sub disease design:
     * - ident: UUID identifier (replaces uuid)
     * - mondo_id: Foreign key to canonical MONDO disease
     * - name: Disease name (replaces title)
     * - deprecated_name: Preserved name when deprecated
     * - JSON columns for flexible data storage
     * - Soft deletes support
     */
    public function up(): void
    {
        Schema::table('diseases', function (Blueprint $table) {
            // New identifier column (will replace uuid)
            $table->string('ident')->nullable()->unique()->after('id');

            // Foreign key to canonical MONDO disease
            $table->unsignedBigInteger('mondo_id')->nullable()->after('ident');
            $table->foreign('mondo_id')->references('id')->on('diseases')->onDelete('set null');

            // New name column (will replace title)
            $table->string('name')->nullable()->after('title');

            // Preserved name when disease becomes deprecated
            $table->string('deprecated_name')->nullable()->after('name');

            // New type column as tinyInteger (will replace string type)
            $table->tinyInteger('type_new')->default(0)->after('type');

            // New status column as tinyInteger (will replace string status)
            $table->tinyInteger('status_new')->default(0)->after('status');

            // JSON columns for flexible data storage
            $table->json('synonyms_json')->nullable()->after('synonyms_related');
            $table->json('xrefs_json')->nullable()->after('xrefs');
            $table->json('scores')->nullable()->after('xrefs_json');
            $table->json('activity')->nullable()->after('scores');
            $table->json('events')->nullable()->after('activity');

            // Notes field
            $table->text('notes')->nullable()->after('events');

            // Soft deletes
            $table->softDeletes();

            // Indexes
            $table->index('mondo_id');
            $table->index('ident');
            $table->index(['curie', 'type_new', 'status_new'], 'diseases_curie_type_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diseases', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('diseases_curie_type_status_index');
            $table->dropIndex(['ident']);
            $table->dropIndex(['mondo_id']);

            // Drop foreign key
            $table->dropForeign(['mondo_id']);

            // Drop columns
            $table->dropColumn([
                'ident',
                'mondo_id',
                'name',
                'deprecated_name',
                'type_new',
                'status_new',
                'synonyms_json',
                'xrefs_json',
                'scores',
                'activity',
                'events',
                'notes',
                'deleted_at'
            ]);
        });
    }
};
