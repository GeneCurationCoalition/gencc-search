<?php

namespace App\Http\Controllers;

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
        $submitter = Submitter::curie($id)->with('submissions.gene', 'submissions.disease')->firstOrFail();
        return view('administrator.submitters.show', ['submitter' => $submitter]);
    }
}
