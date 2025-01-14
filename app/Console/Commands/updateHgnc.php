<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Gene;
use App\Term;
use Illuminate\Support\Facades\Storage;

// check the name change
class updateHgnc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gencc:update-hgnc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '#1';

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
        echo "Downloading gene table from genenames.org ...";

        // set the options so genenames sends json instead of xml
        $options = array(
            'http' => array(
                'method' => "GET",
                'header' => "Accept: application/json\r\n"
            )
        );

        $context = stream_context_create($options);

        try {

            //$results = file_get_contents("ftp://ftp.ebi.ac.uk/pub/databases/genenames/new/json/hgnc_complete_set.json");
            //$results = Storage::disk('local')->path('public/data/hgnc_complete_set.json');
            //$results = file_get_contents($results);
            //$results = file_get_contents("http://ftp.ebi.ac.uk/pub/databases/genenames/hgnc/json/hgnc_complete_set.json");
            $results = file_get_contents("https://storage.googleapis.com/public-download-files/hgnc/json/json/hgnc_complete_set.json");
        } catch (\Exception $e) {

            echo "\nIMPORT ERROR - (E001) Error retrieving gene data\n";
            exit;
        }

        $data = json_decode($results, true);

        if ($data['response']['numFound'] == 0) {
            echo "\nIMPORT ERROR - (E002) Error fetching gene data.\n";
        }

		foreach ($data['response']['docs'] as $doc)
		{
			//echo "Processing " . $doc['symbol'] . "  " . $doc['name'] .  "  " .  $doc['hgnc_id'] . "\n";

            // change doc status to gene status
		    $doc['nstatus'] = Gene::STATUS_INITIALIZED;

            // we are unsetting the mane entries because we'll pull them in elsewhere
            if (isset($doc['mane_select']))
                unset($doc['mane_select']);

            if (isset($doc['mane_plus']))
                unset($doc['mane_plus']);

            // deal with some legacy gencc fields
            $doc['curie'] = $doc['hgnc_id'];
            $doc['title'] = $doc["symbol"];
            $doc['hgnc_uuid'] = $doc["uuid"];
            $doc['description'] = $doc["name"];
            $doc['uuid'] = str_replace("-", ":", $doc['hgnc_id']);


			// check if entry already exists, if not create
            //$gene = Gene::updateOrCreate(['hgnc_id' => $doc['hgnc_id']], $doc);
            $gene = Gene::updateOrCreate(['curie' => $doc["hgnc_id"]], $doc);

            $term = Term::updateOrCreate(['name' => $gene->symbol, 'value' => $gene->hgnc_id],
                                        ['type' => Gene::TYPE_NAME]);

            if ($gene->prev_symbol !== null)
                foreach ($gene->prev_symbol as $symbol)
                    Term::updateOrCreate(['name' => $symbol, 'value' => $gene->hgnc_id],
                                        ['alias' => $gene->symbol, 'type' => Gene::TYPE_PREV]);
            if ($gene->alias_symbol !== null)
                foreach ($gene->alias_symbol as $symbol)
                    Term::updateOrCreate(['name' => $symbol, 'value' => $gene->hgnc_id],
                                        ['alias' => $gene->symbol, 'type' => Gene::TYPE_ALIAS]);
        }

        //Log::channel('slack')->info('HGNC Import Started');

        //Log::channel('slack')->info('HGNC Import Completed');

        echo "DONE\n";
    }
}
