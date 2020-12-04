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

class updateSubmissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gencc:update-submissions {--ref=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '#4';

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
     * @return mixed
     */
    public function handle()
    {

        $arguments = $this->arguments();
        $options = $this->options();
        $refUuid = ($this->option('ref') ?? Str::uuid());
        //dd($refUuid);

        $this->line('Processing submissions ...');
        //dd($options);

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

        Log::channel('slack')->info('Submission Import Started');



        $submissions = SubmissionFile::where('status', 1)->orderby('created_at', 'asc')->get();
        foreach($submissions as $submission) {

            Log::channel('slack')->info('For Submitter ' . $submission->submitter->title);
            Log::channel('slack')->info('Loading submission ' . $submission->file_name);
            $this->line('For Submitter ' . $submission->submitter->title);
            $this->line('Loading submission '. $submission->file_name);
            //$data = Storage::disk('local')->path('public/private/official/PanelAppAus.xlsx');
            $data = Storage::disk('local')->path($submission->path);
            //dd($data);
            if($submission->submitted_run_date){
                $submitted_run_date = $submission->submitted_run_date->format('Y/m/d');
            } else {
                $submitted_run_date = $submission->created_by->format('Y/m/d');
            }
            $import = Excel::import(new SubmissionsImport($submitted_run_date), $data);
            //$this->line($import);
        }
        $this->line('Loading completed');
        Log::channel('slack')->info('Loading completed');

        //echo " \n";
        //dd($data);
        if (!$data) {
            echo "(E002) Error fetching search data.\n";
        }

        Log::channel('slack')->info('Submission Import Completed');
        $notification->status = 0;
        $notification->running = 0;
        $notification->save();

        return 0;

    }
}
