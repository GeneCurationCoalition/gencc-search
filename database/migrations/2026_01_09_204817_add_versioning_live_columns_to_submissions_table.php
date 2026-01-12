<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add versioning columns to support the new gencc-sub payload format.
 *
 * This migration adds:
 * - is_live: Whether the submission version is publicly visible
 * - is_most_recent: Whether this is the most recent version of the SGC ID
 * - released_at: When the version was released (publish or unpublish date)
 *
 * Version States:
 * | State              | is_live | is_most_recent | Description                    |
 * |--------------------|---------|----------------|--------------------------------|
 * | Current published  | true    | true           | Active, publicly visible       |
 * | Historical         | false   | false          | Old version, superseded        |
 * | Unpublished        | false   | true           | Removed - content hidden       |
 *
 * The existing is_current column will be deprecated in a future migration.
 */
class AddVersioningLiveColumnsToSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add new versioning columns
        Schema::table('submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('submissions', 'is_live')) {
                $table->boolean('is_live')->default(false)->after('is_current');
            }
            if (!Schema::hasColumn('submissions', 'is_most_recent')) {
                $table->boolean('is_most_recent')->default(false)->after('is_live');
            }
            if (!Schema::hasColumn('submissions', 'released_at')) {
                $table->timestamp('released_at')->nullable()->after('is_most_recent');
            }
        });

        // Add indexes for efficient queries
        Schema::table('submissions', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('submissions');

            if (!isset($indexes['idx_submissions_is_live'])) {
                $table->index('is_live', 'idx_submissions_is_live');
            }
            if (!isset($indexes['idx_submissions_uuid_live'])) {
                $table->index(['uuid', 'is_live'], 'idx_submissions_uuid_live');
            }
            if (!isset($indexes['idx_submissions_uuid_most_recent'])) {
                $table->index(['uuid', 'is_most_recent'], 'idx_submissions_uuid_most_recent');
            }
        });

        // Backfill existing data based on is_current and unpublished_at
        // - is_current=true AND unpublished_at IS NULL => is_live=true, is_most_recent=true (published)
        // - is_current=true AND unpublished_at IS NOT NULL => is_live=false, is_most_recent=true (unpublished)
        // - is_current=false => is_live=false, is_most_recent=false (historical)
        // - released_at: use unpublished_at if set, otherwise use updated_at
        DB::statement("
            UPDATE submissions
            SET
                is_live = CASE
                    WHEN is_current = TRUE AND unpublished_at IS NULL THEN TRUE
                    ELSE FALSE
                END,
                is_most_recent = CASE
                    WHEN is_current = TRUE THEN TRUE
                    ELSE FALSE
                END,
                released_at = COALESCE(unpublished_at, updated_at)
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submissions', function (Blueprint $table) {
            // Drop indexes first
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('submissions');

            if (isset($indexes['idx_submissions_is_live'])) {
                $table->dropIndex('idx_submissions_is_live');
            }
            if (isset($indexes['idx_submissions_uuid_live'])) {
                $table->dropIndex('idx_submissions_uuid_live');
            }
            if (isset($indexes['idx_submissions_uuid_most_recent'])) {
                $table->dropIndex('idx_submissions_uuid_most_recent');
            }

            // Drop columns
            $table->dropColumn(['is_live', 'is_most_recent', 'released_at']);
        });
    }
}
