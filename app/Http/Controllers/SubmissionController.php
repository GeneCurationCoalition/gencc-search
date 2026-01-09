<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Submission;

class SubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return redirect('home');
        //$items = Submission::with('gene', 'disease')->paginate(5);
        //$page_meta['seo']['title'] = "GenCC Submitters";
        //return view('submissions.index', ['submissions' => $items, 'page_meta' => $page_meta]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     * Supports both versioned (SGC-107616.2) and non-versioned (SGC-107616) IDs.
     * Non-versioned requests redirect to the versioned URL for the current version.
     *
     * Handles several scenarios:
     * 1. Current version exists and is published - show full details
     * 2. Most recent version is unpublished - show "Removed Submission" for ALL versions
     * 3. Current version exists, but viewing an explicitly unpublished previous version -
     *    show both "Previous Version" and "Removed Submission" banners
     * 4. Current version exists, viewing a superseded (not unpublished) previous version -
     *    show "Previous Version" banner with full details
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Look up submission by display ID (handles both formats)
        $submission = Submission::byDisplayId($id)->with('gene', 'disease', 'submitter')->firstOrFail();

        // If the URL doesn't include a version, redirect to the versioned URL
        if (!preg_match('/\.\d+$/', $id)) {
            return redirect()->route('submission-show', ['id' => $submission->display_id]);
        }

        // Find the most recent version of this SGC ID (highest version number)
        $mostRecentVersion = Submission::where('uuid', $submission->uuid)
            ->orderBy('version_number', 'desc')
            ->first();

        // Check if the most recent version is unpublished (not current and has unpublished_at)
        $isSgcIdUnpublished = !$mostRecentVersion->is_current && $mostRecentVersion->unpublished_at !== null;

        // Initialize view variables
        $currentVersion = null;
        $isPreviousVersion = false;
        $isExplicitlyUnpublished = false;
        $unpublishedDate = null;
        $hideDetails = false;

        if ($isSgcIdUnpublished) {
            // The entire SGC ID is unpublished - hide details for ALL versions
            $hideDetails = true;
            $isExplicitlyUnpublished = true;
            $unpublishedDate = $mostRecentVersion->unpublished_at;
        } elseif (!$submission->is_current) {
            // This is a previous version
            $isPreviousVersion = true;

            // Find the current version
            $currentVersion = Submission::where('uuid', $submission->uuid)
                ->where('is_current', true)
                ->first();

            // Check if THIS specific version was explicitly unpublished
            if ($submission->unpublished_at !== null) {
                $isExplicitlyUnpublished = true;
                $unpublishedDate = $submission->unpublished_at;
                $hideDetails = true;
            }
        }

        $page_meta['seo']['title'] = $submission->gene->title . " | " . $submission->disease->title . " | " . $submission->inheritance->title . " by " . $submission->submitter->title . " submission information facts";

        return view('submissions.show', [
            'submission' => $submission,
            'page' => 'submitter',
            'page_meta' => $page_meta,
            'currentVersion' => $currentVersion,
            'isPreviousVersion' => $isPreviousVersion,
            'isExplicitlyUnpublished' => $isExplicitlyUnpublished,
            'unpublishedDate' => $unpublishedDate,
            'hideDetails' => $hideDetails,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
