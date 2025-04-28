<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Setting;

use App\Classification;
use App\Gene;
use App\Disease;
use App\Moi;
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
                    Log::info("Submission " . $data['submission_id'] . " added");
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
            case 'end':
                // update all the counters
                Log::info("Submissions  completer, updating counts");
                Artisan::call('gencc:update-counts');
                Log::info("Counts updated");

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
        $data = $record->data;

        // confirm the required information is all present
        $gene = Gene::curie($data->gene->id)->first();
        if ($gene === null)
            return "Gene not found";

        $disease = Disease::curie($data->disease->id)->first();
        if ($disease === null)
            return "Disease not found";

        $classification = Classification::curie($data->classification->id)->first();
        if ($classification === null)
            return "Classification not found";

        $moi = Moi::curie($data->moi->id)->first();
        if ($moi === null)
            return "Inheritance not found";
    
        $submitter = Submitter::curie($data->submitter->id)->first();
        if ($submitter === null)
            return "Submitter not found";
    
        // create or update the record based on the submission-id
        $submission = Submission::updateOrCreate(
            ['uuid' => $data->submission_id],
            [
                'uuid' => $data->submission_id,
                'order'                                  => $classification->order,
                'submitted_run_date'                     => $record->publish_date,
                'submitted_as_hgnc_id'                   => $data->gene->id,
                'submitted_as_disease_id'                => $data->disease->id,
                'submitted_as_moi_id'                    => $data->moi->id,
                'submitted_as_submitter_id'              => $data->submitter->id,
                'submitted_as_submission_id'             => $data->submission_id,
                'submitted_as_hgnc_symbol'               => $data->gene->symbol,
                'submitted_as_disease_name'              => $data->disease->name,
                'submitted_as_moi_name'                  => $data->moi->name,
                'submitted_as_submitter_name'            => $data->submitter->name,
                'submitted_as_classification_id'         => $data->classification->id,
                'submitted_as_classification_name'       => $data->classification->name,
                'submitted_as_date'                      => $data->report->display_date,
                'submitted_as_public_report_url'         => $data->report->ext_url,
                'submitted_as_notes'                     => $data->notes->display,
                'submitted_as_pmids'                     => $data->evidence,
                'submitted_as_assertion_criteria_url'    => $data->criteria->url,
                'status'                                 => 1
            ]);

        // shouldn't happen but check anyway
        if ($submission === null)
            return "Submission not created";

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

        return ($check ? $check : "Submission not associated");
    }
}