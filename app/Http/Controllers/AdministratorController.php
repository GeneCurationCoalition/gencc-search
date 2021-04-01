<?php

namespace App\Http\Controllers;

use App\Submission;
use App\SubmissionFile;
use App\Submitter;
use Illuminate\Http\Request;

class AdministratorController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Import Genes
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $submitters = Submitter::all();
        return view('administrator.submitters.index', ['submitters' => $submitters]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //dd($id);
        $submitter = Submitter::where('curie', '=', $id)->with('submissions.gene', 'submissions.disease')->firstOrFail();
        //$submission = Submission::where('uuid', '=', $submission)->with('gene', 'disease', 'submitter')->firstOrFail();

        return view('administrator.submitters.show', ['submitter' => $submitter]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function profile($id)
    {
        $submitter = Submitter::curie($id)->with('submissions.gene', 'submissions.disease')->firstOrFail();
        return view('administrator.submitters.edit-profile', ['submitter' => $submitter]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function submitterCreate()
    {

        //dd($newCurie);
        return view('administrator.submitters.edit-profile', ['submitter' => null]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function files($id)
    {
        $submitter = Submitter::curie($id)->with('submissions.gene', 'submissions.disease')->firstOrFail();
        return view('administrator.submitters.show-files', ['submitter' => $submitter]);
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function file($id, $file)
    {
        $file = SubmissionFile::uuid($file)->with('submitter')->firstOrFail();
        return view('administrator.submitters.edit-file', ['file' => $file]);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function submission($id, $submission)
    {
        //dd($submission);
        //$submitter = Submitter::curie($id)->with('submissions.gene', 'submissions.disease')->firstOrFail();
        $submission = Submission::where('uuid', '=', $submission)->with('gene', 'disease', 'submitter')->firstOrFail();
        //dd($submission);
        return view('administrator.submitters.edit-submission', ['submission' => $submission]);
    }
}
