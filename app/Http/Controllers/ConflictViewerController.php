<?php

namespace App\Http\Controllers;

class ConflictViewerController extends Controller
{
    /**
     * Display the conflict viewer page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_meta['seo']['title'] = "Conflicting Submissions - GenCC";

        return view('conflict-viewer.index', ['page_meta' => $page_meta]);
    }
}
