<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Type mapping from old string values to new integer constants
     */
    protected $typeMap = [
        'MONDO' => 1,
        'OMIM' => 10,
        'Orphanet' => 20,
        'Orpha' => 20,
        'ORPHANET' => 20,
        'ORPHA' => 20,
    ];

    /**
     * Status mapping from old values to new integer constants
     * Old: 0=Initialized, 1=Active, 9=GG_Deprecated, 10=Deprecated
     * New: 0=Initializing, 1=Active, 8=Deprecated, 9=Removed
     */
    protected $statusMap = [
        '0' => 0,   // Initialized -> Initializing
        '1' => 1,   // Active -> Active
        '9' => 8,   // GG_Deprecated -> Deprecated
        '10' => 8,  // Deprecated -> Deprecated
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip data migration on SQLite (for testing)
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->info('Skipping data migration on SQLite');
            return;
        }

        $this->migrateBasicFields();
        $this->migrateTypeAndStatus();
        $this->migrateSynonymsToJson();
        $this->migrateXrefsToJson();
        $this->populateMondoIdFromEquivalents();
    }

    /**
     * Migrate basic fields: title -> name, uuid -> ident
     */
    protected function migrateBasicFields(): void
    {
        // Copy title to name
        DB::statement('UPDATE diseases SET name = title WHERE name IS NULL');

        // Generate ident UUIDs for existing records
        DB::table('diseases')->whereNull('ident')->orderBy('id')->chunk(1000, function ($diseases) {
            foreach ($diseases as $disease) {
                DB::table('diseases')
                    ->where('id', $disease->id)
                    ->update(['ident' => Str::uuid()->toString()]);
            }
        });
    }

    /**
     * Migrate type and status from strings to integers
     */
    protected function migrateTypeAndStatus(): void
    {
        // Migrate type values
        foreach ($this->typeMap as $oldType => $newType) {
            DB::table('diseases')
                ->where('type', $oldType)
                ->update(['type_new' => $newType]);
        }

        // Migrate status values
        foreach ($this->statusMap as $oldStatus => $newStatus) {
            DB::table('diseases')
                ->where('status', $oldStatus)
                ->update(['status_new' => $newStatus]);
        }

        // Handle any NULL or unknown status values
        DB::table('diseases')
            ->whereNull('status')
            ->update(['status_new' => 0]);
    }

    /**
     * Migrate synonyms_exact and synonyms_related to JSON synonyms column
     */
    protected function migrateSynonymsToJson(): void
    {
        DB::table('diseases')->orderBy('id')->chunk(1000, function ($diseases) {
            foreach ($diseases as $disease) {
                $synonyms = [];

                // Parse pipe-delimited synonyms_exact
                if (!empty($disease->synonyms_exact)) {
                    $exact = array_filter(explode('|', $disease->synonyms_exact));
                    foreach ($exact as $syn) {
                        $synonyms[] = trim($syn);
                    }
                }

                // Parse pipe-delimited synonyms_related
                if (!empty($disease->synonyms_related)) {
                    $related = array_filter(explode('|', $disease->synonyms_related));
                    foreach ($related as $syn) {
                        $synonyms[] = trim($syn);
                    }
                }

                // Remove duplicates and empty values
                $synonyms = array_values(array_unique(array_filter($synonyms)));

                DB::table('diseases')
                    ->where('id', $disease->id)
                    ->update(['synonyms_json' => json_encode($synonyms)]);
            }
        });
    }

    /**
     * Migrate xrefs from pipe-delimited string to JSON object
     */
    protected function migrateXrefsToJson(): void
    {
        DB::table('diseases')->orderBy('id')->chunk(1000, function ($diseases) {
            foreach ($diseases as $disease) {
                $xrefsJson = [
                    'omim_id' => [],
                    'orpha_id' => null,
                    'do_id' => null,
                    'gard_id' => null,
                    'umls_id' => null,
                    'medgen_id' => null,
                    'mesh' => null,
                    'ncit' => null,
                ];

                // Parse existing xrefs field (pipe-delimited)
                if (!empty($disease->xrefs)) {
                    $xrefItems = array_filter(explode('|', $disease->xrefs));
                    foreach ($xrefItems as $xref) {
                        $xref = trim($xref);
                        if (empty($xref)) continue;

                        // Parse CURIE format (PREFIX:ID)
                        if (strpos($xref, ':') !== false) {
                            list($prefix, $id) = explode(':', $xref, 2);
                            $prefix = strtoupper($prefix);

                            switch ($prefix) {
                                case 'OMIM':
                                    $xrefsJson['omim_id'][] = $id;
                                    break;
                                case 'ORPHANET':
                                case 'ORPHA':
                                    $xrefsJson['orpha_id'] = $id;
                                    break;
                                case 'DOID':
                                    $xrefsJson['do_id'] = $id;
                                    break;
                                case 'GARD':
                                    $xrefsJson['gard_id'] = $id;
                                    break;
                                case 'UMLS':
                                    $xrefsJson['umls_id'] = $id;
                                    break;
                                case 'MEDGEN':
                                    $xrefsJson['medgen_id'] = $id;
                                    break;
                                case 'MESH':
                                    $xrefsJson['mesh'] = $id;
                                    break;
                                case 'NCIT':
                                    $xrefsJson['ncit'] = $id;
                                    break;
                            }
                        }
                    }
                }

                // Remove duplicate OMIM IDs
                $xrefsJson['omim_id'] = array_values(array_unique($xrefsJson['omim_id']));

                DB::table('diseases')
                    ->where('id', $disease->id)
                    ->update(['xrefs_json' => json_encode($xrefsJson)]);
            }
        });
    }

    /**
     * Populate mondo_id from existing disease_disease equivalents relationships
     */
    protected function populateMondoIdFromEquivalents(): void
    {
        // Find all non-MONDO diseases that have equivalents to MONDO diseases
        // The disease_disease table has equiv_id pointing to equivalent diseases
        $sql = "
            UPDATE diseases d
            INNER JOIN disease_disease dd ON d.id = dd.disease_id
            INNER JOIN diseases mondo ON dd.equiv_id = mondo.id AND mondo.type = 'MONDO'
            SET d.mondo_id = mondo.id
            WHERE d.type != 'MONDO'
            AND d.mondo_id IS NULL
        ";

        DB::statement($sql);

        // Also check the reverse relationship
        $sql = "
            UPDATE diseases d
            INNER JOIN disease_disease dd ON d.id = dd.equiv_id
            INNER JOIN diseases mondo ON dd.disease_id = mondo.id AND mondo.type = 'MONDO'
            SET d.mondo_id = mondo.id
            WHERE d.type != 'MONDO'
            AND d.mondo_id IS NULL
        ";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the new columns (data can be regenerated from old columns)
        DB::table('diseases')->update([
            'ident' => null,
            'name' => null,
            'type_new' => 0,
            'status_new' => 0,
            'synonyms_json' => null,
            'xrefs_json' => null,
            'mondo_id' => null,
        ]);
    }

    /**
     * Output info message (for console commands)
     */
    protected function info($message): void
    {
        if (app()->runningInConsole()) {
            echo $message . PHP_EOL;
        }
    }
};
