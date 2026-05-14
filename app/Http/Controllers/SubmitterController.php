<?php

namespace App\Http\Controllers;

use App\Classification;
use App\Submitter;

class SubmitterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $submitters = Submitter::where('status', 1)->paginate(25);
        $submitterCountSummaries = Submitter::submissionCountSummariesFor($submitters->getCollection());
        $page_meta['seo']['title'] = "GenCC Members";
        return view('submitters.index', [
            'submitters' => $submitters,
            'submitterCountSummaries' => $submitterCountSummaries,
            'page' => 'submitter',
            'page_meta' => $page_meta,
        ]);
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $classifications = Classification::all();
        $submitter = Submitter::ident($id)->firstOrFail();
        $countSummary = $submitter->submissionCountSummary();
        $classificationCounts = $countSummary['classificationCounts'];
        $submitterSubmissionsCount = $countSummary['total'];
        $page_meta['seo']['title'] = $submitter->title . " submitter information and submissions";
        return view('submitters.show', [
            'classifications' => $classifications,
            'submitter' => $submitter,
            'classificationCounts' => $classificationCounts,
            'submitterSubmissionsCount' => $submitterSubmissionsCount,
            'page' => 'submitter',
            'page_meta' => $page_meta,
        ]);
    }


}
