<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Setting;
use Artisan;

use App\Classification;
use App\Gene;
use App\Disease;
use App\Inheritance;
use App\Submitter;
use App\Submission;
use Carbon\Carbon;

class PublishController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }


    /**
     * Process an incoming request.
     *
     * @param  object  $record
     * @return \Illuminate\Http\Response
     */
    public function process(Request $request)
    {
        // Log::info("Received request: " . $request);

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

        // Log::info("Setting token: " . $ptoken . " Input token: " . $request->input('token') . " Input action: " . $request->input('action'));
        if ($ptoken === false || $request->input('token') != $ptoken)
        {
            $data = $request->input('data');
            $sgc_id = 'UNKNOWN';
            $local_key = 'UNKNOWN';
            $action = $request->input('action', 'UNKNOWN');
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
                'raw_body_length' => strlen($raw_body),
                'raw_body_preview' => substr($raw_body, 0, 500)
            ]);
            return response()->json(['success' => 'false',
                        'status_code' => 9001,
                        'message' => 'No auth'],
                        501);
        }

        switch ($request->input('action'))
        {
            case 'init':
                // we've already checked above, so just respond
                return response()->json(['success' => 'true',
                            'status_code' => 200,
                            'action' => $request->input('action'),
                            'message' => 'Ready for jobs'],
                            200);
                break;
            case 'publish':
                // add submission to the db
                $data = $request->input('data');

                $check = $this->process_submission($request);

                // respond accordingly
                if ($check === true)
                {
                    Log::info("Submission " . $data['local_key'] . " processed.");
                    Setting::set('update_counts', 1);
                    Setting::save();
                    return response()->json(['success' => 'true',
                                'status_code' => 200,
                                'sid' => $data['local_key'],
                                'message' => 'Submission accepted'],
                                200);
                }
                else
                {
                    return response()->json(['success' => 'false',
                                'status_code' => 9007,
                                'sid' => $data['local_key'],
                                'message' => 'Submission failed:  ' . $check],
                                501);
                }
                break;
            case 'unpublish':
                // remove submission from db
                $data = $request->input('data');

                $check = $this->unpublish_submission($request);

                if ($check === true)
                {
                    Log::info("Submission " . $data['local_key'] . " unpublished");
                    Setting::set('update_counts', 1);
                    Setting::save();

                    return response()->json(['success' => 'true',
                                'status_code' => 200,
                                'sid' => $data['local_key'],
                                'message' => 'Submission unpublished'],
                                200);
                }
                else
                {
                    return response()->json(['success' => 'false',
                                'status_code' => 9008,
                                'sid' => $data['local_key'],
                                'message' => 'Submission remove failed:  ' . $check],
                                501);
                }
                break;
            case 'sgc_id':
                // Update the sgc_id of a submission from sub
                $data = $request->input('data');

                $check = $this->update_sgc_id($request);
                if ($check === true) {
                    Log::info("Submission " . $data['local_key'] . " sgc_id updated.");
                    return response()->json(['success' => 'true',
                        'status_code' => 200,
                        'sid' => $data['local_key'],
                        'message' => 'SGC ID updated'],
                        200);
                } else {
                    Log::error("Submission " . $data['local_key'] . " failed sgc_id update error.");
                    return response()->json(['success' => 'false',
                        'status_code' => 9009,
                        'message' => 'SGC ID update failed: ' . $check],
                        501);
                }
                break;

            case 'end':
                // update all the counters
                Log::info("Remote Session completed");
                //Artisan::call('gencc:update-counts');
                //Log::info("Counts updated");

                return response()->json(['success' => 'true',
                            'status_code' => 200,
                            'message' => 'Session complete'],
                            200);
                break;
            case 'addsubmitter':
            case 'delsubmitter':
            case 'modsubmitter':
            default:
                break;
        }

        return response()->json(['success' => 'false',
                'status_code' => 9011,
                'message' => 'Unknown command'],
                200);
    }


    /**
     * Process a submission record.
     *
     * @param  object  $record
     * @return \Illuminate\Http\Response
     */
    public function process_submission($record)
    {
        try {
            $data = $record->input('data');

            // web1 (and probably web2) are casting inputs to arrays, so we need to cast back.
            $data = json_encode($data);

            $data = json_decode($data);

            // Get SGC ID early for error logging
            $sgc_id = isset($data->submission_id) ? $data->submission_id : 'UNKNOWN';
            $local_key = isset($data->local_key) ? $data->local_key : 'UNKNOWN';

        // confirm the required information is all present
        $gene = Gene::curie($data->gene->id)->first();
        if ($gene === null) {
            $error = "Gene not found: " . $data->gene->id;
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}");
            return $error;
        }

        // Get the submitted disease curie from the payload
        // This comes from submission_data->disease->id in gencc-sub
        $submittedDiseaseCurie = $data->disease->id;

        Log::info("Processing disease - Submitted curie: {$submittedDiseaseCurie}");

        // Find the submitted disease record
        $disease_original = Disease::curie($submittedDiseaseCurie)->first();
        if ($disease_original === null) {
            $error = "Disease not found: " . $submittedDiseaseCurie;
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}");
            return $error;
        }

        // Now determine disease_id based on the submitted disease type
        // Support both old string types ('MONDO', 'OMIM', etc.) and new integer type constants
        $isMondo = $disease_original->type === 'MONDO' || $disease_original->type === Disease::TYPE_MONDO;

        if ($isMondo) {
            // Submitted disease is MONDO - use it for both disease_id and disease_original_id
            $disease = $disease_original;
            Log::info("Submitted disease is MONDO: {$disease->curie} - using for both disease_id and disease_original_id");
        } else {
            // Submitted disease is Orphanet or OMIM - find MONDO equivalent for disease_id
            Log::info("Submitted disease is type={$disease_original->type}: {$disease_original->curie} - looking for MONDO equivalent");

            // Try the new rosetta() method first (uses mondo_id FK)
            $mondoEquivalent = Disease::rosetta($disease_original->curie);

            // If rosetta found a result, use it
            if ($mondoEquivalent) {
                Log::info("Found MONDO equivalent via rosetta(): {$mondoEquivalent->curie}");
            } else {
                // Fallback: Look for MONDO equivalent via legacy equivalents relationship
                foreach ($disease_original->equivalents as $equiv) {
                    $equivIsMondo = $equiv->type === 'MONDO' || $equiv->type === Disease::TYPE_MONDO;
                    if ($equivIsMondo) {
                        $mondoEquivalent = $equiv;
                        Log::info("Found MONDO equivalent via equivalents: {$equiv->curie}");
                        break;
                    }
                }

                // If not found via equivalents, try xrefs field (legacy)
                if (!$mondoEquivalent && !empty($disease_original->xrefs)) {
                    // Handle both old pipe-delimited format and new JSON format
                    if (is_object($disease_original->xrefs)) {
                        // New JSON format - can't directly find MONDO from xrefs
                    } else {
                        // Old format - xrefs was a disease ID
                        $xrefDisease = Disease::find($disease_original->xrefs);
                        $xrefIsMondo = $xrefDisease && ($xrefDisease->type === 'MONDO' || $xrefDisease->type === Disease::TYPE_MONDO);
                        if ($xrefIsMondo) {
                            $mondoEquivalent = $xrefDisease;
                            Log::info("Found MONDO equivalent via xrefs: {$xrefDisease->curie}");
                        }
                    }
                }
            }

            if ($mondoEquivalent) {
                $disease = $mondoEquivalent;
                Log::info("Mapping: disease_original_id={$disease_original->curie}, disease_id={$disease->curie}");
            } else {
                // No MONDO equivalent found - use submitted disease for both
                $disease = $disease_original;
                Log::warning("No MONDO equivalent found for {$disease_original->curie} - using submitted disease for both IDs");
            }
        }

        $classification = Classification::curie($data->classification->id)->first();
        if ($classification === null) {
            $error = "Classification not found: " . $data->classification->id;
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}");
            return $error;
        }

        $moi = Inheritance::curie($data->moi->id)->first();
        if ($moi === null) {
            $error = "Inheritance (MOI) not found: " . $data->moi->id;
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}");
            return $error;
        }

        $submitter = Submitter::curie($data->submitter->id)->first();
        if ($submitter === null) {
            $error = "Submitter not found: " . $data->submitter->id;
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}");
            return $error;
        }

        // repack any evidence lines
        $evidences = [];

        foreach ($data->evidence as $evidence)
            if (!empty($evidence->pmid))
                $evidences[] = $evidence->pmid;

        // Find the current version of this submission (if any exists)
        // Use is_current=TRUE to find the active version
        Log::info("Looking for current submission by uuid: " . $data->submission_id);
        $currentSubmission = $submitter->submissions()
            ->where('uuid', $data->submission_id)
            ->where('is_current', true)
            ->first();

        // Determine the version number for this submission
        $versionNumber = 1;
        if ($currentSubmission) {
            // Mark the previous version as no longer current
            $currentSubmission->is_current = false;
            $currentSubmission->save();

            // Increment version number
            $versionNumber = ($currentSubmission->version_number ?? 1) + 1;
            Log::info("Superseding submission {$data->submission_id} v{$currentSubmission->version_number} with v{$versionNumber}");
        } else {
            // Check if there are any previous versions (could be republishing after unpublish)
            $maxVersion = $submitter->submissions()
                ->where('uuid', $data->submission_id)
                ->max('version_number');
            if ($maxVersion) {
                $versionNumber = $maxVersion + 1;
                Log::info("Creating new version {$versionNumber} for previously unpublished submission {$data->submission_id}");
            } else {
                Log::info("Creating new submission: " . $data->submission_id);
            }
        }

        // All submitted_as_* fields come directly from the payload data
        // gencc-sub sends submission_data values which represent what was submitted
        $submissionData = [
            'uuid'                                   => $data->submission_id,
            'version_number'                         => $versionNumber,
            'is_current'                             => true,
            'order'                                  => $classification->order,
            'submitted_run_date'                     => $record->input('publish_date'),
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
            'submitted_as_notes'                     => $data->notes->display,
            'submitted_as_pmids'                     => implode(',', $evidences),
            'submitted_as_assertion_criteria_url'    => $data->criteria->url
        ];

        // Always create a new record for versioning (immutable records)
        $submission = Submission::create($submissionData);

        // associate the submissions as needed
        $submission->submitter()->associate($submitter);
        $submission->gene()->associate($gene);
        $submission->disease_original()->associate($disease_original);
        $submission->disease()->associate($disease);

        // set up the equivs.
        $relate_options[$disease->id] = [
            'type'          => 'original',
            'ontology'      => $disease->type
        ];
        foreach ($disease->equivalents as $eqivs) {
            $relate_options[$eqivs->id] = [
                'type'          => 'equiv',
                'ontology'      => $eqivs->type
            ];
        }
        $submission->diseases()->sync($relate_options, false);

        $submission->inheritance()->associate($moi);
        $submission->classification()->associate($classification);

            $check = $submission->save();

            return ($check ? $check : "Submission " . $data->submission_id . " not associated");
        } catch (\Exception $e) {
            // Extract SGC ID from data if available, otherwise use UNKNOWN
            $sgc_id = 'UNKNOWN';
            $local_key = 'UNKNOWN';
            try {
                $data = $record->input('data');
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
     * Sets is_current=FALSE and unpublished_at=now() to mark explicit unpublish.
     *
     * @param  object  $record
     * @return \Illuminate\Http\Response
     */
    public function unpublish_submission($record)
    {
        $data = $record->input('data');

        // web1 (and probably web2) are casting inputs to arrays, so we need to cast back.
        $data = json_encode($data);

        $data = json_decode($data);

        $submitter = Submitter::curie($data->submitter->id)->first();
        if ($submitter === null)
            return "Submitter not found";

        // Find the current version of this submission using is_current flag
        $submission = $submitter->submissions()
            ->where('uuid', $data->submission_id)
            ->where('is_current', true)
            ->first();
        \Log::info('PublishController@unpublish_submission looking up uuid=submission_id: ' . $data->submission_id);

        if ($submission === null) {
            \Log::info('PublishController@unpublish_submission looking up submitted_as_submission_id=local_key: ' . $data->local_key);
            $submission = $submitter->submissions()
                ->where('submitted_as_submission_id', $data->local_key)
                ->where('is_current', true)
                ->first();
        }

        if ($submission === null)
            return "Submission not found";

        // Mark as explicitly unpublished with timestamp
        // is_current=FALSE indicates no longer active
        // unpublished_at timestamp indicates explicit unpublish (not just superseded)
        $check = $submission->update([
            'is_current' => false,
            'unpublished_at' => Carbon::now()
        ]);

        \Log::info("Unpublished submission {$submission->uuid} v{$submission->version_number}");

        return ($check ? $check : "Submission not removed");

    }

    /**
     * Update the sgc_id associated with a record originally sent from search
     * added to the submission portal. SGC_IDs are created
     * in the submission portal on creation and is the only unique id we
     * have between the two independent systems.
     */
    public function update_sgc_id($record) {
        $data = $record->input('data');
        $data = json_encode($data);
        $data = json_decode($data);
        // unique find by db row id
        $submission = Submission::find($data->search_row_id);
        if ($submission === null) {
            Log::error("Submission with local_key " . $data->local_key . " not found error updating sgc_id: " . $data->search_row_id);
            throw new Exception("Submission not found");
        }
        Log::info("Submission with local_key " . $data->local_key . " updated with sgc_id (submission_id): " . $data->submission_id);
        $submission->uuid = $data->submission_id;
        $check = $submission->save();
        return ($check ? true : "Failed to save submission with local_key " . $data->local_key . " with sgc_id: " . $data->search_row_id);
    }
}
