<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Import Genes
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        return view('dashboards.index');
    }
}
