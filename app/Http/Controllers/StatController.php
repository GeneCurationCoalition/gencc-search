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
        $submitters_with_submissions = Submitter::has('submissions');
        $submissionsCount = Submission::where('status', '=', 1)->count();
        $classifications = Classification::with('submissions')->get();
        $submitters = Submitter::where('status', 1)->paginate(25);
        $page_meta['seo']['title'] = "GenCC Submission Statistics";

        return view('statistics.index', ['genesCount' => $genesCount, 'diseasesCount' => $diseasesCount, 'submissionsCount' => $submissionsCount, 'classifications' => $classifications, 'page_meta' => $page_meta, 'submitters_with_submissions' => $submitters_with_submissions, 'submitters' => $submitters]);
    }
}
