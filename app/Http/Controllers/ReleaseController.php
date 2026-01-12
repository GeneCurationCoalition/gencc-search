<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use Setting;

use App\Classification;
use App\Gene;
use App\Disease;
use App\Inheritance;
use App\Submitter;
use App\Submission;
use Carbon\Carbon;

class ReleaseController extends Controller
{
    /**
     * Process an incoming release request.
     * Uses X-GENCC-ACTION header to determine action (PUBLISH or UNPUBLISH).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function process(Request $request)
    {
        // Handle cases where Laravel doesn't parse JSON request body
        // This can happen with certain Content-Type variations
        $all_inputs = $request->all();
        if (empty($all_inputs) && $request->getContent()) {
            $raw_body = $request->getContent();
            $parsed = json_decode($raw_body, true);

            if ($parsed !== null && json_last_error() === JSON_ERROR_NONE) {
                $request->merge($parsed);
                Log::info("Manually parsed JSON request body", [
                    'content_type' => $request->header('Content-Type'),
                    'parsed_keys' => array_keys($parsed)
                ]);
            } else {
                Log::error("Failed to parse JSON request body", [
                    'content_type' => $request->header('Content-Type'),
                    'json_error' => json_last_error_msg()
                ]);
            }
        }

        // check if posting is allowed
        $reject = Setting::get('allow_posts', "no");

        if ($reject != "yes")
        {
            Log::info("Attempt to add submission when posts were disallowed");
            return response()->json(['success' => 'false',
                        'status_code' => 9002,
                        'message' => 'Service not available'],
                        501);
        }

        // confirm token
        $ptoken = Setting::get('token_posts', false);

        if ($ptoken === false || $request->input('token') != $ptoken)
        {
            $data = $request->input('data');
            $sgc_id = 'UNKNOWN';
            $local_key = 'UNKNOWN';
            $action = $request->header('X-GENCC-ACTION', 'UNKNOWN');
            $provided_token = $request->input('token', 'NONE');
            $all_inputs = $request->all();
            $raw_body = $request->getContent();

            if ($data) {
                if (is_array($data)) {
                    $sgc_id = $data['submission_id'] ?? 'UNKNOWN';
                    $local_key = $data['local_key'] ?? 'UNKNOWN';
                } elseif (is_object($data)) {
                    $sgc_id = $data->submission_id ?? 'UNKNOWN';
                    $local_key = $data->local_key ?? 'UNKNOWN';
                }
            }

            Log::error("Attempt to add submission with invalid token", [
                'action' => $action,
                'sgc_id' => $sgc_id,
                'local_key' => $local_key,
                'token_provided' => substr($provided_token, 0, 10) . '...',
                'token_valid' => $ptoken !== false,
                'request_keys' => array_keys($all_inputs),
                'ip' => $request->ip(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'content_type' => $request->header('Content-Type'),
                'content_length' => $request->header('Content-Length'),
                'x_gencc_action' => $request->header('X-GENCC-ACTION'),
                'raw_body_length' => strlen($raw_body),
                'raw_body_preview' => substr($raw_body, 0, 500)
            ]);
            return response()->json(['success' => 'false',
                        'status_code' => 9001,
                        'message' => 'No auth'],
                        501);
        }

        // X-GENCC-ACTION header is required
        $headerAction = $request->header('X-GENCC-ACTION');
        if ($headerAction === null) {
            Log::warning("Missing X-GENCC-ACTION header");
            return response()->json([
                'success' => 'false',
                'status_code' => 9014,
                'message' => 'Missing X-GENCC-ACTION header. Expected PUBLISH, UNPUBLISH, INIT, or UPDATE_COUNTS.'
            ], 400);
        }

        $data = $request->input('data');
        $localKey = is_array($data) ? ($data['local_key'] ?? 'UNKNOWN') : (is_object($data) ? ($data->local_key ?? 'UNKNOWN') : 'UNKNOWN');

        // Normalize action to uppercase for comparison
        $action = strtoupper(trim($headerAction));

        Log::info("Processing release request", [
            'action' => $action,
            'local_key' => $localKey
        ]);

        switch ($action) {
            case 'PUBLISH':
                $check = $this->publish_submission($request);

                if ($check === true) {
                    Log::info("Submission {$localKey} published");
                    Setting::set('update_counts', 1);
                    Setting::save();

                    return response()->json([
                        'success' => 'true',
                        'status_code' => 200,
                        'sid' => $localKey,
                        'message' => 'Submission accepted'
                    ], 200);
                } else {
                    return response()->json([
                        'success' => 'false',
                        'status_code' => 9007,
                        'sid' => $localKey,
                        'message' => 'Submission failed: ' . $check
                    ], 501);
                }

            case 'UNPUBLISH':
                $check = $this->unpublish_submission($request);

                if ($check === true) {
                    Log::info("Submission {$localKey} unpublished");
                    Setting::set('update_counts', 1);
                    Setting::save();

                    return response()->json([
                        'success' => 'true',
                        'status_code' => 200,
                        'sid' => $localKey,
                        'message' => 'Submission unpublished'
                    ], 200);
                } else {
                    return response()->json([
                        'success' => 'false',
                        'status_code' => 9008,
                        'sid' => $localKey,
                        'message' => 'Submission unpublish failed: ' . $check
                    ], 501);
                }

            case 'INIT':
                Log::info("Init request received - authentication verified");
                return response()->json([
                    'success' => 'true',
                    'status_code' => 200,
                    'message' => 'Ready for jobs'
                ], 200);

            case 'UPDATE_COUNTS':
                Log::info("Update counts requested via API");
                try {
                    Artisan::call('gencc:update-counts', ['force' => 'yes']);
                    $output = Artisan::output();
                    Log::info("Update counts completed: " . $output);
                    return response()->json([
                        'success' => 'true',
                        'status_code' => 200,
                        'message' => 'Counts updated successfully'
                    ], 200);
                } catch (\Exception $e) {
                    Log::error("Update counts failed: " . $e->getMessage());
                    return response()->json([
                        'success' => 'false',
                        'status_code' => 9012,
                        'message' => 'Count update failed: ' . $e->getMessage()
                    ], 500);
                }

            default:
                Log::warning("Unknown X-GENCC-ACTION header value: {$action}");
                return response()->json([
                    'success' => 'false',
                    'status_code' => 9013,
                    'message' => "Invalid X-GENCC-ACTION header value: {$action}. Expected PUBLISH, UNPUBLISH, INIT, or UPDATE_COUNTS."
                ], 400);
        }
    }

    /**
     * Publish a submission record.
     * Creates a new version with is_live=true, status='published'.
     *
     * @param  Request  $request
     * @return bool|string True on success, error message on failure
     */
    public function publish_submission(Request $request)
    {
        try {
            $data = $request->input('data');

            // web1 (and probably web2) are casting inputs to arrays, so we need to cast back.
            $data = json_encode($data);
            $data = json_decode($data);

            // Get release_date from payload
            $releaseDate = $request->input('release_date');
            if ($releaseDate) {
                $releaseDate = Carbon::parse($releaseDate);
            } else {
                $releaseDate = Carbon::now();
            }

            // Get version_number from payload
            $versionNumber = $data->version_number ?? 1;

            // Get SGC ID early for error logging
            $sgc_id = $data->submission_id ?? 'UNKNOWN';
            $local_key = $data->local_key ?? 'UNKNOWN';

            Log::info("Processing disease - Submitted curie: {$data->disease->id}");

            // Validate and look up all required entities (uses caching for static lookups)
            $lookupResult = $this->validateAndLookupEntities($data, $sgc_id, $local_key);
            if (!$lookupResult['success']) {
                return $lookupResult['error'];
            }

            // Extract validated entities
            $gene = $lookupResult['entities']['gene'];
            $disease_original = $lookupResult['entities']['disease_original'];
            $disease = $lookupResult['entities']['disease'];
            $classification = $lookupResult['entities']['classification'];
            $moi = $lookupResult['entities']['moi'];
            $submitter = $lookupResult['entities']['submitter'];

            // Repack evidence lines
            $evidences = [];
            foreach ($data->evidence as $evidence) {
                if (!empty($evidence->pmid)) {
                    $evidences[] = $evidence->pmid;
                }
            }

            // Archive ALL existing versions of this submission (mark as historical)
            $archivedCount = Submission::where('uuid', $data->submission_id)
                ->update([
                    'is_live' => false,       // No longer most recent
                    'is_current' => false     // Keep deprecated column in sync
                ]);

            if ($archivedCount > 0) {
                Log::info("Archived {$archivedCount} existing version(s) of {$data->submission_id}");
            }

            // Create the new submission record
            // is_live=true means this is the most recent version
            // status='published' means it's publicly visible
            $submissionData = [
                'uuid'                                   => $data->submission_id,
                'version_number'                         => $versionNumber,
                'is_live'                                => true,   // Most recent version
                'status'                                 => Submission::STATUS_PUBLISHED,
                'is_current'                             => true,   // Keep deprecated column in sync
                'released_at'                            => $releaseDate,
                'order'                                  => $classification->order,
                'submitted_run_date'                     => $releaseDate,
                'submitted_as_hgnc_id'                   => $data->gene->id,
                'submitted_as_disease_id'                => $data->disease->id,
                'submitted_as_moi_id'                    => $data->moi->id,
                'submitted_as_submitter_id'              => $data->submitter->id,
                'submitted_as_submission_id'             => $data->local_key,
                'submitted_as_hgnc_symbol'               => $data->gene->symbol,
                'submitted_as_disease_name'              => $data->disease->name,
                'submitted_as_moi_name'                  => $data->moi->name,
                'submitted_as_submitter_name'            => $data->submitter->name,
                'submitted_as_classification_id'         => $data->classification->id,
                'submitted_as_classification_name'       => $data->classification->name,
                'submitted_as_date'                      => $data->report->display_date,
                'submitted_as_public_report_url'         => $data->report->ext_url,
                'submitted_as_notes'                     => $data->notes->display ?? '',
                'submitted_as_pmids'                     => implode(',', $evidences),
                'submitted_as_assertion_criteria_url'    => $data->criteria->url ?? ''
            ];

            $submission = Submission::create($submissionData);

            // Associate relationships
            $submission->submitter()->associate($submitter);
            $submission->gene()->associate($gene);
            $submission->disease_original()->associate($disease_original);
            $submission->disease()->associate($disease);

            // Set up disease equivalents
            $relate_options[$disease->id] = [
                'type'     => 'original',
                'ontology' => $disease->type
            ];
            foreach ($disease->equivalents as $equiv) {
                $relate_options[$equiv->id] = [
                    'type'     => 'equiv',
                    'ontology' => $equiv->type
                ];
            }
            $submission->diseases()->sync($relate_options, false);

            $submission->inheritance()->associate($moi);
            $submission->classification()->associate($classification);

            $check = $submission->save();

            Log::info("Created submission {$data->submission_id} v{$versionNumber} (is_live=true, status=published)");

            return $check ? true : "Submission " . $data->submission_id . " not saved";

        } catch (\Exception $e) {
            $sgc_id = 'UNKNOWN';
            $local_key = 'UNKNOWN';
            try {
                $data = $request->input('data');
                if (is_array($data)) {
                    $sgc_id = $data['submission_id'] ?? 'UNKNOWN';
                    $local_key = $data['local_key'] ?? 'UNKNOWN';
                } elseif (is_object($data)) {
                    $sgc_id = $data->submission_id ?? 'UNKNOWN';
                    $local_key = $data->local_key ?? 'UNKNOWN';
                }
            } catch (\Exception $inner) {
                // Ignore errors trying to extract IDs
            }

            $error = "Exception processing submission: " . $e->getMessage();
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return $error;
        }
    }

