<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;
use Mail;

use Carbon\Carbon;

use App\Gene;
use App\Conflict;
use App\Morbid;
use App\Submission;
use App\Inheritance;
use App\Submitter;
use App\Classification;

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
            case 'conflict':
                $this->report3();
                echo "Report Complete\n";
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


    public function report3()
    {
        $classifications = Classification::all()->pluck('title')->toArray();

        $records = Submission::where('status', 1)->get();
        $fd = fopen("/tmp/conflictreport.tsv", "w");

        $header = "Gene\tHGNC\tMONDO\tDisease\tMOI\tLimited -\tModerate +\t";

        $header .= implode("\t", $classifications);

        fwrite($fd, $header . PHP_EOL);

        $results = [];

        //clear the table
        Conflict::truncate();


        foreach($records as $record)
        {
            //echo "Processing record id $record->id \n";

            $moi = Inheritance::find($record->moi_id);

            if ($moi === null || $record->disease === null)
                continue;

            $lookup = Conflict::triple($record->gene->curie, $record->disease->curie, $moi->curie)->first();

            if ($lookup === null)
            {
                $lookup = new Conflict([
                            'hgnc_id' => $record->gene->curie, 'gene_symbol' => $record->gene->title, 'mondo_id' => $record->disease->curie,
                            'disease' => $record->disease->title, 'moi' => $moi->curie,
                            'weak' => 0, 'strong' => 0, 'submitters' => []
                ]);

                $lookup->save();
            }

            // update classification counters
            switch ($record->classification->curie)
            {
                case 'GENCC:100001':
                case 'GENCC:100002':
                case 'GENCC:100003':
                    $lookup->strong++;
                    break;
                case 'GENCC:100004':
                case 'GENCC:100005':
                case 'GENCC:100006':
                case 'GENCC:100007':
                case 'GENCC:100008':
                    $lookup->weak++;
                    break;
            }
            // update submitters
            $submitters = $lookup->submitters;
            $submitters[] = ['submitter' => $record->submitter->title, 'classification' => $record->classification->title, 'date' => $record->submitted_as_date];
            $lookup->submitters = $submitters;
            $lookup->save();

        }

        $records = Conflict::where('weak', '!=', 0)->where('strong', '!=', 0)->orderBy('gene_symbol', 'asc')->get();

        foreach($records as $record)
        {
            $moi = Inheritance::where('curie', $record->moi)->first();

            $data = [$record->gene_symbol, $record->hgnc_id, $record->mondo_id, $record->disease, $moi->title, $record->weak, $record->strong];

            // create a new array using values fron Subs
            $cdata = array_fill_keys($classifications, "");

            foreach($record->submitters as $submitter)
            {
                if (!empty($cdata[$submitter["classification"]]))
                    $cdata[$submitter["classification"]] .= " || ";

                $cdata[$submitter["classification"]] .= $submitter["submitter"] . ", " . $submitter["date"] . ", " . $submitter["classification"];
            }

            fwrite($fd, implode("\t", array_merge($data, $cdata)) . PHP_EOL);
        }

        fclose($fd);
    }


    public function report4()
    {
        $subs = Submitter::all()->pluck('title')->toArray();

        $records = Submission::where('status', 1)->get();
        $fd = fopen("/tmp/conflictreport.tsv", "w");

        $header = "Gene\tHGNC\tMONDO\tDisease\tMOI\tLimited -\tModerate +\t";

        $header .= implode("\t", $subs);

        fwrite($fd, $header . PHP_EOL);

        $results = [];

        //clear the table
        Conflict::truncate();


        foreach($records as $record)
        {
            //echo "Processing record id $record->id \n";

            $moi = Inheritance::find($record->moi_id);

            if ($moi === null || $record->disease === null)
                continue;

            $lookup = Conflict::triple($record->gene->curie, $record->disease->curie, $moi->curie)->first();

            if ($lookup === null)
            {
                $lookup = new Conflict([
                            'hgnc_id' => $record->gene->curie, 'gene_symbol' => $record->gene->title, 'mondo_id' => $record->disease->curie,
                            'disease' => $record->disease->title, 'moi' => $moi->curie,
                            'weak' => 0, 'strong' => 0, 'submitters' => []
                ]);

                $lookup->save();
            }

            // update classification counters
            switch ($record->classification->curie)
            {
                case 'GENCC:100001':
                case 'GENCC:100002':
                case 'GENCC:100003':
                    $lookup->strong++;
                    break;
                case 'GENCC:100004':
                case 'GENCC:100005':
                case 'GENCC:100006':
                case 'GENCC:100007':
                case 'GENCC:100008':
                    $lookup->weak++;
                    break;
            }
            // update submitters
            $submitters = $lookup->submitters;
            $submitters[] = ['submitter' => $record->submitter->title, 'classification' => $record->classification->title, 'date' => $record->submitted_as_date];
            $lookup->submitters = $submitters;
            $lookup->save();

        }

        $records = Conflict::where('weak', '!=', 0)->where('strong', '!=', 0)->orderBy('gene_symbol', 'asc')->get();

        foreach($records as $record)
        {
            $moi = Inheritance::where('curie', $record->moi)->first();

            $data = [$record->gene_symbol, $record->hgnc_id, $record->mondo_id, $record->disease, $moi->title, $record->weak, $record->strong];

            // create a new array using values fron Subs
            $subdata = array_fill_keys($subs, "");

            foreach($record->submitters as $submitter)
            {
                if (!empty($subdata[$submitter["submitter"]]))
                $subdata[$submitter["submitter"]] .= " || ";

                $subdata[$submitter["submitter"]] .= $submitter["submitter"] . ", " . $submitter["date"] . ", " . $submitter["classification"];
            }

            fwrite($fd, implode("\t", array_merge($data, $subdata)) . PHP_EOL);
        }

        fclose($fd);
    }



}
