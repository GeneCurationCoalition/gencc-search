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

        // Find the most recent version of this SGC ID (is_live=true means most recent)
        $mostRecentVersion = Submission::where('uuid', $submission->uuid)
            ->where('is_live', true)
            ->first();

        // If no is_live found, fallback to highest version number (data migration edge case)
        if (!$mostRecentVersion) {
            $mostRecentVersion = Submission::where('uuid', $submission->uuid)
                ->orderBy('version_number', 'desc')
                ->first();
        }

        // Check if this SGC ID is unpublished (most recent version has status='unpublished')
        $isSgcIdUnpublished = $mostRecentVersion && $mostRecentVersion->isUnpublished();

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
            // Use released_at as the unpublish date (when the unpublish version was released)
            $unpublishedDate = $mostRecentVersion->released_at;

            // If viewing a historical version of an unpublished SGC, also show previous version banner
            if ($submission->isHistorical()) {
                $isPreviousVersion = true;
                // The "current version" is the unpublished one (no link will be shown since it's unpublished)
                $currentVersion = $mostRecentVersion;
            }
        } elseif ($submission->isHistorical()) {
            // This is a historical version (superseded by newer version)
            $isPreviousVersion = true;

            // Find the current live published version
            $currentVersion = Submission::where('uuid', $submission->uuid)
                ->where('is_live', true)
                ->where('status', Submission::STATUS_PUBLISHED)
                ->first();
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
