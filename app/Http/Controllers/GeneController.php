<?php

namespace App\Http\Controllers;

use App\Gene;
use App\Classification;

class GeneController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_meta['seo']['title'] = "GenCC genes with classifications";
        return view('genes.index', ['page_meta' => $page_meta]);
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
        $records = Classification::with(['submissions' => function ($query) use ($item) {
            return $query->where('gene_id', '=', $item->id);
        }])->get();
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
        $records = Classification::with(['submissions' => function ($query) use ($item) {
            return $query->where('gene_id', '=', $item->id);
        }])->get();
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
        $records = Classification::with(['submissions' => function ($query) use ($item) {
            return $query->where('gene_id', '=', $item->id);
        }])->get();
        $page_meta['seo']['title'] = $item->title . " gene with submissions organized by submitter";
        return view('genes.submitter', ['gene' => $item, 'records' => $records, 'page' => 'submitter', 'page_meta' => $page_meta]);
    }
}
