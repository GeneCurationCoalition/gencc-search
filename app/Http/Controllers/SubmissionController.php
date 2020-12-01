<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Submission;

class SubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return redirect('home');
        $items = Submission::with('gene', 'disease')->paginate(5);
        $page_meta['seo']['title'] = "GenCC Submitters";
        return view('submissions.index', ['submissions' => $items, 'page_meta' => $page_meta]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $sortPram = ['classification_id', 'DESC'];
        $submission = Submission::uuid($id)->with('gene', 'disease', 'submitter')->firstOrFail();
        $page_meta['seo']['title'] = $submission->gene->title . " | " . $submission->disease->title . " | " . $submission->inheritance->title . " by " . $submission->submitter->title . " submission information facts";
        //dd($submission);
        return view('submissions.show', ['submission' => $submission, 'page' => 'submitter', 'page_meta' => $page_meta]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
