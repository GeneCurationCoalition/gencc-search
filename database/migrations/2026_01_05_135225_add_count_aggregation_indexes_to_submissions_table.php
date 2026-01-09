<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountAggregationIndexesToSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * These composite indexes optimize the GROUP BY queries used in
     * gencc:update-counts command for aggregating submission counts
     * by gene, submitter, and disease.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submissions', function (Blueprint $table) {
            // Index for gene count aggregation: WHERE is_current = true GROUP BY gene_id, classification_id
            $table->index(['is_current', 'gene_id', 'classification_id', 'submitter_id', 'disease_id'], 'idx_submissions_gene_counts');

            // Index for submitter count aggregation: WHERE is_current = true GROUP BY submitter_id, classification_id
            $table->index(['is_current', 'submitter_id', 'classification_id', 'gene_id', 'disease_id'], 'idx_submissions_submitter_counts');

            // Index for disease count aggregation: WHERE is_current = true GROUP BY disease_id, classification_id
            $table->index(['is_current', 'disease_id', 'classification_id', 'submitter_id'], 'idx_submissions_disease_counts');
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
            $table->dropIndex('idx_submissions_gene_counts');
            $table->dropIndex('idx_submissions_submitter_counts');
            $table->dropIndex('idx_submissions_disease_counts');
        });
    }
}
