<?php

namespace App\Http\Controllers;

use App\Classification;
use App\Submitter;
use Illuminate\Support\Facades\Cache;

class SubmitterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Don't eager load submissions - curation counts are computed via accessors
        $submitters = Submitter::where('status', 1)->paginate(25);
        $page_meta['seo']['title'] = "GenCC Members";
        return view('submitters.index', ['submitters' => $submitters, 'page' => 'submitter', 'page_meta' => $page_meta]);
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $classifications = Cache::remember('classifications_all', 3600, function () {
            return Classification::all();
        });
        $submitter = Submitter::ident($id)->firstOrFail();
        $page_meta['seo']['title'] = $submitter->title . " submitter information and submissions";
        return view('submitters.show', ['classifications' => $classifications, 'submitter' => $submitter, 'page' => 'submitter', 'page_meta' => $page_meta]);
    }


}
