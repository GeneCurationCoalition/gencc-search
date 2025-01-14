<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Inheritance;
use App\Gene;
use App\Classification;
use App\Submission;

class UpdateValidity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:validity {option=none}';

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
     * @return mixed
     */
    public function handle()
    {
        $option = $this->argument('option');

        switch ($option)
        {
            case 'clean':
                echo "Removing existing ClinGen Entries\n";
                $this->cleanup();
                break;
            case 'format':
                echo "Reformatting ClinGen download to submission file\n";
                $this->mksub();
                break;
            default:
                echo "Unknown Option\n";
        }

        echo "DONE\n";
    }


    /**
     * Remove all the current ClinGen submissions and any pivots.
     *
     * @return mixed
     */
    public function cleanup()
    {
        Submission::where('submitter_id', 2)->get()->each(function ($record) {

            $record->diseases()->detach();
            $record->delete();

        });
    }


    /**
     * Make a submission form from CG download.
     *
     * @return mixed
     */
    public function mksub()
    {
		echo "Importing ClinGen data ...\n";

        $fp = fopen(base_path() . '/DATA/Clingen-Gene-Disease-Summary.csv', 'r');

		if ($fp === false)
		{
			die("Error opening ClinGen table");
		}

        $fo = fopen(base_path() . '/DATA/Clingen-Submission.csv', 'w');

        // skip over the header section
        for ($n = 0; $n < 6; $n++)
        {
            $data = fgetcsv($fp);
        }

		// parse the remaining file
        while (($data = fgetcsv($fp)) !== false)
        {
            /*
                0 => gene symbol
                1 => gene hgnc id
                2 => disease label
                3 => disease mondo
                4 => moi
                5 => sop
                6 => classification string
                7 => assertion / report
                8 => classification date
                9 => gcep

            */

            // remove prefix and suffix from assertion id

            if (strpos($data[7], 'https://search.clinicalgenome.org/kb/gene-validity/CGGV:assertion_') === 0)
                $assertion_id = substr($data[7], strlen('https://search.clinicalgenome.org/kb/gene-validity/CGGV:assertion_'), 36);
            else if (strpos($data[7], 'https://search.clinicalgenome.org/kb/gene-validity/CGGCIEX:assertion_') === 0)
            {
                $assertion_id = substr($data[7], strlen('https://search.clinicalgenome.org/kb/gene-validity/CGGCIEX:assertion_'));
               // dd($assertion_id);
            }
            else
            {
                echo "Assertion ID error\n";
                dd($data);
            }

            // get the HP term for the MOI

            if ($data[4] == "Undetermined" || $data[4] == "UD")
                $data[4] = "Unknown";           // Clingen uses undetermined instead of known;
            else if ($data[4] == "MT")
                $data[4] = "MIT";

            $hp = Inheritance::where('abbreviation', $data[4])->first();

            if ($hp === null)
            {
                echo "Error mapping moi $data[4] \n";
                dd($data);
            }

            // map the classification

            $sclass = $data[6];

            switch ($sclass)
            {
                case 'Disputed':
                    $sclass = 'Disputed Evidence';
                    break;
                case 'Refuted':
                    $sclass = 'Refuted Evidence';
                    break;

            }
            $class = Classification::where('title', $sclass)->first();

            if ($class === null)
            {
                echo "Error mapping classification $data[6] \n";
                dd($data);
            }

            $gene = Gene::hgnc($data[1])->first();

            if ($gene === null)
            {
				echo "Gene " . $data[1] . " not found\n";
				continue;
            }

            // build up the new format
            $a = [
                $assertion_id,          // id
                $data[1],               // Gene HGNC ID
                $data[0],               // Gene symbol
                $data[3],               // Mondo ID
                $data[2],               // Disease name
                $hp->curie,             // MOI HP term
                $hp->title,             // MOI string
                'GENCC:000102',         // Submitter ID
                'ClinGen',              // Submitter name
                $class->curie,          // Classification ID
                $class->title,               // Classification string
                $data[8],               // Report date
                $data[7],                     // Report URL
                '',                     // Notes
                '',                     // list of pmids
                'https://www.clinicalgenome.org/docs/?doc-type=curation-activity-procedures&curation-procedure=gene-disease-validity'       // Assertion criteria


                ];

                fputcsv($fo, $a);

        }

        fclose($fp);
        fclose($fo);


    }
}
