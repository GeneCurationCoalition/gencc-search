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
     * Pair each ranked classification with its gene count, in canonical display order, so the
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
     * Limited. Any recognized classified category takes precedence over the
     * unclassified Supportive bucket for this chart only.
     *
     * Aggregated in SQL rather than in PHP: the submissions table runs to tens of
     * thousands of rows and this page is public, so loading them to group in
     * memory is not an option.
     *
     * @return array classification CURIE => number of genes, in canonical display order
     */
    private function genesByStrongestClassification()
    {
        $idsByCurie = Classification::whereIn('curie', array_keys(Classification::VOCABULARY))
            ->pluck('id', 'curie')
            ->all();
        $ranks = Classification::validityRanks();
        $ranks['GENCC:100009'] = max($ranks) + 1;
        $rankCase = $this->validityRankCaseExpression($idsByCurie, $ranks);

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

        // Back from chart-specific rank to stable CURIE, preserving display order.
        $counts = [];

        foreach ($ranks as $curie => $rank) {
            $counts[$curie] = (int) ($countsByRank[$rank] ?? 0);
        }

        return $counts;
    }

    /**
     * A CASE expression mapping classification_id to its chart-specific rank.
     */
    private function validityRankCaseExpression(array $idsByCurie, array $ranks)
    {
        $whens = '';

        foreach ($ranks as $curie => $rank) {
            if (isset($idsByCurie[$curie])) {
                $whens .= ' WHEN ' . (int) $idsByCurie[$curie] . ' THEN ' . (int) $rank;
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
