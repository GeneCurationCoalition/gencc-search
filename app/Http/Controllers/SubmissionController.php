<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Submitter;
use App\Gene;
use App\Disease;
use App\Inheritance;
use App\Classification;

class SubmissionController extends Controller
{
    /**
     * Legacy friendly format pattern.
     * Format: GENCC_000101-HGNC_10896-OMIM_182212-HP_0000006-GENCC_100001
     * Components: {SUBMITTER}-{GENE}-{DISEASE}-{INHERITANCE}-{CLASSIFICATION}
     * Note: Colons are replaced with underscores in the friendly value.
     */
    private const LEGACY_FRIENDLY_PATTERN = '/^(GENCC_\d+)-(HGNC_\d+)-((?:OMIM|MONDO|ORPHA|Orphanet)_\d+)-(HP_\d+)-(GENCC_\d+)$/i';

    /**
     * Check if the given ID matches the legacy friendly format.
     *
     * @param string $id
     * @return bool
     */
    private function isLegacyFriendlyFormat(string $id): bool
    {
        return preg_match(self::LEGACY_FRIENDLY_PATTERN, $id) === 1;
    }

    /**
     * Parse the legacy friendly format into component CURIEs.
     * Converts underscores back to colons for CURIE format.
     *
     * @param string $friendly
     * @return array|null Array with keys: submitter_curie, gene_hgnc_id, disease_curie, inheritance_curie, classification_curie
     */
    private function parseLegacyFriendly(string $friendly): ?array
    {
        if (!preg_match(self::LEGACY_FRIENDLY_PATTERN, $friendly, $matches)) {
            return null;
        }

        // Convert underscores back to colons for proper CURIE format
        return [
            'submitter_curie' => str_replace('_', ':', $matches[1]),
            'gene_hgnc_id' => str_replace('_', ':', $matches[2]),
            'disease_curie' => str_replace('_', ':', $matches[3]),
            'inheritance_curie' => str_replace('_', ':', $matches[4]),
            'classification_curie' => str_replace('_', ':', $matches[5]),
        ];
    }

    /**
     * Find submission by legacy friendly components.
     * Looks up the live submission matching all the parsed CURIEs.
     *
     * @param array $components Parsed legacy friendly components
     * @return Submission|null
     */
    private function findSubmissionByLegacyFriendly(array $components): ?Submission
    {
        // Look up each component
        $submitter = Submitter::where('curie', $components['submitter_curie'])->first();
        if (!$submitter) {
            return null;
        }

        $gene = Gene::where('hgnc_id', $components['gene_hgnc_id'])->first();
        if (!$gene) {
            return null;
        }

        $inheritance = Inheritance::where('curie', $components['inheritance_curie'])->first();
        if (!$inheritance) {
            return null;
        }

        $classification = Classification::where('curie', $components['classification_curie'])->first();
        if (!$classification) {
            return null;
        }

        // For disease, we need to find:
        // 1. The exact disease record (for original_disease_id lookup)
        // 2. The MONDO disease that this maps to (for disease_id lookup)
        $diseaseCurie = $components['disease_curie'];

        // First, find the exact disease record (could be OMIM, ORPHA, or MONDO)
        $originalDisease = Disease::where('curie', $diseaseCurie)->first();

        // Find the MONDO disease (normalized disease_id in submissions)
        $mondoDisease = null;
        $parts = explode(':', $diseaseCurie);
        if (count($parts) === 2) {
            $type = strtolower($parts[0]);
            $id = $parts[1];

            if ($type === 'mondo') {
                // Already a MONDO curie
                $mondoDisease = $originalDisease;
            } else {
                // Search for MONDO diseases that have this as an xref
                $xrefMap = [
                    'omim' => 'omim_id',
                    'orpha' => 'orpha_id',
                    'orphanet' => 'orpha_id',
                ];
                $xrefField = $xrefMap[$type] ?? null;

                if ($xrefField) {
                    $mondoDisease = Disease::where('type', Disease::TYPE_MONDO)
                        ->whereRaw("JSON_CONTAINS(xrefs->'$.{$xrefField}', ?)", [json_encode($id)])
                        ->first();
                }
            }
        }

        // Need at least one disease match to proceed
        if (!$originalDisease && !$mondoDisease) {
            return null;
        }

        // Build query for finding the submission
        $query = Submission::where('is_live', true)
            ->where('status', Submission::STATUS_PUBLISHED)
            ->where('submitter_id', $submitter->id)
            ->where('gene_id', $gene->id)
            ->where('inheritance_id', $inheritance->id)
            ->where('classification_id', $classification->id);

        // Match by disease - submissions have disease_id (MONDO normalized) and original_disease_id
        $query->where(function ($q) use ($mondoDisease, $originalDisease) {
            if ($mondoDisease) {
                $q->where('disease_id', $mondoDisease->id);
            }
            if ($originalDisease && (!$mondoDisease || $originalDisease->id !== $mondoDisease->id)) {
                if ($mondoDisease) {
                    $q->orWhere('original_disease_id', $originalDisease->id);
                } else {
                    $q->where('original_disease_id', $originalDisease->id);
                }
            }
        });

        return $query->first();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return redirect('home');
    }

