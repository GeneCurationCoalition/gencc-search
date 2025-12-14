<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration finalizes the schema change by:
     * 1. Dropping old columns that have been migrated
     * 2. Renaming new columns to their final names
     *
     * NOTE: Run this migration only after verifying data migration was successful.
     * Consider running this in a separate deployment after testing.
     */
    public function up(): void
    {
        // Skip this migration on SQLite (testing) - it requires MySQL-specific operations
        if (DB::getDriverName() === 'sqlite') {
            echo "Skipping finalize_disease_schema_migration on SQLite\n";
            return;
        }

        // Drop old columns that will be replaced by renamed columns
        // type (varchar) -> type_new (tinyint) renamed to type
        // status (varchar) -> status_new (tinyint) renamed to status
        // xrefs (text) -> xrefs_json (json) renamed to xrefs
        Schema::table('diseases', function (Blueprint $table) {
            $table->dropColumn([
                'type',    // varchar - replaced by type_new (tinyint)
                'status',  // varchar - replaced by status_new (tinyint)
                'xrefs',   // text - replaced by xrefs_json (json)
            ]);
        });

        // Rename new columns to final names
        Schema::table('diseases', function (Blueprint $table) {
            $table->renameColumn('type_new', 'type');
            $table->renameColumn('status_new', 'status');
            $table->renameColumn('synonyms_json', 'synonyms');
            $table->renameColumn('xrefs_json', 'xrefs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename columns back to temporary names
        Schema::table('diseases', function (Blueprint $table) {
            $table->renameColumn('type', 'type_new');
            $table->renameColumn('status', 'status_new');
            $table->renameColumn('synonyms', 'synonyms_json');
            $table->renameColumn('xrefs', 'xrefs_json');
        });

        // Re-add old columns
        Schema::table('diseases', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->text('xrefs')->nullable();
        });
    }
};
