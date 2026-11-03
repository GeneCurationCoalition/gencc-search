<?php

namespace App\Http\Controllers;

class CurationFrameworksController extends Controller
{
    /**
     * Display the mechanism curation frameworks page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_meta['seo']['title'] = "GenCC Curation Frameworks";

        return view('general.curation-frameworks', ['page_meta' => $page_meta]);
    }
}
