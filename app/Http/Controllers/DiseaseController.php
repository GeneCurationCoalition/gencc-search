<?php

namespace App\Http\Controllers;

use App\Disease;
use App\Classification;

class DiseaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $items = Disease::has('submissions')->orderBy('name')->paginate(25);
        return view('diseases.index', ['diseases' => $items]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = Disease::curie($id)->with('submissions.gene', 'submissions.disease')->firstOrFail();
        $classifications = Classification::all();
        return view('diseases.show', ['disease' => $item, 'classifications' => $classifications]);
    }
}
