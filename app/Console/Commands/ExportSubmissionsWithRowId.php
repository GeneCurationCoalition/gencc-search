<?php

namespace App\Console\Commands;

use App\Exports\SubmissionWithRowIDExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ExportSubmissionsWithRowId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-submissions-with-rowid {--path= : The output path for the export file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export GenCC submissions with row IDs to XLSX file';

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
        $this->info('Starting export of submissions with row IDs...');

        $filename = 'gencc-submissions-with-rowid.xlsx';
        $path = $this->option('path') ?? storage_path('app/exports');

        // Ensure the directory exists
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $fullPath = $path . '/' . $filename;

        // Export the file
        Excel::store(new SubmissionWithRowIDExport, $filename, 'local', \Maatwebsite\Excel\Excel::XLSX);

        // Move to desired location if path was specified
        if ($this->option('path')) {
            $storagePath = storage_path('app/' . $filename);
            if (file_exists($storagePath)) {
                rename($storagePath, $fullPath);
            }
        } else {
            $fullPath = storage_path('app/' . $filename);
        }

        $this->info("Export completed successfully!");
        $this->line("File saved to: {$fullPath}");

        return 0;
    }
}
