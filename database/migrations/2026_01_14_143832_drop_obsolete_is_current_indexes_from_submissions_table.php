<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropObsoleteIsCurrentIndexesFromSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submissions', function (Blueprint $table) {
            // Drop obsolete indexes that use deprecated is_current column
            // These have been replaced by indexes using is_live + status
            $table->dropIndex('idx_submissions_gene_counts');
            $table->dropIndex('idx_submissions_disease_counts');
            $table->dropIndex('idx_submissions_submitter_counts');
            $table->dropIndex('idx_submissions_uuid_current');
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
            // Recreate the indexes if rollback is needed
            $table->index(['gene_id', 'is_current', 'classification_id', 'disease_id', 'submitter_id'], 'idx_submissions_gene_counts');
            $table->index(['disease_id', 'is_current', 'classification_id', 'submitter_id'], 'idx_submissions_disease_counts');
            $table->index(['submitter_id', 'is_current', 'classification_id', 'disease_id', 'gene_id'], 'idx_submissions_submitter_counts');
            $table->index(['uuid', 'is_current'], 'idx_submissions_uuid_current');
        });
    }
}
