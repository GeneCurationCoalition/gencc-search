<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Gene;
use App\Classification;
use App\Disease;
use App\Submitter;
use Illuminate\Support\Facades\Log;

class GeneController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        //Log::channel('slack')->info('Something happened!');
        //
        //$items = Gene::has('submissions')->orderBy('title')->paginate(10);
        //dd($items);
        //return view('genes.index', ['genes' => $items]);
        $page_meta['seo']['title'] = "GenCC genes with classifications";
        return view('genes.index', ['page_meta' => $page_meta]);
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
        $item = Gene::curie($id)->firstOrFail();
        //$classifications = Classification::with('submissions')->get()->sortBy('order');
        // $classifications = Classification::whereHas('submissions', function ($query) use ($item) {
        //     return $query->where('gene_id', '=', $item->id);
        // })->get();
        //dd($classifications);
        $records = Classification::with(['submissions' => function ($query) use ($item) {
            return $query->where('gene_id', '=', $item->id);
        }])->get();
        //dd($classifications);
        $page_meta['seo']['title'] = $item->title . " gene with submissions organized by classifications";
        return view('genes.show', ['gene' => $item, 'records' => $records, 'page' => 'show', 'page_meta' => $page_meta]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function disease($id)
    {
        $item = Gene::curie($id)->firstOrFail();
        // $records = Disease::whereHas('submissions', function ($query) use ($item) {
        //     return $query->where('gene_id', '=', $item->id);
        // })->where('type', 'MONDO')->get();
        $records = Classification::with(['submissions' => function ($query) use ($item) {
            return $query->where('gene_id', '=', $item->id);
        }])->get();
        //dd($classifications);
        $page_meta['seo']['title'] = $item->title . " gene with submissions organized by disease";
        return view('genes.disease', ['gene' => $item, 'records' => $records, 'page' => 'disease', 'page_meta' => $page_meta]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function submitter($id)
    {
        $item = Gene::curie($id)->firstOrFail();
        // $records = Submitter::with(['submissions' => function ($query) use ($item) {
        //     return $query->where('gene_id', '=', $item->id);
        // }])->get();
        $records = Classification::with(['submissions' => function ($query) use ($item) {
            return $query->where('gene_id', '=', $item->id);
        }])->get();
        $page_meta['seo']['title'] = $item->title . " gene with submissions organized by submitter";
        //dd($classifications);
        return view('genes.submitter', ['gene' => $item, 'records' => $records, 'page' => 'submitter', 'page_meta' => $page_meta]);
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