    /**
     * Unpublish a submission record.
     * Creates a new version with is_live=true, status='unpublished'.
     *
     * @param  Request  $request
     * @return bool|string True on success, error message on failure
     */
    public function unpublish_submission(Request $request)
    {
        try {
            $data = $request->input('data');

            // web1 (and probably web2) are casting inputs to arrays, so we need to cast back.
            $data = json_encode($data);
            $data = json_decode($data);

            // Get release_date from payload (serves as unpublish date)
            $releaseDate = $request->input('release_date');
            if ($releaseDate) {
                $releaseDate = Carbon::parse($releaseDate);
            } else {
                $releaseDate = Carbon::now();
            }

            // Get version_number from payload
            $versionNumber = $data->version_number ?? 1;

            $sgc_id = $data->submission_id ?? 'UNKNOWN';
            $local_key = $data->local_key ?? 'UNKNOWN';

            // Validate and look up all required entities (uses caching for static lookups)
            $lookupResult = $this->validateAndLookupEntities($data, $sgc_id, $local_key);
            if (!$lookupResult['success']) {
                return $lookupResult['error'];
            }

            // Extract validated entities
            $gene = $lookupResult['entities']['gene'];
            $disease_original = $lookupResult['entities']['disease_original'];
            $disease = $lookupResult['entities']['disease'];
            $classification = $lookupResult['entities']['classification'];
            $moi = $lookupResult['entities']['moi'];
            $submitter = $lookupResult['entities']['submitter'];

            // Archive ALL existing versions of this submission (mark as historical)
            $archivedCount = Submission::where('uuid', $data->submission_id)
                ->update([
                    'is_live' => false,       // No longer most recent
                    'is_current' => false     // Keep deprecated column in sync
                ]);

            Log::info("Archived {$archivedCount} existing version(s) of {$data->submission_id} for unpublish");

            // Repack evidence lines
            $evidences = [];
            if (isset($data->evidence) && is_array($data->evidence)) {
                foreach ($data->evidence as $evidence) {
                    if (!empty($evidence->pmid)) {
                        $evidences[] = $evidence->pmid;
                    }
                }
            }

            // Create new "unpublished" version record
            // is_live=true means this is the most recent version
            // status='unpublished' means content is hidden from public view
            $submissionData = [
                'uuid'                                   => $data->submission_id,
                'version_number'                         => $versionNumber,
                'is_live'                                => true,   // Most recent version
                'status'                                 => Submission::STATUS_UNPUBLISHED,
                'is_current'                             => false,  // Keep deprecated column in sync (false = not visible)
                'released_at'                            => $releaseDate,  // Serves as unpublish date
                'order'                                  => $classification->order,
                'submitted_run_date'                     => $releaseDate,
                'submitted_as_hgnc_id'                   => $data->gene->id,
                'submitted_as_disease_id'                => $data->disease->id,
                'submitted_as_moi_id'                    => $data->moi->id,
                'submitted_as_submitter_id'              => $data->submitter->id,
                'submitted_as_submission_id'             => $data->local_key,
                'submitted_as_hgnc_symbol'               => $data->gene->symbol,
                'submitted_as_disease_name'              => $data->disease->name,
                'submitted_as_moi_name'                  => $data->moi->name,
                'submitted_as_submitter_name'            => $data->submitter->name,
                'submitted_as_classification_id'         => $data->classification->id,
                'submitted_as_classification_name'       => $data->classification->name,
                'submitted_as_date'                      => $data->report->display_date ?? null,
                'submitted_as_public_report_url'         => $data->report->ext_url ?? '',
                'submitted_as_notes'                     => $data->notes->display ?? '',
                'submitted_as_pmids'                     => implode(',', $evidences),
                'submitted_as_assertion_criteria_url'    => $data->criteria->url ?? ''
            ];

            $submission = Submission::create($submissionData);

            // Associate relationships
            $submission->submitter()->associate($submitter);
            $submission->gene()->associate($gene);
            $submission->disease_original()->associate($disease_original);
            $submission->disease()->associate($disease);
            $submission->inheritance()->associate($moi);
            $submission->classification()->associate($classification);

            $check = $submission->save();

            Log::info("Created unpublished record for {$data->submission_id} v{$versionNumber} (is_live=true, status=unpublished)");

            return $check ? true : "Failed to create unpublished record for " . $data->submission_id;

        } catch (\Exception $e) {
            $sgc_id = 'UNKNOWN';
            $local_key = 'UNKNOWN';
            try {
                $data = $request->input('data');
                if (is_array($data)) {
                    $sgc_id = $data['submission_id'] ?? 'UNKNOWN';
                    $local_key = $data['local_key'] ?? 'UNKNOWN';
                }
            } catch (\Exception $inner) {
                // Ignore
            }

            $error = "Exception unpublishing submission: " . $e->getMessage();
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return $error;
        }
    }

