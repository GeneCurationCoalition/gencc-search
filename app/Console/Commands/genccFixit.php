<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\Comment;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\User\UserService;
use JiraRestApi\JiraException;

use DB;
use Mail;

use Carbon\Carbon;

use App\Submission;
use App\Disease;

class GenccFixit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gencc:fixit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix the missing disease ids';

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
        $bads = Submission::where('disease_id', null)->get();

        foreach ($bads as $bad)
        {
            $disease = Disease::where('curie', $bad->submitted_as_disease_id)->first();

            if ($disease === null)
            {
                echo "$bad->submitted_as_disease_id not found. \n";
            }
            else
            {
                if ($disease->xrefs !== null)
                {
                    $disease = Disease::find($disease->xrefs);

                    if ($disease === null)
                    {
                        echo "MONDO $bad->submitted_as_disease_id not found. \n";
                        continue;
                    }
                }

                $bad->disease_id = $disease->id;

                $bad->save();

                echo "Disease found! \n";

            }
        }

    }
}
