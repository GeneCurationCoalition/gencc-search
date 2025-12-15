<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddVersioningColumnsToSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submissions', function (Blueprint $table) {
            // Add versioning columns (version_number may already exist)
            if (!Schema::hasColumn('submissions', 'version_number')) {
                $table->unsignedInteger('version_number')->default(1)->after('uuid');
            }
            if (!Schema::hasColumn('submissions', 'unpublished_at')) {
                $table->timestamp('unpublished_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('submissions', 'is_current')) {
                $table->boolean('is_current')->default(true)->after('status');
            }
        });

        // Add indexes if they don't exist
        Schema::table('submissions', function (Blueprint $table) {
            // Check and add indexes
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('submissions');

            if (!isset($indexes['idx_submissions_uuid_current'])) {
                $table->index(['uuid', 'is_current'], 'idx_submissions_uuid_current');
            }
            if (!isset($indexes['idx_submissions_uuid_version'])) {
                $table->index(['uuid', 'version_number'], 'idx_submissions_uuid_version');
            }
        });

        // Backfill existing data based on current status
        // status=1 (published): is_current=TRUE
        // status=0 or other (unpublished): is_current=FALSE, unpublished_at=updated_at
        DB::statement("
            UPDATE submissions
            SET is_current = CASE WHEN status = 1 THEN TRUE ELSE FALSE END,
                unpublished_at = CASE WHEN status != 1 THEN updated_at ELSE NULL END
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
            // Drop indexes first (if they exist)
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('submissions');

            if (isset($indexes['idx_submissions_uuid_current'])) {
                $table->dropIndex('idx_submissions_uuid_current');
            }
            if (isset($indexes['idx_submissions_uuid_version'])) {
                $table->dropIndex('idx_submissions_uuid_version');
            }

            // Drop columns
            $table->dropColumn(['unpublished_at', 'is_current']);
        });
    }
}
