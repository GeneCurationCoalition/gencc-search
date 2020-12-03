<?php

namespace App\Console\Commands;

use App\Disease;
use App\Gene;
use App\Http\Livewire\Dashboard\SubmissionUpload;
use App\Notification;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\Storage;
use App\Imports\SubmissionsImport;
use App\SubmissionFile;
use App\Submitter;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class updateCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gencc:update-counts {--ref=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '#5 Update counts for submissions, genes, diseases, etc...';

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

        $arguments = $this->arguments();
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


        $this->line('Updating Gene Counts... ');
        Log::channel('slack')->info('Updating Gene Counts... ');
        $items = Gene::with('submissions.classification')->has('submissions')->get();
        //$items = $items->processSubmissionsForGene();
        //dd($items);
        // if (empty($items))
        //     $this->emit('endImportCount');

        //dd($list);
        //dd($data);
        foreach ($items as $item) {
            // NOTE for this to work the classificaiton slugs need to be matched to the keys below.
            $list = array(
                "definitive"                    => "0",
                "strong"                        => "0",
                "moderate"                      => "0",
                "limited"                       => "0",
                "disputed"                      => "0",
                "refuted"                       => "0",
                "animal-model-only"             => "0",
                "no-known"                      => "0",
                "supportive"                    => "0",
                "count_submissions"             => "0",
                //"count_unique_submitters"       => "0",
                "nul"                           => "0"
            );

            foreach ($item->submissions as $val) {
                //$val = strstr(, 'GENCC');
                //$agent = str_replace("GENCC:AGENT-", "agent", $val->submitter->curie);
                // Take the val and add one to it.
                $list[$val->classification->slug]                               = $list[$val->classification->slug] + 1;

                // TODO - Make this better
                if (isset($list['count_unique_submitters'][$val->submitter->curie])) {
                    $list['count_unique_submitters'][$val->submitter->curie]    = $list['count_unique_submitters'][$val->submitter->curie] + 1;
                } else {
                    $list['count_unique_submitters'][$val->submitter->curie]    = 1;
                }

                if (isset($val->disease)) {
                    if(isset($list['count_unique_diseases'][$val->disease->curie])) {
                        $list['count_unique_diseases'][$val->disease->curie]    = $list['count_unique_diseases'][$val->disease->curie] + 1;
                    } else {
                        $list['count_unique_diseases'][$val->disease->curie]    = 1;
                    }
                }
                //dd($list);
            }


            $gene = Gene::find($item->id);
            $gene->curations_definitive     = $list['definitive'];
            $gene->curations_strong         = $list['strong'];
            $gene->curations_moderate       = $list['moderate'];
            $gene->curations_limited        = $list['limited'];
            $gene->curations_disputed       = $list['disputed'];
            $gene->curations_refuted        = $list['refuted'];
            $gene->curations_animal         = $list['animal-model-only'];
            $gene->curations_noknown        = $list['no-known'];
            $gene->curations_supportive     = $list['supportive'];
            $gene->curations_nul            = $list['nul'];
            $gene->count_submissions        = count($item->submissions);
            if (isset($list['count_unique_diseases'])) {
                $gene->count_unique_diseases        = count($list['count_unique_diseases']);
            }
            if (isset($list['count_unique_submitters'])) {
                $gene->count_unique_submitters      = count($list['count_unique_submitters']);
                //$gene->count_unique_submitters       = $list['count_unique_submitters'];
            }
            $gene->save();

            //dd($gene);

        }


        Log::channel('slack')->info('Updating Submitter Submission Counts...');
        $this->line('Updating Submitter Submission Counts... ');
        $items = Submitter::with('submissions.classification')->has('submissions')->get();
        //$items = $items->processSubmissionsForGene();
        //dd($items);
        // if (empty($items))
        //     $this->emit('endImportCount');

        //dd($list);
        //dd($data);
        foreach ($items as $item) {
            // NOTE for this to work the classificaiton slugs need to be matched to the keys below.
            $list = array(
                "definitive"                    => "0",
                "strong"                        => "0",
                "moderate"                      => "0",
                "limited"                       => "0",
                "disputed"                      => "0",
                "refuted"                       => "0",
                "animal-model-only"             => "0",
                "no-known"                      => "0",
                "supportive"                    => "0",
                "count_submissions"             => "0",
                //"count_unique_submitters"       => "0",
                "nul"                           => "0"
            );

            foreach ($item->submissions as $val) {
                //$val = strstr(, 'GENCC');
                //$agent = str_replace("GENCC:AGENT-", "agent", $val->submitter->curie);
                // Take the val and add one to it.
                $list[$val->classification->slug]                               = $list[$val->classification->slug] + 1;

                // TODO - Make this better
                if (isset($list['count_unique_genes'][$val->submitter->curie])) {
                    $list['count_unique_genes'][$val->submitter->curie]    = $list['count_unique_genes'][$val->submitter->curie] + 1;
                } else {
                    $list['count_unique_genes'][$val->submitter->curie]    = 1;
                }

                if (isset($val->disease)) {
                    $list['count_unique_diseases'][$val->disease->curie]    = $list[$val->classification->slug][$val->disease->curie] + 1;
                }
                //dd($list);
            }


            $submitter = Submitter::find($item->id);
            $submitter->curations_definitive     = $list['definitive'];
            $submitter->curations_strong         = $list['strong'];
            $submitter->curations_moderate       = $list['moderate'];
            $submitter->curations_limited        = $list['limited'];
            $submitter->curations_disputed       = $list['disputed'];
            $submitter->curations_refuted        = $list['refuted'];
            $submitter->curations_animal         = $list['animal-model-only'];
            $submitter->curations_noknown        = $list['no-known'];
            $submitter->curations_supportive     = $list['supportive'];
            $submitter->curations_nul            = $list['nul'];
            $submitter->count_submissions        = count($item->submissions);
            if (isset($list['count_unique_diseases'])) {
                $submitter->count_unique_diseases        = count($list['count_unique_diseases']);
            }
            if (isset($list['count_unique_genes'])) {
                $submitter->count_unique_genes      = count($list['count_unique_genes']);
                //$gene->count_unique_submitters       = $list['count_unique_submitters'];
            }
            $submitter->save();

            //dd($gene);

        }


        $this->line('Gene Counts Completed... ');
        Log::channel('slack')->info('Gene Counts Completed...');
        Log::channel('slack')->info('Updating Diseases Counts...');
        $this->line('Updating Diseases Counts... ');

        $items = Disease::with('submissions.classification')->has('submissions')->get();
        //$items = $items->processSubmissionsForGene();
        //dd($items);
        if (empty($items))
            $this->emit('endImportCount');

        //dd($list);
        //dd($data);
        foreach ($items as $item) {
            // NOTE for this to work the classificaiton slugs need to be matched to the keys below.
            $list = array(
                "definitive"                    => "0",
                "strong"                        => "0",
                "moderate"                      => "0",
                "limited"                       => "0",
                "disputed"                      => "0",
                "refuted"                       => "0",
                "animal-model-only"             => "0",
                "supportive"                    => "0",
                "no-known"                      => "0",
                "count_submissions"             => "0",
                //"count_unique_submitters"       => "0",
                "nul"                           => "0"
            );

            foreach ($item->submissions as $val) {
                //dd($val);
                // Take the val and add one to it.
                $list[$val->classification->slug]                           = $list[$val->classification->slug] + 1;

                // TODO - Make this better
                if (isset($list['count_unique_submitters'][$val->submitter->curie])) {
                    $list['count_unique_submitters'][$val->submitter->curie]    = $list['count_unique_submitters'][$val->submitter->curie] + 1;
                } else {
                    $list['count_unique_submitters'][$val->submitter->curie]    = 1;
                }

                if (isset($val->disease)) {
                    $list['count_unique_genes'][$val->disease->curie]       = $list[$val->classification->slug][$val->disease->curie] + 1;
                }
            }
            //dd($list);

            $disease = Disease::find($item->id);
            $disease->curations_definitive     = $list['definitive'];
            $disease->curations_strong         = $list['strong'];
            $disease->curations_moderate       = $list['moderate'];
            $disease->curations_limited        = $list['limited'];
            $disease->curations_disputed       = $list['disputed'];
            $disease->curations_refuted        = $list['refuted'];
            $disease->curations_animal         = $list['animal-model-only'];
            $disease->curations_noknown        = $list['no-known'];
            $disease->curations_supportive     = $list['supportive'];
            $disease->curations_nul            = $list['nul'];
            $disease->count_submissions        = count($item->submissions);
            if (isset($list['count_unique_genes'])) {
                $disease->count_unique_genes            = count($list['count_unique_genes']);
            }
            if (isset($list['count_unique_submitters'])) {
                $disease->count_unique_submitters       = count($list['count_unique_submitters']);
                //$disease->count_unique_submitters       = $list['count_unique_submitters'];
            }
            $disease->save();

            //dd($gene);

        }

        $this->line('Disease Counts Completed... ');
        Log::channel('slack')->info('Disease Counts Completed...');

        $this->line('Processing completed');

        Log::channel('slack')->info('Submission Import Completed');
        $notification->status = 0;
        $notification->running = 0;
        $notification->save();
        return 0;
    }
}
