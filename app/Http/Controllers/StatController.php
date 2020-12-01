<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Gene;
use App\Disease;
use App\Classification;
use App\Submission;
use App\Submitter;

class StatController extends Controller
{
    /**
     * Import Genes
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $genesCount = Gene::with('submissions')->has('submissions')->count();
        $diseasesCount = Disease::with('submissions')->has('submissions')->count();
        $submitters = Submitter::has('submissions');
        $submissionsCount = Submission::count();
        $classifications = Classification::with('submissions')->with('submissions')->get();
        $page_meta['seo']['title'] = "GenCC Submission Statistics";

        return view('statistics.index', ['genesCount' => $genesCount, 'diseasesCount' => $diseasesCount, 'submissionsCount' => $submissionsCount, 'classifications' => $classifications, 'page_meta' => $page_meta, 'submitter' => $submitters]);
    }
}
