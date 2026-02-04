<?php

namespace App\Console\Commands;

use App\Submitter;
use Illuminate\Console\Command;

class ImportSubmitterLogos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-submitter-logos {--path= : Path to logo files directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import submitter logos from files into database logo_contents column';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Importing submitter logos...');

        $logoPath = $this->option('path') ?? public_path('brand/submitters');

        if (!is_dir($logoPath)) {
            $this->error("Logo directory not found: {$logoPath}");
            return 1;
        }

        $submitters = Submitter::all();
        $imported = 0;
        $skipped = 0;

        foreach ($submitters as $submitter) {
            // Convert CURIE format (GENCC:000101) to filename format (GENCC_000101.png)
            $filename = str_replace(':', '_', $submitter->curie) . '.png';
            $filePath = $logoPath . '/' . $filename;

            if (!file_exists($filePath)) {
                $this->warn("Logo file not found for {$submitter->name}: {$filename}");
                $skipped++;
                continue;
            }

            // Read file and encode to base64
            $fileContents = file_get_contents($filePath);
            $base64 = base64_encode($fileContents);

            // Determine MIME type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($filePath);

            // Update the submitter
            $submitter->logo_contents = $base64;
            $submitter->logo_mime_type = $mimeType;
            $submitter->save();

            $this->line("Imported logo for: {$submitter->name} ({$mimeType})");
            $imported++;
        }

        $this->info("Import complete. Imported: {$imported}, Skipped: {$skipped}");

        return 0;
    }
}
