<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrivacyController extends Controller
{
    /**
     * Display the privacy page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_meta['seo']['title'] = "GenCC Website Privacy Policy";

        return view('general.privacy', ['page_meta' => $page_meta]);
    }
}
