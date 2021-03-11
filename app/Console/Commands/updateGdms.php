<?php

namespace App\Console\Commands;

use App\Submission;
use App\Trio;
use Illuminate\Console\Command;

class updateGdms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gencc:update-gdms {--ref=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the Gene Disease MOI Trios';

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
     * @return int
     */
    public function handle()
    {
        $items = Submission::with('gene', 'disease', 'inheritance')->where('status', '=', 1)->get();
        //$items = $items->processSubmissionsForGene();
        //dd($items);
        // if (empty($items))
        //     $this->emit('endImportCount');

        //dd($list);
        //dd($data);
        echo "Prcessing GDMs \n";

        foreach ($items as $item) {

            //dd($item);
            echo "Submission UUID: ". $item->uuid . "\n";
            $curie = $item->gene->uuid ."-". $item->disease->uuid ."-". $item->inheritance->uuid;
            $uuid = str_replace(":", "_", $curie);
            //dd($item->inheritance->id);
            $trio = Trio::updateOrCreate(
                ['uuid' => $uuid],
                [
                    'title'         => $item->gene->title ." | ". $item->disease->title ." | ". $item->inheritance->title,
                    'uuid'          => $uuid,
                    'gene_id'          => $item->gene->id,
                    'disease_id'       => $item->disease->id,
                    'moi_id'   => $item->inheritance->id,
                    'status' => 1
                ]
            );

            $item->trio()->associate($trio)->save();

            //dd($curie);

            echo "SAVED GDM UUID: " . $curie . "\n";
        }

        echo "DONE";
        return 0;
    }
}
