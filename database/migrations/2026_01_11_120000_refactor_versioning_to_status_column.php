<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Refactor versioning columns to use status column model.
 *
 * This migration changes the versioning model:
 * - is_live: NOW means "most recent version of SGC ID" (replaces is_most_recent meaning)
 * - status: NEW column - 'published' or 'unpublished'
 * - Drops: is_most_recent (redundant with new is_live meaning)
 * - Drops: unpublished_at (use released_at for both publish/unpublish timestamps)
 *
 * Version States:
 * | State              | is_live | status      | Description                           | Counted? |
 * |--------------------|---------|-------------|---------------------------------------|----------|
 * | Current published  | true    | published   | Active, publicly visible              | Yes      |
 * | Current unpublished| true    | unpublished | Removed - content hidden              | No       |
 * | Historical         | false   | n/a         | Old version, superseded               | No       |
 *
 * Count queries should use: WHERE is_live = true AND status = 'published'
 */
class RefactorVersioningToStatusColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Step 1: Add status column
        Schema::table('submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('submissions', 'status')) {
                $table->string('status', 20)->default('published')->after('is_live');
            }
        });

        // Step 2: Add index on status column
        Schema::table('submissions', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('submissions');

            if (!isset($indexes['idx_submissions_status'])) {
                $table->index('status', 'idx_submissions_status');
            }
            if (!isset($indexes['idx_submissions_live_status'])) {
                $table->index(['is_live', 'status'], 'idx_submissions_live_status');
            }
        });

        // Step 3: Migrate data
        // Current is_live meaning: publicly visible
        // Current is_most_recent meaning: most recent version
        // New is_live meaning: most recent version (same as current is_most_recent)
        // New status column: 'published' if was visible, 'unpublished' if was is_most_recent but not is_live

        // First, set status based on current columns:
        // - If is_live=true (currently visible) -> status = 'published'
        // - If is_most_recent=true AND is_live=false -> status = 'unpublished' (current but hidden)
        // - If is_most_recent=false (historical) -> status = 'published' (doesn't matter, not counted anyway)
        DB::statement("
            UPDATE submissions
            SET status = CASE
                WHEN is_live = TRUE THEN 'published'
                WHEN is_most_recent = TRUE AND is_live = FALSE THEN 'unpublished'
                ELSE 'published'
            END
        ");

        // Now update is_live to have new meaning (most recent version = what is_most_recent was)
        DB::statement("
            UPDATE submissions
            SET is_live = is_most_recent
        ");

        // Step 4: Drop deprecated indexes related to is_most_recent
        Schema::table('submissions', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('submissions');

            if (isset($indexes['idx_submissions_uuid_most_recent'])) {
                $table->dropIndex('idx_submissions_uuid_most_recent');
            }
        });

        // Step 5: Drop deprecated columns (SQLite requires separate calls)
        if (Schema::hasColumn('submissions', 'is_most_recent')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn('is_most_recent');
            });
        }

        if (Schema::hasColumn('submissions', 'unpublished_at')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn('unpublished_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Step 1: Re-add is_most_recent and unpublished_at columns
        Schema::table('submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('submissions', 'is_most_recent')) {
                $table->boolean('is_most_recent')->default(false)->after('is_live');
            }
            if (!Schema::hasColumn('submissions', 'unpublished_at')) {
                $table->timestamp('unpublished_at')->nullable()->after('released_at');
            }
        });

        // Step 2: Restore data from status column back to original column meanings
        // is_most_recent = is_live (current meaning is most recent)
        // is_live (old meaning) = (status = 'published' AND is_live = true)
        DB::statement("
            UPDATE submissions
            SET is_most_recent = is_live
        ");

        DB::statement("
            UPDATE submissions
            SET is_live = CASE
                WHEN is_live = TRUE AND status = 'published' THEN TRUE
                ELSE FALSE
            END
        ");

        // Set unpublished_at from released_at for unpublished submissions
        DB::statement("
            UPDATE submissions
            SET unpublished_at = released_at
            WHERE status = 'unpublished'
        ");

        // Step 3: Re-add index
        Schema::table('submissions', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('submissions');

            if (!isset($indexes['idx_submissions_uuid_most_recent'])) {
                $table->index(['uuid', 'is_most_recent'], 'idx_submissions_uuid_most_recent');
            }
        });

        // Step 4: Drop status column and its indexes
        Schema::table('submissions', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('submissions');

            if (isset($indexes['idx_submissions_status'])) {
                $table->dropIndex('idx_submissions_status');
            }
            if (isset($indexes['idx_submissions_live_status'])) {
                $table->dropIndex('idx_submissions_live_status');
            }

            if (Schema::hasColumn('submissions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
}
