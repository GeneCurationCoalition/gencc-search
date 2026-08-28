<?php

namespace App\Http\Controllers;

use App\Gene;
use App\Disease;
use App\Classification;
use App\Submission;
use App\Submitter;
use Illuminate\Support\Facades\DB;

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
        $classifications = Classification::orderCollection(
            Classification::withCount('submissions')->get()
        );
        // Show all active submitters - the view will handle displaying stats vs "Member" vs "Coming Soon"
        $submitters = Submitter::where('status', 1)->paginate(25);
        $submitterCountSummaries = Submitter::submissionCountSummariesFor($submitters->getCollection());
        $page_meta['seo']['title'] = "GenCC Submission Statistics";

        $geneCountsByClassification = $this->genesByStrongestClassification();
        $genesByClassification = $this->chartRows($classifications, $geneCountsByClassification);

        return view('statistics.index', [
            'genesCount' => $genesCount,
            'diseasesCount' => $diseasesCount,
            'submissionsCount' => $submissionsCount,
            'classifications' => $classifications,
            'genesByClassification' => $genesByClassification,
            'genesByClassificationTotal' => array_sum($geneCountsByClassification),
            'page_meta' => $page_meta,
            'submitters_with_submissions' => $submitters_with_submissions,
            'submitters' => $submitters,
            'submitterCountSummaries' => $submitterCountSummaries,
        ]);
    }

    /**
     * Pair each ranked classification with its gene count, strongest first, so the
     * view can render the bars without knowing the ranking. Unranked terms — the
     * GENCC:000000 placeholder, or a tenth term added later — are dropped.
     *
     * @return array<int, array{classification: Classification, genes_count: int}>
     */
    private function chartRows($classifications, array $geneCounts)
    {
        $rows = [];

        foreach ($classifications as $classification) {
            $metadata = $classification->vocabularyMetadata();

            $rows[$metadata['priority']] = [
                'classification' => $classification,
                'genes_count' => $geneCounts[$classification->curie] ?? 0,
            ];
        }

        ksort($rows);

        return array_values($rows);
    }

    /**
     * Count each gene once, in the bucket for its strongest assertion (#210).
     *
     * A gene with Definitive, Strong and Moderate assertions counts only towards
     * Definitive; a gene with three Limited assertions counts once towards
     * Limited. Supportive ranks between Moderate and Limited.
     *
     * Aggregated in SQL rather than in PHP: the submissions table runs to tens of
     * thousands of rows and this page is public, so loading them to group in
     * memory is not an option.
     *
     * @return array classification CURIE => number of genes, strongest first
     */
    private function genesByStrongestClassification()
    {
        $idsByCurie = Classification::whereIn('curie', array_keys(Classification::VOCABULARY))
            ->pluck('id', 'curie')
            ->all();
        $rankCase = $this->validityRankCaseExpression($idsByCurie);

        $strongestPerGene = DB::table('submissions')
            ->selectRaw("gene_id, MIN({$rankCase}) as best_rank")
            ->where('is_live', '=', true)
            ->where('status', '=', Submission::STATUS_PUBLISHED)
            ->groupBy('gene_id');

        $countsByRank = DB::query()
            ->fromSub($strongestPerGene, 'strongest')
            ->selectRaw('best_rank, COUNT(*) as genes_count')
            ->groupBy('best_rank')
            ->pluck('genes_count', 'best_rank')
            ->toArray();

        // Back from rank to stable CURIE, preserving strongest-first order.
        $counts = [];

        foreach (Classification::VOCABULARY as $curie => $metadata) {
            $counts[$curie] = (int) ($countsByRank[$metadata['priority']] ?? 0);
        }

        return $counts;
    }

    /**
     * A CASE expression mapping classification_id to its validity rank, so the
     * ranking lives in one place rather than being duplicated in SQL.
     */
    private function validityRankCaseExpression(array $idsByCurie)
    {
        $whens = '';

        foreach (Classification::VOCABULARY as $curie => $metadata) {
            if (isset($idsByCurie[$curie])) {
                $whens .= ' WHEN ' . (int) $idsByCurie[$curie] . ' THEN ' . (int) $metadata['priority'];
            }
        }

        if ($whens === '') {
            return '99';
        }

        // Anything unranked sorts last so a new term cannot outrank Definitive
        // by accident.
        return '(CASE classification_id' . $whens . ' ELSE 99 END)';
    }
}
