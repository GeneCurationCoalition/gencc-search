<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Display the FAQ page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_meta['seo']['title'] = "GenCC FAQ";

        return view('general.faq', ['page_meta' => $page_meta]);
    }
}