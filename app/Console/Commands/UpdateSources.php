<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;

use App\Gene;
use App\Jira;

class UpdateSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:sources {schedule=daily}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Master command to update all sources based on schedule';

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
      $schedule = $this->argument('schedule');
      switch ($schedule)
      {
        case 'daily':
          $this->call('gencc:update-hgnc');  // HGNC
          $this->call('update:mondo');   // MONDO
          $this->call('update:mim');       // Omim Titles
          $this->call('update:morbid');    // OMIM morbid diseases
          break;
        case 'weekly':
          break;
        case 'monthly':
          break;
        case 'init':
          break;

      }

    }
}
