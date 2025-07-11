<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TermsController extends Controller
{
    /**
     * Display the terms page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_meta['seo']['title'] = "GenCC Terms of Use";

        return view('general.terms', ['page_meta' => $page_meta]);
    }
}