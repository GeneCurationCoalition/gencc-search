<?php

namespace App\Http\Controllers;

class MechanismFrameworksController extends Controller
{
    /**
     * Display the mechanism curation frameworks page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_meta['seo']['title'] = "GenCC Mechanism Frameworks";

        return view('general.mechanism-frameworks', ['page_meta' => $page_meta]);
    }
}
