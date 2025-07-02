<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AboutController extends Controller
{
    /**
     * Display the about page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_meta['seo']['title'] = "About GenCC";

        return view('about', ['page_meta' => $page_meta]);
    }
}
