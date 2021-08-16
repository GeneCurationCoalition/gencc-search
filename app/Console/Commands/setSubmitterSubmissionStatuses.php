<?php

namespace App\Console\Commands;

use App\Submission;
use App\Submitter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class setSubmitterSubmissionStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gencc:set-submission-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $submitters = Submitter::all();
        foreach ($submitters as $item) {
            $this->info('' . $item->curie . '  -  ' . $item->title);
        }

        $submitter_input = $this->ask('Which submitter from the list above? To choose one type in their CURIE: GENCC:000000');
        $submitter = Submitter::curie($submitter_input)->first();
        //dd($submitter);
        if($submitter) {
            $status_input = $this->ask('What status should all submissions for ' . $submitter->title . ' to be set? (1=visable , 0=hidden)');
        } else {
            $this->error('That submitter was not found!');
            return 0;
        }

        if ($this->confirm('Are you sure you want to change all the submissions for '. $submitter->title . ' to status '. $status_input .'.', true)) {
            //
        } else {
            $this->error('Cancelled');
            return 0;
        }

        //dd($submitter->submissions);
        $count = 0;
        foreach(Submission::where('submitter_id', '=', $submitter->id)->get() as $item) {
            $submission = Submission::where('uuid', '=', $item->uuid)->first();
            //dd($submission->status);
            $submission->status = $status_input;
            $submission->save();
            $count++;
        }
        $this->info($count . ' ' . $submitter->title . ' submissions updated successfully. Wait one minute while the counts are updated...');
        Artisan::call('gencc:update-counts');
        $this->info('Done, have a good rest of your day!');
        return 0;
    }
}
