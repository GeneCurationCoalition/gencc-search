<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Disease;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class updateConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gencc:update-connections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '#3';

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

        Log::channel('slack')->info('MONDO Connect Related Items...');

        $related_exactMatch = Disease::whereNotNull('related_exactMatch')->get();
        //$related_closeMatch = Disease::whereNotNull('related_closeMatch')->get();
        $synonyms_exact = Disease::whereNotNull('synonyms_exact')->get();
        //$synonyms_related = Disease::whereNotNull('synonyms_related')->get();
        //$xrefs = Disease::whereNotNull('xrefs')->get();

        // xrefs
        // synonyms
        // relateds

        // Process XREFS
        // if (isset($xrefs)) {
        //     foreach ($xrefs as $key => $data) {
        //         // Explode the data are IDs to diseases to it's an array
        //         $items = explode("|", $data->xrefs);
        //        // Loop through each item
        //         foreach($items as $item) {
        //             // Find the disease by the ID
        //             $relate = Disease::find($item);
        //             // Pivot has extra tables so get that ready for data
        //             $relate_options[$relate->id] = [
        //                 'type'          => 'xref',
        //                 'predicate'     => 'xref',
        //                 'ontology'      => $relate->type
        //             ];
        //         }
        //             // Sync and save it
        //             $data->xrefs()->sync($relate_options);
        //             $data->save();
        //             unset($relate_options);
        //     }
        // }
        if (isset($related_exactMatch)) {
            foreach ($related_exactMatch as $key => $data) {
                $this->line('Connecting exact matches for ' . $data->title);
                // Explode the data are IDs to diseases to it's an array
                $items = explode("|", $data->related_exactMatch);
                // Loop through each item
                foreach ($items as $item) {
                    // Find the disease by the ID
                    $relate = Disease::find($item);
                    // Pivot has extra tables so get that ready for data
                    $relate_options[$relate->id] = [
                        'type'          => 'propery',
                        'predicate'     => 'exactMatch',
                        'ontology'      => $relate->type
                    ];

                    $inverse_options[$data->id] = [
                        'type'          => 'propery',
                        'predicate'     => 'exactMatch',
                        'ontology'      => $data->type
                    ];
                    $relate->equivalents()->sync($inverse_options);
                    $relate->save();
                    unset($inverse_options);
                }
                // Sync and save it
                $data->equivalents()->sync($relate_options);
                //$data->related_exactMatch = "";
                $data->save();
                unset($relate_options);
            }
        }
        if (isset($related_closeMatch)) {
            foreach ($related_closeMatch as $key => $data) {
                // Explode the data are IDs to diseases to it's an array
                $this->line('Connecting close matches for ' . $data->title);
                $items = explode("|", $data->related_closeMatch);
                // Loop through each item
                foreach ($items as $item) {
                    // Find the disease by the ID
                    $relate = Disease::find($item);
                    // Pivot has extra tables so get that ready for data
                    $relate_options[$relate->id] = [
                        'type'          => 'propery',
                        'predicate'     => 'closeMatch',
                        'ontology'      => $relate->type
                    ];

                    $inverse_options[$data->id] = [
                        'type'          => 'propery',
                        'predicate'     => 'closeMatch',
                        'ontology'      => $data->type
                    ];
                    $relate->equivalents()->sync($inverse_options);
                    $relate->save();
                    unset($inverse_options);
                }
                // Sync and save it
                $data->equivalents()->sync($relate_options);
                //$data->related_closeMatch = "";
                $data->save();
                unset($relate_options);
            }
        }
        if (isset($synonyms_exact)) {
            foreach ($synonyms_exact as $key => $data) {
                $this->line('Connecting synomyms matches for ' . $data->title);
                // Explode the data are IDs to diseases to it's an array
                $items = explode("|", $data->synonyms_exact);
                // Loop through each item
                foreach ($items as $item) {
                    // Find the disease by the ID
                    $relate = Disease::find($item);
                    // Pivot has extra tables so get that ready for data
                    $relate_options[$relate->id] = [
                        'type'          => 'synonym',
                        'predicate'     => 'exactMatch',
                        'ontology'      => $relate->type
                    ];

                    $inverse_options[$data->id] = [
                        'type'          => 'synonym',
                        'predicate'     => 'exactMatch',
                        'ontology'      => $data->type
                    ];
                    $relate->equivalents()->sync($inverse_options);
                    $relate->save();
                    unset($inverse_options);
                }
                // Sync and save it
                $data->synonyms()->sync($relate_options);
                //$data->synonyms_exact = "";
                $data->save();
                unset($relate_options);
            }
        }

        Log::channel('slack')->info('Connections Import Ended');
    }
}