    /**
     * Determine the version state for a submission.
     * Returns view state information including whether the submission is historical,
     * unpublished, and what the current version is.
     *
     * @param Submission $submission The submission being viewed
     * @return array Version state with keys: currentVersion, isPreviousVersion, isExplicitlyUnpublished, unpublishedDate, hideDetails
     */
    private function determineVersionState(Submission $submission): array
    {
        $sgcId = $submission->sid;

        // Find the most recent version (is_live=true means most recent)
        $mostRecentVersion = Submission::where('sid', $sgcId)
            ->where('is_live', true)
            ->first();

        // Fallback to highest version number (data migration edge case)
        if (!$mostRecentVersion) {
            $mostRecentVersion = Submission::where('sid', $sgcId)
                ->orderBy('version_number', 'desc')
                ->first();
        }

        // Check if this SGC ID is unpublished (most recent version has status='unpublished')
        $isSgcIdUnpublished = $mostRecentVersion && $mostRecentVersion->isUnpublished();

        $state = [
            'currentVersion' => null,
            'isPreviousVersion' => false,
            'isExplicitlyUnpublished' => false,
            'unpublishedDate' => null,
            'hideDetails' => false,
        ];

        if ($isSgcIdUnpublished) {
            // The entire SGC ID is unpublished - hide details for ALL versions
            $state['hideDetails'] = true;
            $state['isExplicitlyUnpublished'] = true;
            $state['unpublishedDate'] = $mostRecentVersion->released_at;

            if ($submission->isHistorical()) {
                $state['isPreviousVersion'] = true;
                $state['currentVersion'] = $mostRecentVersion;
            }
        } elseif ($submission->isHistorical()) {
            // Historical version superseded by newer version
            $state['isPreviousVersion'] = true;

            // Check if THIS specific version was unpublished
            if ($submission->status === Submission::STATUS_UNPUBLISHED) {
                $state['isExplicitlyUnpublished'] = true;
                $state['hideDetails'] = true;
                $state['unpublishedDate'] = $submission->released_at;
            }

            // Find the current live published version
            $state['currentVersion'] = Submission::where('sid', $sgcId)
                ->where('is_live', true)
                ->where('status', Submission::STATUS_PUBLISHED)
                ->first();
        }

        return $state;
    }

    /**
     * Display the specified resource.
     * Supports both versioned (SGC-107616.2) and non-versioned (SGC-107616) IDs.
     * Non-versioned requests redirect to the versioned URL for the current version.
     *
     * Also supports legacy friendly format URLs like:
     * GENCC_000101-HGNC_10896-OMIM_182212-HP_0000006-GENCC_100001
     * These are redirected to the proper SGC ID URL.
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
        // Handle legacy friendly format URLs (redirect to proper SGC ID URL)
        if ($this->isLegacyFriendlyFormat($id)) {
            $components = $this->parseLegacyFriendly($id);
            if ($components) {
                $submission = $this->findSubmissionByLegacyFriendly($components);
                if ($submission) {
                    return redirect()->route('submission-show', ['id' => $submission->display_id], 301);
                }
            }
            abort(404, 'Submission not found for legacy identifier: ' . $id);
        }

        // Look up submission by display ID
        $submission = Submission::byDisplayId($id)->with('gene', 'disease', 'submitter')->firstOrFail();

        // Redirect non-versioned URLs to versioned URL
        if (!preg_match('/\.\d+$/', $id)) {
            return redirect()->route('submission-show', ['id' => $submission->display_id]);
        }

        // Determine version state (historical, unpublished, etc.)
        $versionState = $this->determineVersionState($submission);

        // Build page metadata
        $inheritanceTitle = optional($submission->inheritance)->title ?? 'N/A';
        $page_meta['seo']['title'] = $submission->gene->title . " | " . $submission->disease->title . " | " . $inheritanceTitle . " by " . $submission->submitter->title . " submission information facts";

        return view('submissions.show', array_merge([
            'submission' => $submission,
            'page' => 'submitter',
            'page_meta' => $page_meta,
        ], $versionState));
    }
}
