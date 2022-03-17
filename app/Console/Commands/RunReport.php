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

use App\Gene;
use App\GeneLib;
use App\Morbid;
use App\Panel;
use App\Sensitivity;
use App\Validity;
use App\Nodal;
use App\Gdmmap;

class RunReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:report {report=none}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Erins omim report';

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
        $report = $this->argument('report');

        switch ($report)
        {
            case 'omim':
                echo "Creating gene overlap report\n";
                $this->report1();
                echo "Update Complete\n";
                break;
            default:
                echo "Nothing to do, exiting\n";
                break;
        }
    }

    public function report1()
    {
       /* $records = Gene::where('locus_group', 'protein-coding gene')->where(function($query) {
                                $query->whereNotNull('omim_id')
                                        ->orWhere('curations_definitive', '>', 0)
                                        ->orWhere('curations_strong', '>', 0)
                                        ->orWhere('curations_moderate', '>', 0)
                                        ->orWhere('curations_limited', '>', 0)
                                        ->orWhere('curations_disputed', '>', 0)
                                        ->orWhere('curations_refuted', '>', 0)
                                        ->orWhere('curations_animal', '>', 0)
                                        ->orWhere('curations_noknown', '>', 0)
                                        ->orWhere('curations_supportive', '>', 0);
                        })->get();*/

        $records = Gene::whereNotNull('omim_id')->where('is_morbid', 1)->orWhere('count_submissions', '>', 0)->get();

        $fd = fopen("/tmp/omimreport.tsv", "w");

        $header = "Gene\tHGNC\tOMIM\tGenCC";

        fwrite($fd, $header . PHP_EOL);

        $gencc_count = 0;
        $omim_count = 0;
        $gencc_exc = 0;
        $omim_exc = 0;

        foreach($records as $record)
        {
            $gencc = false;
            $omim = false;

            if ($record->curations_definitive > 0 || $record->curations_strong > 0 || $record->curations_moderate > 0 || $record->curations_limited > 0 ||
                    $record->curations_disputed > 0 || $record->curations_refuted > 0 || $record->curations_animal > 0 || $record->curations_noknown > 0  ||
                    $record->curations_supportive > 0)
            {
                $gencc = true;
                $gencc_count++;
            }

            if ($record->omim_id !== null && $record->is_morbid == 1)
            {
                $omim_count++;
                $omim = true;
            }

            if ($gencc && !$omim)
                $gencc_exc++;

            if ($omim && !$gencc)
                $omim_exc++;

            $data = [ $record->symbol, $record->hgnc_id, ($omim ? 'X' : ''), ($gencc ? 'X' : '')];

            // get all the omim phenotypes for this gene
            $oids = $record->omim_id;
            if ($oids !== null)
            {
                    $phenotypes = Morbid::whereIn('mim', $oids)->get();
                    foreach ($phenotypes as $phenotype)
                    {
                        $name = $phenotype->original_phenotype;
                        if (!empty($phenotype->pheno_omim))
                            $name .= '  ' . $phenotype->pheno_omim;

                        if (!empty($phenotype->mapkey))
                            $name .= '  (' . $phenotype->mapkey . ')';

                        $data[] = $name;
                    }
            }

            fwrite($fd, implode("\t", $data) . PHP_EOL);
        }

        fclose($fd);

        echo "Number of genes in either:  " . $records->count() . "\n";
        echo "Number of GenCC genes:  $gencc_count \n";
        echo "Number of Omim genes:  $omim_count \n";
        echo "Number of Exclusive GenCC genes:  $gencc_exc \n";
        echo "Number of Exclusive Omim genes:  $omim_exc \n";
        echo "Percent overlap:  " . ($gencc_count / $records->count * 100) . "\n";
    }

}
