<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DownloadController extends Controller
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
        $page_meta['seo']['title'] = "Download GenCC Data";
        return view('download.index', ['page_meta' => $page_meta]);
    }
}
