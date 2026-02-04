<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CleanSubmissionWhitespaceAndPmids extends Migration
{
    /**
     * Run the migrations.
     *
     * 1. Trims trailing whitespace from submitted_as_hgnc_id and submitted_as_disease_id.
     * 2. Adds normalized_pmids and submitted_as_pmids_issues columns to submissions table.
     * 3. Normalizes submitted_as_pmids into normalized_pmids using the following rules:
     *    - Split on commas, semicolons, underscores, whitespace, and non-breaking spaces
     *    - Strip [PMID] suffix from entries
     *    - Remove literal "NULL" strings
     *    - Trim whitespace from each entry
     *    - Remove zero values
     *    - Remove non-numeric entries (catches scientific notation like 1.5845E+15)
     *    - Remove values with more than 8 digits (excluding leading zeros)
     *    - Log a warning for PMIDs with fewer than 7 digits (excluding leading zeros)
     *    - Sort in ascending numerical order
     *    - Rejoin with comma delimiter
     *    - Set to null if no valid PMIDs remain
     * 4. Records failed values in submitted_as_pmids_issues as a JSON array, each entry
     *    formatted as "value (issue_label)".
     * 5. Re-synchronizes pubmed_submission pivot table so only PMIDs from the
     *    normalized list are associated with each submission.
     *
     * @return void
     */
    public function up()
    {
        // Trim trailing whitespace from submitted_as_hgnc_id
        DB::statement("UPDATE submissions SET submitted_as_hgnc_id = RTRIM(submitted_as_hgnc_id) WHERE submitted_as_hgnc_id != RTRIM(submitted_as_hgnc_id)");

        // Trim trailing whitespace from submitted_as_disease_id
        DB::statement("UPDATE submissions SET submitted_as_disease_id = RTRIM(submitted_as_disease_id) WHERE submitted_as_disease_id != RTRIM(submitted_as_disease_id)");

        // Add normalized_pmids and submitted_as_pmids_issues columns
        Schema::table('submissions', function (Blueprint $table) {
            $table->text('normalized_pmids')->nullable()->after('submitted_as_pmids');
            $table->json('submitted_as_pmids_issues')->nullable()->after('normalized_pmids');
        });

        // Build a lookup of pmid string => pubmeds.id for re-syncing the pivot table
        $pubmedLookup = DB::table('pubmeds')
            ->select('id', 'pmid')
            ->get()
            ->keyBy('pmid');

        // Normalize submitted_as_pmids into normalized_pmids
        $submissions = DB::table('submissions')
            ->whereNotNull('submitted_as_pmids')
            ->where('submitted_as_pmids', '!=', '')
            ->select('id', 'uuid', 'version_number', 'submitted_as_pmids')
            ->get();

        foreach ($submissions as $submission) {
            $sgcId = $submission->uuid . '.' . ($submission->version_number ?? 1);
            $val = $submission->submitted_as_pmids;
            $failedValues = [];

            // Replace non-breaking spaces (U+00A0) with regular spaces before processing
            $val = str_replace("\xC2\xA0", ' ', $val);

            // Split on commas, semicolons, underscores, and whitespace
            $parts = preg_split('/[,;_\s]+/', $val, -1, PREG_SPLIT_NO_EMPTY);

            $validPmids = [];
            foreach ($parts as $part) {
                $original = trim($part);
                $trimmed = $original;

                // Strip [PMID] suffix
                if (preg_match('/\[PMID\]$/i', $trimmed)) {
                    $trimmed = preg_replace('/\[PMID\]$/i', '', $trimmed);
                }

                // Remove literal NULL strings
                if (strcasecmp($trimmed, 'NULL') === 0) {
                    $failedValues[] = "{$original} (literal_null)";
                    continue;
                }

                // Skip empty values
                if ($trimmed === '') {
                    continue;
                }

                // Detect scientific notation before digit check
                if (preg_match('/\d+\.\d+E\+\d+/i', $trimmed)) {
                    $failedValues[] = "{$original} (scientific_notation)";
                    continue;
                }

                // Must be purely numeric (digits only)
                if (!preg_match('/^\d+$/', $trimmed)) {
                    $failedValues[] = "{$original} (non_numeric)";
                    continue;
                }

                // Remove zero values
                if (intval($trimmed) === 0) {
                    $failedValues[] = "{$original} (zero_value)";
                    continue;
                }

                // Remove values with more than 8 digits (excluding leading zeros)
                $numericStr = ltrim($trimmed, '0');
                if (strlen($numericStr) > 8) {
                    $failedValues[] = "{$original} (exceeds_max_digits)";
                    continue;
                }

                // Warn for PMIDs with fewer than 7 digits (excluding leading zeros)
                if (strlen($numericStr) < 7) {
                    Log::warning("Suspect PMID (fewer than 7 digits): {$trimmed} on submission {$sgcId}");
                }

                $validPmids[] = $trimmed;
            }

            // Sort in ascending numerical order
            usort($validPmids, function ($a, $b) {
                return intval($a) - intval($b);
            });

            $normalized = implode(',', $validPmids);
            $issuesJson = !empty($failedValues) ? json_encode($failedValues) : null;

            DB::table('submissions')
                ->where('id', $submission->id)
                ->update([
                    'normalized_pmids' => $normalized ?: null,
                    'submitted_as_pmids_issues' => $issuesJson,
                ]);

            // Re-synchronize pubmed_submission pivot table
            // Remove all existing associations for this submission
            DB::table('pubmed_submission')
                ->where('submission_id', $submission->id)
                ->delete();

            // Re-associate only the normalized PMIDs
            foreach ($validPmids as $pmid) {
                $pubmed = $pubmedLookup->get($pmid);
                if ($pubmed) {
                    DB::table('pubmed_submission')->insert([
                        'pubmed_id' => $pubmed->id,
                        'submission_id' => $submission->id,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('normalized_pmids');
            $table->dropColumn('submitted_as_pmids_issues');
        });
    }
}
