<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurieIndexToInheritancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inheritances', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('inheritances');

            if (!isset($indexes['idx_inheritances_curie'])) {
                $table->index('curie', 'idx_inheritances_curie');
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
        Schema::table('inheritances', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('inheritances');

            if (isset($indexes['idx_inheritances_curie'])) {
                $table->dropIndex('idx_inheritances_curie');
            }
        });
    }
}
