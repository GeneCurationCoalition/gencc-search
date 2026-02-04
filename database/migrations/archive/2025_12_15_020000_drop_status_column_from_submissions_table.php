<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropStatusColumnFromSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration removes the legacy 'status' column from submissions.
     * The status column has been replaced by:
     * - is_current (boolean): TRUE for current/published versions
     * - unpublished_at (timestamp): When a submission was explicitly unpublished
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (Schema::hasColumn('submissions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('submissions', 'status')) {
                // Restore the status column (default to 1 = published)
                $table->unsignedTinyInteger('status')->default(1)->after('moi_id');
            }
        });
    }
}
