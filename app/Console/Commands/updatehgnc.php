<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Gene;
use Illuminate\Support\Facades\Storage;

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
        echo "Downloading gene table from genenames.org ...\n";

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
            $results = Storage::disk('local')->path('public/data/hgnc_complete_set.json');
            $results = file_get_contents($results);
        } catch (\Exception $e) {

            echo "(E001) Error retrieving search data\n";
            exit;
        }

        $data = json_decode($results, true);

        if ($data['response']['numFound'] == 0) {
            echo "(E002) Error fetching search data.\n";
        }

        Log::channel('slack')->info('HGNC Import Started');

        foreach ($data['response']['docs'] as $doc) {
            echo "Local File Processing -- " . $doc['hgnc_id'] . " -- " .  $doc['symbol'] . "\n";
            $gene = Gene::updateOrCreate(
                ['curie' => $doc["hgnc_id"]],
                [
                    'curie' => $doc['hgnc_id'],
                    'title' => $doc["symbol"],
                    'uuid' => str_replace("-", ":", $doc['hgnc_id']),
                    'hgnc_uuid' => $doc["uuid"],
                    'description' => $doc["name"],
                    //'discounted' => $doc["description"],
                    'locus_group' => $doc["locus_group"],
                    'locus_type' => $doc["locus_type"],
                    //'alias_symbol' => $doc["alias_symbol"],
                    //'omim_id' => $doc["omim_id"],
                    //'ucsc_id' => $doc["ucsc_id"],
                    'status' => $doc["status"]
                ]
            );
        }

        Log::channel('slack')->info('HGNC Import Completed');
    }
}
