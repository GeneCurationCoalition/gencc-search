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
        // Use count() without eager loading to avoid memory issues
        $genesCount = Gene::has('submissions')->count();
        $diseasesCount = Disease::has('submissions')->count();
        $submitters_with_submissions = Submitter::has('submissions');
        $submissionsCount = Submission::where('is_live', '=', true)->where('status', '=', Submission::STATUS_PUBLISHED)->count();
        // Use withCount instead of with('submissions') to avoid loading all 28k+ submissions
        $classifications = Classification::withCount('submissions')->get();
        // Show all active submitters - the view will handle displaying stats vs "Member" vs "Coming Soon"
        $submitters = Submitter::where('status', 1)->paginate(25);
        $page_meta['seo']['title'] = "GenCC Submission Statistics";

        return view('statistics.index', ['genesCount' => $genesCount, 'diseasesCount' => $diseasesCount, 'submissionsCount' => $submissionsCount, 'classifications' => $classifications, 'page_meta' => $page_meta, 'submitters_with_submissions' => $submitters_with_submissions, 'submitters' => $submitters]);
    }
}
