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

        // Get original_data from the top level of the request, not from inside data
        $original_data = $record->input('original_data');

        // If original_data is a JSON string, decode it as object
        if ($original_data && is_string($original_data)) {
            $original_data = json_decode($original_data);
        }

        // If original_data is an array, convert it to object (Laravel may cast it to array)
        if ($original_data && is_array($original_data)) {
            $original_data = json_decode(json_encode($original_data));
        }

        // Attach original_data to the data object for use in the rest of the method
        $data->original_data = $original_data;

        // confirm the required information is all present
        $gene = Gene::curie($data->gene->id)->first();
        if ($gene === null) {
            $error = "Gene not found: " . $data->gene->id;
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}");
            return $error;
        }

        $disease = Disease::curie($data->disease->id)->first();
        if ($disease === null) {
            $error = "Disease not found: " . $data->disease->id;
            Log::error("SGC-ID: {$sgc_id}, Local-Key: {$local_key} - {$error}");
            return $error;
        }

        // Check for original disease data
        $disease_original = null;
        if (isset($data->original_data) && isset($data->original_data->disease) && isset($data->original_data->disease->id)) {
            $disease_original = Disease::curie($data->original_data->disease->id)->first();
            // If original disease not found, log warning but continue (use normalized disease as fallback)
            if ($disease_original === null) {
                Log::warning("Original disease not found: " . $data->original_data->disease->id . ", using normalized disease as fallback");
                $disease_original = $disease;
            }
        } else {
            // No original_data provided, use normalized disease
            $disease_original = $disease;
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

        // Use original_data for submitted_as_* fields when available, otherwise fall back to normalized data
        $original = isset($data->original_data) ? $data->original_data : $data;

        $submissionData = [
            'uuid'                                   => $data->submission_id,
            'order'                                  => $classification->order,
            'submitted_run_date'                     => $record->input('publish_date'),
            'submitted_as_hgnc_id'                   => isset($original->gene->id) ? $original->gene->id : $data->gene->id,
            'submitted_as_disease_id'                => isset($original->disease->id) ? $original->disease->id : $data->disease->id,
            'submitted_as_moi_id'                    => isset($original->moi->id) ? $original->moi->id : $data->moi->id,
            'submitted_as_submitter_id'              => isset($original->submitter->id) ? $original->submitter->id : $data->submitter->id,
            'submitted_as_submission_id'             => $data->local_key,
            'submitted_as_hgnc_symbol'               => isset($original->gene->symbol) ? $original->gene->symbol : $data->gene->symbol,
            'submitted_as_disease_name'              => isset($original->disease->name) ? $original->disease->name : $data->disease->name,
            'submitted_as_moi_name'                  => isset($original->moi->name) ? $original->moi->name : $data->moi->name,
            'submitted_as_submitter_name'            => isset($original->submitter->name) ? $original->submitter->name : $data->submitter->name,
            'submitted_as_classification_id'         => isset($original->classification->id) ? $original->classification->id : $data->classification->id,
            'submitted_as_classification_name'       => isset($original->classification->name) ? $original->classification->name : $data->classification->name,
            'submitted_as_date'                      => $data->report->display_date,
            'submitted_as_public_report_url'         => $data->report->ext_url,
            'submitted_as_notes'                     => $data->notes->display,
            'submitted_as_pmids'                     => implode(',', $evidences),
            'submitted_as_assertion_criteria_url'    => $data->criteria->url,
            'status'                                 => 1
        ];

        // Find by uuid = submission_id (which is SGC-id from gencc-sub) regardless of status
        // This prevents duplicate records when re-publishing a soft-deleted submission
        Log::info( "Looking for submission by uuid=submission_id: " . $data->submission_id);
        $submission = $submitter->submissions()->where('uuid', $data->submission_id)->first();

        if ($submission) {
            Log::info( "updating submission: " . $data->submission_id . " (previous status: " . $submission->status . ")");
            $submission->update($submissionData);
        } else {
            Log::info( "creating new submission: " . $data->submission_id);
            $submission = Submission::create($submissionData);
        }

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

        $submission = $submitter->submissions()->where('uuid', $data->submission_id)->where('status', 1)->first();
        \Log::info('PublishController@unpublish_submission looking up uuid=submission_id: ' . $data->submission_id);
        if ($submission === null) {
            \Log::info('PublishController@unpublish_submission looking up submitted_as_submission_id=local_key: ' . $data->local_key);
            $submission = $submitter->submissions()->where('submitted_as_submission_id', $data->local_key)->where('status', 1)->first();
        }

        if ($submission === null)
            return "Submission not found";

        $check = $submission->update(['status' => 0]);

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
