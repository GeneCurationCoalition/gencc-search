<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Disease;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class updateMondo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gencc:update-mondo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'TEMP SKIP - #2';

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
        echo "Downloading mondo's from monarchinitiative.org ...\n";

        try {
            $results = Storage::disk('local')->path('public/data/mondo.json');
            //$results = file_get_contents("https://archive.monarchinitiative.org/latest/owl/mondo.json");
        } catch (\Exception $e) {

            echo "IMPORT ERROR - (E001) Error retrieving search data\n";
            exit;
        }

        //$data = json_decode($results, true);

        $data = \JsonMachine\JsonMachine::fromFile($results, "/graphs/0/nodes" /* <- Json Pointer */);
        //dd($data);
        if (!$data) {
            echo "IMPORT ERROR - (E002) Error fetching search data.\n";
        }

        Log::channel('slack')->info('MONDO Import Started');

        foreach ($data as $name => $item) {
            if (isset($item['id'])) {
                $id = explode("/", $item['id']);
                $uuid = end($id);
                $curie = str_replace('_', ':', $uuid);
                $type = explode(":", $curie);
                $type = $type[0];
            } else {
                $curie = null;
            }
            $title          = isset($item['lbl']) ? $item['lbl'] : null;
            $deprecated     = isset($item['meta']['deprecated']) ? true : false;
            $xrefs          = isset($item['meta']['xrefs']) ? $item['meta']['xrefs'] : null;
            $synonyms = isset($item['meta']['synonyms']) ? $item['meta']['synonyms'] : null;
            $basicPropertyValues = isset($item['meta']['basicPropertyValues']) ? $item['meta']['basicPropertyValues'] : null;
            $description    = isset($item['meta']['definition']['val']) ? $item['meta']['definition']['val'] : null;
            //dd($uuid);
            echo "Local File Processing -- '" . $curie . "' -- " .  $title . "\n";

            if (($deprecated  != true) && (preg_match('(OMIM:|MONDO:|Orphanet:|HP:)', $curie) === 1)) {
                if ($curie) {
                    $disease = Disease::updateOrCreate(
                        [
                            'uuid'          => $uuid,
                            'type'          => $type,
                            'curie'         => $curie,
                            'title'         => $title,
                            'description'   => $description
                        ],
                        [
                            'uuid'          => $uuid,
                            'type'          => $type,
                            'curie'         => $curie,
                            'title'         => $title,
                            'description'   => $description
                        ]
                    );
                }

                // The xrefs are the equivs that can be added
                // and connected with the disease as a child
                if ($xrefs) {
                    foreach ($xrefs as $xref) {
                        $val = $xref["val"];
                        // Only save some of the diseases
                        //if (preg_match('(OMIM:|MONDO:|DOID:|Orphanet:|HP:)', $val) === 1) {
                        if (preg_match('(OMIM:|MONDO:|Orphanet:|HP:)', $val) === 1) {


                            //dd("test");
                            $type = explode(":", $val);
                            $type = $type[0];
                            $title = $val;
                            $uuid = str_replace(':', '_', $val);

                            $result = Disease::updateOrCreate(
                                [
                                    'curie' => $val,
                                    'type' => $type,
                                    'title' => $title,
                                    'uuid'  => $uuid
                                ],
                                [
                                    'curie' => $val,
                                    'type' => $type,
                                    'title' => $title,
                                    'uuid'  => $uuid
                                ]
                            );
                            echo "Local File Processing -- '" . $val . "' -- " .  $title . "\n";

                            // Save
                            $sync[] = $result->id;

                            //
                        }
                    }
                    // Grab the sync and make a string and save it to the disease
                    if (isset($sync)) {
                        //dd($disease);
                        //dd($sync);
                        $sync = array_unique($sync);
                        $disease->xrefs = implode("|", $sync);
                        //dd($disease->xrefs);
                    }
                    unset($sync);
                }
                // The xrefs are the equivs that can be added
                // and connected with the disease as a child
                if ($basicPropertyValues) {
                    // pred
                    // val
                    foreach ($basicPropertyValues as $item) {
                        $val = $item["val"];

                        if (($item["pred"] == "http://www.w3.org/2004/02/skos/core#closeMatch") or ($item["pred"] == "http://www.w3.org/2004/02/skos/core#exactMatch")) {
                            //dd($item["pred"]);
                            $type = explode("/", $val);
                            $ontology = strtoupper($type[3]);
                            $curie = $ontology . ":" . $type[4];
                            $uuid = str_replace(':', '_', $curie);
                            //dd($val);
                            //if (preg_match('(OMIM:|MONDO:|DOID:|Orphanet:|HP:)', $ontology) === 1) {
                            if (preg_match('(OMIM:|MONDO:|Orphanet:|HP:)', $curie) === 1) {
                                //dd($curie);
                                $entry = Disease::updateOrCreate(
                                    [
                                        'curie'     => $curie,
                                        'type'      => $ontology,
                                        'uuid'       => $uuid
                                    ],
                                    [
                                        'curie'     => $curie,
                                        'type'      => $ontology,
                                        'uuid'       => $uuid
                                    ]
                                );
                                echo "Local File Processing -- '" . $curie . "' -- " .  $curie . "\n";
                                //dd($item["pred"]);
                                if ($item["pred"] == "http://www.w3.org/2004/02/skos/core#closeMatch") {
                                    $closeMatch[] = $entry->id;
                                    //dd($closeMatch);
                                } elseif ($item["pred"] == "http://www.w3.org/2004/02/skos/core#exactMatch") {
                                    $exactMatch[] = $entry->id;
                                }
                            }
                        }
                    }
                    // Grab the sync and make a string and save it to the disease
                    if (isset($closeMatch)) {
                        $closeMatch = array_unique($closeMatch);
                        $disease->related_closeMatch = implode("|", $closeMatch);
                        unset($closeMatch);
                    }
                    if (isset($exactMatch)) {
                        //dd($exactMatch);
                        $exactMatch = array_unique($exactMatch);
                        $disease->related_exactMatch = implode("|", $exactMatch);
                        unset($exactMatch);
                    }
                    //dd($disease);
                    //$disease->save();
                }

                if ($synonyms) {
                    // pred
                    // val
                    //dd($synonyms);
                    foreach ($synonyms as $item) {
                        //dd($item);
                        $val = $item["val"];
                        // Go through each of the xrefs and create/match as needed
                        foreach ($item["xrefs"] as $xref) {
                            //dd($xref);
                            $type = explode(":", $xref);
                            $type = $type[0];
                            $title = $xref;
                            $uuid = str_replace(':', '_', $xref);
                            //if (preg_match('(OMIM:|MONDO:|DOID:|Orphanet:|HP:)', $xref) === 1) {
                            if (preg_match('(OMIM:|MONDO:|Orphanet:|HP:)', $xref) === 1) {
                                //dd($xref);
                                $entry = Disease::updateOrCreate(
                                    [
                                        'curie' => $xref,
                                        'type' => $type,
                                        'title' => $title,
                                        'uuid'  => $uuid
                                    ],
                                    [
                                        'curie' => $xref,
                                        'type' => $type,
                                        'title' => $title,
                                        'uuid'  => $uuid
                                    ]
                                );
                                echo "Local File Processing -- '" . $xref . "' -- " .  $title . "\n";
                                //dd($item["pred"]);
                                if ($item["pred"] == "hasExactSynonym") {
                                    $hasExactSynonym[] = $entry->id;
                                    //dd($closeMatch);
                                }
                            }
                        }
                    }

                    // Grab the sync and make a string and save it to the disease
                    if (isset($hasExactSynonym)) {
                        $hasExactSynonym = array_unique($hasExactSynonym);
                        $disease->synonyms_exact = implode("|", $hasExactSynonym);
                        unset($hasExactSynonym);
                    }
                }
                $disease->save();
            }

        }

        Log::channel('slack')->info('MONDO Import Ended');
    }
}
