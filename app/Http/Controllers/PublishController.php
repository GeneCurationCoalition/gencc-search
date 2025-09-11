<?php

namespace App\Http\Controllers;

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
            Log::error("Attempt to add submission with invalid token");
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
                            'sid' => $request->input('action'),
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
                    Log::info("Submission " . $data['submission_id'] . " processed.");
                    Setting::set('update_counts', 1);
                    Setting::save();
                    return response()->json(['success' => 'true',
                                'status_code' => 200,
                                'sid' => $data['submission_id'],
                                'message' => 'Submission accepted'],
                                200);
                }
                else
                {
                    Log::error("Submission " . $data['submission_id'] . " failed with error: " . $check);
                    return response()->json(['success' => 'false',
                                'status_code' => 9007,
                                'sid' => $data['submission_id'],
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
                    Log::info("Submission " . $data['submission_id'] . " unpublished");
                    Setting::set('update_counts', 1);
                    Setting::save();

                    return response()->json(['success' => 'true',
                                'status_code' => 200,
                                'sid' => $data['submission_id'],
                                'message' => 'Submission unpublished'],
                                200);
                }
                else
                {
                    Log::error("Submission " . $data['submission_id'] . " failed removal error: " . $check);
                    return response()->json(['success' => 'false',
                                'status_code' => 9008,
                                'sid' => $data['submission_id'],
                                'message' => 'Submission remove failed:  ' . $check],
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
        $data = $record->input('data');

        // web1 (and probably web2) are casting inputs to arrays, so we need to cast back.
        $data = json_encode($data);

        $data = json_decode($data);

        // confirm the required information is all present
        if (!isset($data->sid) || empty($data->sid))
            return "SID is required";

        $gene = Gene::curie($data->gene->id)->first();
        if ($gene === null)
            return "Gene not found";

        $disease = Disease::curie($data->disease->id)->first();
        if ($disease === null)
            return "Disease not found";

        $classification = Classification::curie($data->classification->id)->first();
        if ($classification === null)
            return "Classification not found";

        $moi = Inheritance::curie($data->moi->id)->first();
        if ($moi === null)
            return "Inheritance not found";

        $submitter = Submitter::curie($data->submitter->id)->first();
        if ($submitter === null)
            return "Submitter not found";

        // repack any evidence lines
        $evidences = [];

        foreach ($data->evidence as $evidence)
            if (!empty($evidence->pmid))
                $evidences[] = $evidence->pmid;

        // Process original_data if present to get original submission values
        $originalData = null;
        if (isset($data->original_data) && !empty($data->original_data)) {
            if (is_string($data->original_data)) {
                $originalData = json_decode($data->original_data);
            } else {
                $originalData = $data->original_data;
            }
        }

        // Use original_data values for submitted_as_ fields if they exist and differ from normalized values
        $submitted_gene_id = $data->gene->id;
        $submitted_gene_symbol = $data->gene->symbol;
        $submitted_disease_id = $data->disease->id;
        $submitted_disease_name = $data->disease->name;
        $submitted_moi_id = $data->moi->id;
        $submitted_moi_name = $data->moi->name;
        $submitted_classification_id = $data->classification->id;
        $submitted_classification_name = $data->classification->name;
        $submitted_local_key = $data->local_key;

        if ($originalData !== null) {
            // Check gene values
            if (isset($originalData->gene) && !empty($originalData->gene->id) && 
                $originalData->gene->id !== $data->gene->id) {
                $submitted_gene_id = $originalData->gene->id;
            }
            if (isset($originalData->gene) && !empty($originalData->gene->symbol) && 
                $originalData->gene->symbol !== $data->gene->symbol) {
                $submitted_gene_symbol = $originalData->gene->symbol;
            }

            // Check disease values
            if (isset($originalData->disease) && !empty($originalData->disease->id) && 
                $originalData->disease->id !== $data->disease->id) {
                $submitted_disease_id = $originalData->disease->id;
            }
            if (isset($originalData->disease) && !empty($originalData->disease->name) && 
                $originalData->disease->name !== $data->disease->name) {
                $submitted_disease_name = $originalData->disease->name;
            }

            // Check moi values
            if (isset($originalData->moi) && !empty($originalData->moi->id) && 
                $originalData->moi->id !== $data->moi->id) {
                $submitted_moi_id = $originalData->moi->id;
            }
            if (isset($originalData->moi) && !empty($originalData->moi->name) && 
                $originalData->moi->name !== $data->moi->name) {
                $submitted_moi_name = $originalData->moi->name;
            }

            // Check classification values
            if (isset($originalData->classification) && !empty($originalData->classification->id) && 
                $originalData->classification->id !== $data->classification->id) {
                $submitted_classification_id = $originalData->classification->id;
            }
            if (isset($originalData->classification) && !empty($originalData->classification->name) && 
                $originalData->classification->name !== $data->classification->name) {
                $submitted_classification_name = $originalData->classification->name;
            }

            // Check local_key
            if (isset($originalData->local_key) && !empty($originalData->local_key) && 
                $originalData->local_key !== $data->local_key) {
                $submitted_local_key = $originalData->local_key;
            }
        }

        $submissionData = [
            'uuid'                                   => $data->sid,
            'order'                                  => $classification->order,
            'submitted_run_date'                     => $record->input('publish_date'),
            'submitted_as_hgnc_id'                   => $submitted_gene_id,
            'submitted_as_disease_id'                => $submitted_disease_id,
            'submitted_as_moi_id'                    => $submitted_moi_id,
            'submitted_as_submitter_id'              => $data->submitter->id,
            'submitted_as_submission_id'             => $submitted_local_key,
            'submitted_as_hgnc_symbol'               => $submitted_gene_symbol,
            'submitted_as_disease_name'              => $submitted_disease_name,
            'submitted_as_moi_name'                  => $submitted_moi_name,
            'submitted_as_submitter_name'            => $data->submitter->name,
            'submitted_as_classification_id'         => $submitted_classification_id,
            'submitted_as_classification_name'       => $submitted_classification_name,
            'submitted_as_date'                      => $data->report->display_date,
            'submitted_as_public_report_url'         => $data->report->ext_url,
            'submitted_as_notes'                     => $data->notes->display,
            'submitted_as_pmids'                     => implode(',', $evidences),
            'submitted_as_assertion_criteria_url'    => $data->criteria->url,
            'status'                                 => 1
        ];

        // Find by uuid (sid) with status = 1
        Log::info( "Looking for submission by uuid=sid: " . $data->sid);
        $submission = $submitter->submissions()->where('uuid', $data->sid)->where('status',1)->first();
        if ($submission === null) {
            Log::info( "Looking for submission by submitted_as_submission_id=local_key: " . $data->local_key);
            $submission = $submitter->submissions()->where('submitted_as_submission_id', $data->local_key)->where('status',1)->first();
        }

        if ($submission) {
            Log::info( "updating submission: " . $data->submission_id);
            $submission->update($submissionData);
        } else {
            Log::info( "creating new submission: " . $data->submission_id);
            $submission = Submission::create($submissionData);
        }

        // associate the submissions as needed
        $submission->submitter()->associate($submitter);
        $submission->gene()->associate($gene);
        $submission->disease_original()->associate($disease);
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

        $submission = $submitter->submissions()->where('uuid', $data->sid)->where('status', 1)->first();
        \Log::info('PublishController@unpublish_submission looking up uuid=sid: ' . $data->sid);
        if ($submission === null) {
            \Log::info('PublishController@unpublish_submission looking up submitted_as_submission_id=local_key: ' . $data->local_key);
            $submission = $submitter->submissions()->where('submitted_as_submission_id', $data->local_key)->where('status', 1)->first();
        }

        if ($submission === null)
            return "Submission not found";

        $check = $submission->update(['status' => 0]);

        return ($check ? $check : "Submission not removed");

    }
}
