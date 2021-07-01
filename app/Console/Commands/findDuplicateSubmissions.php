<?php

namespace App\Console\Commands;

use App\Disease;
use App\Gene;
use App\Http\Livewire\Dashboard\SubmissionUpload;
use App\Notification;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\Storage;
use App\Imports\SubmissionsImport;
use App\Submission;
use App\SubmissionFile;
use App\Submitter;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class findDuplicateSubmissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gencc:find-duplicate-submissions {--ref=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '#5';

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

        $this->line('Finding and deleting dup submissions ...');
        //dd($options);

        $duplicated = DB::table('submissions')
        ->select('uuid', DB::raw('count(`uuid`) as occurences'))
        ->groupBy('uuid')
        ->having('occurences', '>', 1)
        ->get();

        //dd($duplicated);
        //Log::channel('slack')->info('Submission Import Started');
        $duplicated_count = 0;
        $deleted_count = 0;
        foreach ($duplicated as $duplicate) {
            $duplicated_count++;
            //dd($duplicate->uuid);
            $submissions = Submission::where('uuid', '=', $duplicate->uuid)->orderBy('submitted_run_date', 'asc')->get();
            //dd($submissions);
            $count = 0;
            foreach ($submissions as $dup) {
                //dd($dup->uuid);
                if($count != 0) {
                    //dd($dup->submitted_run_date);
                    $dup->delete();
                    $deleted_count++;
                }
                $count++;
            }
            //dd("STOP");

        }
        $data = "";
        $this->line('Total submissions with duplicates was '. $duplicated_count);
        $this->line('Total duplicates deleted was ' . $deleted_count);
        $this->line('Done deleting');

        return 0;

    }
}
