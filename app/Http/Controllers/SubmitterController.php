<?php

namespace App\Http\Controllers;

use App\Classification;
use App\Submitter;

class SubmitterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $submitters = Submitter::where('status', 1)->paginate(25);
        $submitterCountSummaries = Submitter::submissionCountSummariesFor($submitters->getCollection());
        $page_meta['seo']['title'] = "GenCC Members";
        return view('submitters.index', [
            'submitters' => $submitters,
            'submitterCountSummaries' => $submitterCountSummaries,
            'page' => 'submitter',
            'page_meta' => $page_meta,
        ]);
    }


    /**
     * Throwaway side-by-side preview of the candidate members list layouts.
     *
     * Removed once a variant is chosen for issue #219.
     *
     * @param  string  $variant
     * @return \Illuminate\Http\Response
     */
    public function preview($variant)
    {
        $submitters = Submitter::where('status', 1)->paginate(25);
        $submitterCountSummaries = Submitter::submissionCountSummariesFor($submitters->getCollection());
        $partials = [
            '0' => 'partials.submitter.submitter-grid',
            '1' => 'partials.submitter.list-variants.variant-a',
            '2' => 'partials.submitter.list-variants.variant-b',
            '3' => 'partials.submitter.list-variants.variant-c',
        ];
        $page_meta['seo']['title'] = "GenCC Members";
        return view('submitters.preview', [
            'submitters' => $submitters,
            'submitterCountSummaries' => $submitterCountSummaries,
            'variant' => $variant,
            'variantPartial' => $partials[$variant],
            'page' => 'submitter',
            'page_meta' => $page_meta,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // If accessed via old ident-based URL, redirect to curie-based URL
        $submitter = Submitter::curie($id)->first();

        if (!$submitter) {
            $submitter = Submitter::ident($id)->firstOrFail();
            return redirect()->route('member-show', $submitter->curie, 301);
        }

        $classifications = Classification::orderCollection(Classification::all());
        $countSummary = $submitter->submissionCountSummary();
        $classificationCountsByCurie = $countSummary['classificationCountsByCurie'] ?? collect();
        $submitterSubmissionsCount = $countSummary['total'] ?? null;
        $page_meta['seo']['title'] = $submitter->title . " submitter information and submissions";
        return view('submitters.show', [
            'classifications' => $classifications,
            'submitter' => $submitter,
            'countSummary' => $countSummary,
            'classificationCountsByCurie' => $classificationCountsByCurie,
            'submitterSubmissionsCount' => $submitterSubmissionsCount,
            'page' => 'submitter',
            'page_meta' => $page_meta,
        ]);
    }


}
