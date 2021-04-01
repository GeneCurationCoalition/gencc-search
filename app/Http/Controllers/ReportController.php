<?php

namespace App\Http\Controllers;

use App\Trio;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    //
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
        //$trios = Trio::paginate(25);
        $trios = Trio::all();
        //dd($trios->first());
        $page_meta['seo']['title'] = "GenCC Reports";
        return view('reports.index', ['page_meta' => $page_meta, 'trios' => $trios]);
    }
}
