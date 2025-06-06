<?php

namespace App\Http\Controllers;

use App\Classification;
use App\Gene;
use App\Submitter;
use Illuminate\Http\Request;

class SubmitterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$submitters = Submitter::whereHas('submissions')->paginate(25);
        $submitters = Submitter::where('status', 1)->paginate(25);
        $page_meta['seo']['title'] = "GenCC Submitters";
        //dd($records);
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
        $sortPram = ['classification_id', 'DESC'];
        // $classifications = Classification::with('submissions')->with('submissions')->get();
        // $submitter = Submitter::uuid($id)->with('submissions')->firstOrFail();

        $classifications = Classification::all();
        $submitter = Submitter::uuid($id)->firstOrFail();
        $page_meta['seo']['title'] = $submitter->title . " submitter information and submissions";
        return view('submitters.show', ['classifications' => $classifications, 'submitter' => $submitter, 'page' => 'submitter', 'page_meta' => $page_meta]);
    }


}