    /**
     * Get all classifications keyed by curie (cached for 1 hour).
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCachedClassifications()
    {
        return Cache::remember('release_classifications', 3600, function () {
            return Classification::all()->keyBy('curie');
        });
    }

    /**
     * Get all inheritances keyed by curie (cached for 1 hour).
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCachedInheritances()
    {
        return Cache::remember('release_inheritances', 3600, function () {
            return Inheritance::all()->keyBy('curie');
        });
    }

    /**
     * Validate and look up all required entities in parallel using batched queries.
     * Returns an array with the entities or an error message.
     *
     * @param object $data
     * @param string $sgc_id
     * @param string $local_key
     * @return array ['success' => bool, 'entities' => [...] | 'error' => string]
     */
    protected function validateAndLookupEntities($data, $sgc_id, $local_key): array
    {
        // Get cached static lookups (classifications and inheritances rarely change)
        $classifications = $this->getCachedClassifications();
        $inheritances = $this->getCachedInheritances();

        // Look up classification from cache
        $classificationCurie = $data->classification->id;
        $classification = $classifications->get($classificationCurie);
        if ($classification === null) {
            $error = "Classification not found: " . $classificationCurie;
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}");
            return ['success' => false, 'error' => $error];
        }

        // Look up inheritance from cache
        $moiCurie = $data->moi->id;
        $moi = $inheritances->get($moiCurie);
        if ($moi === null) {
            $error = "Inheritance (MOI) not found: " . $moiCurie;
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}");
            return ['success' => false, 'error' => $error];
        }

        // Batch lookup for gene, disease, and submitter in parallel
        $geneCurie = $data->gene->id;
        $diseaseCurie = $data->disease->id;
        $submitterCurie = $data->submitter->id;

        // Execute lookups (these use indexed curie columns)
        $gene = Gene::curie($geneCurie)->first();
        $disease_original = Disease::curie($diseaseCurie)->first();
        $submitter = Submitter::curie($submitterCurie)->first();

        // Validate each lookup
        if ($gene === null) {
            $error = "Gene not found: " . $geneCurie;
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}");
            return ['success' => false, 'error' => $error];
        }

        if ($disease_original === null) {
            $error = "Disease not found: " . $diseaseCurie;
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}");
            return ['success' => false, 'error' => $error];
        }

        if ($submitter === null) {
            $error = "Submitter not found: " . $submitterCurie;
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}");
            return ['success' => false, 'error' => $error];
        }

        // Find MONDO equivalent for disease
        $disease = $this->findMondoEquivalent($disease_original);

        return [
            'success' => true,
            'entities' => [
                'gene' => $gene,
                'disease_original' => $disease_original,
                'disease' => $disease,
                'classification' => $classification,
                'moi' => $moi,
                'submitter' => $submitter,
            ]
        ];
    }

    /**
     * Find MONDO equivalent for a disease.
     *
     * @param Disease $disease_original
     * @return Disease
     */
    protected function findMondoEquivalent(Disease $disease_original): Disease
    {
        $isMondo = $disease_original->type === 'MONDO' || $disease_original->type === Disease::TYPE_MONDO;

        if ($isMondo) {
            Log::info("Submitted disease is MONDO: {$disease_original->curie}");
            return $disease_original;
        }

        Log::info("Submitted disease is type={$disease_original->type}: {$disease_original->curie} - looking for MONDO equivalent");

        // Try rosetta() method first
        $mondoEquivalent = Disease::rosetta($disease_original->curie);
        if ($mondoEquivalent) {
            Log::info("Found MONDO equivalent via rosetta(): {$mondoEquivalent->curie}");
            return $mondoEquivalent;
        }

        // Fallback: Look for MONDO equivalent via equivalents relationship
        foreach ($disease_original->equivalents as $equiv) {
            $equivIsMondo = $equiv->type === 'MONDO' || $equiv->type === Disease::TYPE_MONDO;
            if ($equivIsMondo) {
                Log::info("Found MONDO equivalent via equivalents: {$equiv->curie}");
                return $equiv;
            }
        }

        // If not found via equivalents, try xrefs field (legacy)
        if (!empty($disease_original->xrefs) && !is_object($disease_original->xrefs)) {
            $xrefDisease = Disease::find($disease_original->xrefs);
            $xrefIsMondo = $xrefDisease && ($xrefDisease->type === 'MONDO' || $xrefDisease->type === Disease::TYPE_MONDO);
            if ($xrefIsMondo) {
                Log::info("Found MONDO equivalent via xrefs: {$xrefDisease->curie}");
                return $xrefDisease;
            }
        }

        Log::warning("No MONDO equivalent found for {$disease_original->curie} - using submitted disease");
        return $disease_original;
    }
}
