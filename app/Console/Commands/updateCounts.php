<?php

namespace App\Console\Commands;

use App\Disease;
use App\Gene;
use App\Notification;
use Illuminate\Console\Command;

use App\Submitter;
use App\Submission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class updateCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gencc:update-counts {force=no} {--ref=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '#5 Update counts for submissions, genes, diseases, etc...';

    /**
     * Classification slug to column mapping
     */
    protected $classificationColumns = [
        'definitive' => 'curations_definitive',
        'strong' => 'curations_strong',
        'moderate' => 'curations_moderate',
        'limited' => 'curations_limited',
        'disputed' => 'curations_disputed',
        'refuted' => 'curations_refuted',
        'animal-model-only' => 'curations_animal',
        'no-known' => 'curations_noknown',
        'supportive' => 'curations_supportive',
        'nul' => 'curations_nul',
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $argument = $this->argument('force');
        $options = $this->options();
        $refUuid = ($this->option('ref') ?? Str::uuid());

        $notification = Notification::create(
            [
                'ref'           => $refUuid,
                'type'          => 3,
                'status'        => 1,
                'running'       => 1,
                'label'         => "Submission Processing",
                'message'       => "Processing all submissions"
            ]
        );

        $value = DB::table('settings')
            ->where('key', 'running_counts')
            ->value('value');  // returns the single column value
        $runningCounts = (int) $value;

        if ($runningCounts == 1)
        {
            print('Another update is running, exiting');
            return -1;
        }

        $value = DB::table('settings')
            ->where('key', 'update_counts')
            ->value('value');  // returns the single column value
        $updateCounts = (int) $value;
        if ( $updateCounts == 0 && $argument != 'yes')
        {
            print('There are no updates pending, exiting');
            return -1;
        }

        // Using query builder queries for settings as previous version of code
        // using l4-settings had defect where explicit updates to Settings were
        // not always working
        DB::beginTransaction();
        DB::table('settings')->updateOrInsert(
            ['key' => 'running_counts', 'value' => 0],
            ['value' => 1]);
        DB::table('settings')->updateOrInsert(
            ['key' => 'update_counts'],
            ['value' => 0]);
        DB::commit();

        $startTime = microtime(true);

        // Update Gene counts using SQL aggregation
        $this->updateGeneCounts();

        // Update Submitter counts using SQL aggregation
        $this->updateSubmitterCounts();

        // Update Disease counts using SQL aggregation
        $this->updateDiseaseCounts();

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->line("Processing completed in {$elapsed} seconds");

        DB::beginTransaction();
        DB::table('settings')->where('key', 'running_counts')->update(['value' => 0]);
        DB::commit();

        Log::channel('slack')->info('Submission Import Completed');
        $notification->status = 0;
        $notification->running = 0;
        $notification->save();


        return 0;
    }

    /**
     * Update gene counts using SQL aggregation.
     */
    protected function updateGeneCounts()
    {
        $this->line('Updating Gene Counts... ');
        Log::channel('slack')->info('Updating Gene Counts... ');

        // First, reset all gene counts to 0
        Gene::query()->update([
            'curations_definitive' => 0,
            'curations_strong' => 0,
            'curations_moderate' => 0,
            'curations_limited' => 0,
            'curations_disputed' => 0,
            'curations_refuted' => 0,
            'curations_animal' => 0,
            'curations_noknown' => 0,
            'curations_supportive' => 0,
            'curations_nul' => 0,
            'count_submissions' => 0,
            'count_unique_submitters' => 0,
            'count_unique_diseases' => 0,
        ]);

        // Get classification counts per gene using SQL aggregation
        // Only count live (is_live=true) AND published (status='published') submissions
        $classificationCounts = DB::table('submissions')
            ->join('classifications', 'submissions.classification_id', '=', 'classifications.id')
            ->select(
                'submissions.gene_id',
                'classifications.slug',
                DB::raw('COUNT(*) as count')
            )
            ->where('submissions.is_live', true)
            ->where('submissions.status', Submission::STATUS_PUBLISHED)
            ->groupBy('submissions.gene_id', 'classifications.slug')
            ->get();

        // Build update data grouped by gene_id
        $geneUpdates = [];
        foreach ($classificationCounts as $row) {
            if (!isset($geneUpdates[$row->gene_id])) {
                $geneUpdates[$row->gene_id] = [];
            }
            if (isset($this->classificationColumns[$row->slug])) {
                $geneUpdates[$row->gene_id][$this->classificationColumns[$row->slug]] = $row->count;
            }
        }

        // Get total submission counts per gene
        $submissionCounts = DB::table('submissions')
            ->select('gene_id', DB::raw('COUNT(*) as count'))
            ->where('is_live', true)
            ->where('status', Submission::STATUS_PUBLISHED)
            ->groupBy('gene_id')
            ->get();

        foreach ($submissionCounts as $row) {
            if (!isset($geneUpdates[$row->gene_id])) {
                $geneUpdates[$row->gene_id] = [];
            }
            $geneUpdates[$row->gene_id]['count_submissions'] = $row->count;
        }

        // Get unique submitter counts per gene
        $uniqueSubmitters = DB::table('submissions')
            ->select('gene_id', DB::raw('COUNT(DISTINCT submitter_id) as count'))
            ->where('is_live', true)
            ->where('status', Submission::STATUS_PUBLISHED)
            ->groupBy('gene_id')
            ->get();

        foreach ($uniqueSubmitters as $row) {
            if (!isset($geneUpdates[$row->gene_id])) {
                $geneUpdates[$row->gene_id] = [];
            }
            $geneUpdates[$row->gene_id]['count_unique_submitters'] = $row->count;
        }

        // Get unique disease counts per gene
        $uniqueDiseases = DB::table('submissions')
            ->select('gene_id', DB::raw('COUNT(DISTINCT disease_id) as count'))
            ->where('is_live', true)
            ->where('status', Submission::STATUS_PUBLISHED)
            ->groupBy('gene_id')
            ->get();

        foreach ($uniqueDiseases as $row) {
            if (!isset($geneUpdates[$row->gene_id])) {
                $geneUpdates[$row->gene_id] = [];
            }
            $geneUpdates[$row->gene_id]['count_unique_diseases'] = $row->count;
        }

        // Batch update genes
        foreach ($geneUpdates as $geneId => $updates) {
            Gene::where('id', $geneId)->update($updates);
        }

        $this->line('Gene Counts Completed (' . count($geneUpdates) . ' genes updated)');
        Log::channel('slack')->info('Gene Counts Completed...');
    }

    /**
     * Update submitter counts using SQL aggregation.
     */
    protected function updateSubmitterCounts()
    {
        $this->line('Updating Submitter Submission Counts... ');
        Log::channel('slack')->info('Updating Submitter Submission Counts...');

        // First, reset all submitter counts to 0
        Submitter::query()->update([
            'curations_definitive' => 0,
            'curations_strong' => 0,
            'curations_moderate' => 0,
            'curations_limited' => 0,
            'curations_disputed' => 0,
            'curations_refuted' => 0,
            'curations_animal' => 0,
            'curations_noknown' => 0,
            'curations_supportive' => 0,
            'curations_nul' => 0,
            'count_submissions' => 0,
            'count_unique_genes' => 0,
            'count_unique_diseases' => 0,
        ]);

        // Get classification counts per submitter using SQL aggregation
        // Only count live (is_live=true) AND published (status='published') submissions
        $classificationCounts = DB::table('submissions')
            ->join('classifications', 'submissions.classification_id', '=', 'classifications.id')
            ->select(
                'submissions.submitter_id',
                'classifications.slug',
                DB::raw('COUNT(*) as count')
            )
            ->where('submissions.is_live', true)
            ->where('submissions.status', Submission::STATUS_PUBLISHED)
            ->groupBy('submissions.submitter_id', 'classifications.slug')
            ->get();

        // Build update data grouped by submitter_id
        $submitterUpdates = [];
        foreach ($classificationCounts as $row) {
            if (!isset($submitterUpdates[$row->submitter_id])) {
                $submitterUpdates[$row->submitter_id] = [];
            }
            if (isset($this->classificationColumns[$row->slug])) {
                $submitterUpdates[$row->submitter_id][$this->classificationColumns[$row->slug]] = $row->count;
            }
        }

        // Get total submission counts per submitter
        $submissionCounts = DB::table('submissions')
            ->select('submitter_id', DB::raw('COUNT(*) as count'))
            ->where('is_live', true)
            ->where('status', Submission::STATUS_PUBLISHED)
            ->groupBy('submitter_id')
            ->get();

        foreach ($submissionCounts as $row) {
            if (!isset($submitterUpdates[$row->submitter_id])) {
                $submitterUpdates[$row->submitter_id] = [];
            }
            $submitterUpdates[$row->submitter_id]['count_submissions'] = $row->count;
        }

        // Get unique gene counts per submitter
        $uniqueGenes = DB::table('submissions')
            ->select('submitter_id', DB::raw('COUNT(DISTINCT gene_id) as count'))
            ->where('is_live', true)
            ->where('status', Submission::STATUS_PUBLISHED)
            ->groupBy('submitter_id')
            ->get();

        foreach ($uniqueGenes as $row) {
            if (!isset($submitterUpdates[$row->submitter_id])) {
                $submitterUpdates[$row->submitter_id] = [];
            }
            $submitterUpdates[$row->submitter_id]['count_unique_genes'] = $row->count;
        }

        // Get unique disease counts per submitter
        $uniqueDiseases = DB::table('submissions')
            ->select('submitter_id', DB::raw('COUNT(DISTINCT disease_id) as count'))
            ->where('is_live', true)
            ->where('status', Submission::STATUS_PUBLISHED)
            ->groupBy('submitter_id')
            ->get();

        foreach ($uniqueDiseases as $row) {
            if (!isset($submitterUpdates[$row->submitter_id])) {
                $submitterUpdates[$row->submitter_id] = [];
            }
            $submitterUpdates[$row->submitter_id]['count_unique_diseases'] = $row->count;
        }

        // Batch update submitters
        foreach ($submitterUpdates as $submitterId => $updates) {
            Submitter::where('id', $submitterId)->update($updates);
        }

        $this->line('Submitter Counts Completed (' . count($submitterUpdates) . ' submitters updated)');
    }

    /**
     * Update disease counts using SQL aggregation.
     */
    protected function updateDiseaseCounts()
    {
        $this->line('Updating Disease Counts... ');
        Log::channel('slack')->info('Updating Diseases Counts...');

        // First, reset all disease counts to 0
        Disease::query()->update([
            'curations_definitive' => 0,
            'curations_strong' => 0,
            'curations_moderate' => 0,
            'curations_limited' => 0,
            'curations_disputed' => 0,
            'curations_refuted' => 0,
            'curations_animal' => 0,
            'curations_noknown' => 0,
            'curations_supportive' => 0,
            'curations_nul' => 0,
            'count_submissions' => 0,
            'count_unique_submitters' => 0,
            'count_unique_genes' => 0,
        ]);

        // Get classification counts per disease using SQL aggregation
        // Only count live (is_live=true) AND published (status='published') submissions
        $classificationCounts = DB::table('submissions')
            ->join('classifications', 'submissions.classification_id', '=', 'classifications.id')
            ->select(
                'submissions.disease_id',
                'classifications.slug',
                DB::raw('COUNT(*) as count')
            )
            ->where('submissions.is_live', true)
            ->where('submissions.status', Submission::STATUS_PUBLISHED)
            ->groupBy('submissions.disease_id', 'classifications.slug')
            ->get();

        // Build update data grouped by disease_id
        $diseaseUpdates = [];
        foreach ($classificationCounts as $row) {
            if (!isset($diseaseUpdates[$row->disease_id])) {
                $diseaseUpdates[$row->disease_id] = [];
            }
            if (isset($this->classificationColumns[$row->slug])) {
                $diseaseUpdates[$row->disease_id][$this->classificationColumns[$row->slug]] = $row->count;
            }
        }

        // Get total submission counts per disease
        $submissionCounts = DB::table('submissions')
            ->select('disease_id', DB::raw('COUNT(*) as count'))
            ->where('is_live', true)
            ->where('status', Submission::STATUS_PUBLISHED)
            ->groupBy('disease_id')
            ->get();

        foreach ($submissionCounts as $row) {
            if (!isset($diseaseUpdates[$row->disease_id])) {
                $diseaseUpdates[$row->disease_id] = [];
            }
            $diseaseUpdates[$row->disease_id]['count_submissions'] = $row->count;
        }

        // Get unique submitter counts per disease
        $uniqueSubmitters = DB::table('submissions')
            ->select('disease_id', DB::raw('COUNT(DISTINCT submitter_id) as count'))
            ->where('is_live', true)
            ->where('status', Submission::STATUS_PUBLISHED)
            ->groupBy('disease_id')
            ->get();

        foreach ($uniqueSubmitters as $row) {
            if (!isset($diseaseUpdates[$row->disease_id])) {
                $diseaseUpdates[$row->disease_id] = [];
            }
            $diseaseUpdates[$row->disease_id]['count_unique_submitters'] = $row->count;
        }

        // Get unique gene counts per disease
        $uniqueGenes = DB::table('submissions')
            ->select('disease_id', DB::raw('COUNT(DISTINCT gene_id) as count'))
            ->where('is_live', true)
            ->where('status', Submission::STATUS_PUBLISHED)
            ->groupBy('disease_id')
            ->get();

        foreach ($uniqueGenes as $row) {
            if (!isset($diseaseUpdates[$row->disease_id])) {
                $diseaseUpdates[$row->disease_id] = [];
            }
            $diseaseUpdates[$row->disease_id]['count_unique_genes'] = $row->count;
        }

        // Batch update diseases
        foreach ($diseaseUpdates as $diseaseId => $updates) {
            Disease::where('id', $diseaseId)->update($updates);
        }

        $this->line('Disease Counts Completed (' . count($diseaseUpdates) . ' diseases updated)');
        Log::channel('slack')->info('Disease Counts Completed...');
    }
}
