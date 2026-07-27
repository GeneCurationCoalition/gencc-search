<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearReleaseCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'releases:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the local release-cache control markers so the next request re-fetches from GCS';

    /**
     * Execute the console command.
     *
     * Removes the cache-control markers (.meta.json and .refresh-failed) for every
     * folder/format combination. Removing .meta.json makes DownloadController fall
     * through its TTL guard and re-fetch from GCS on the next request (revalidating
     * ETag/CRC). The cached data file is deliberately left in place so a transient
     * GCS hiccup still serves last-good rather than 503.
     *
     * Idempotent: missing markers are fine.
     *
     * @return int
     */
    public function handle()
    {
        $folders = ['current', 'legacy'];
        $formats = ['csv', 'tsv', 'xlsx'];
        $markers = ['.meta.json', '.refresh-failed'];

        $removed = 0;

        foreach ($folders as $folder) {
            foreach ($formats as $format) {
                foreach ($markers as $marker) {
                    $path = storage_path("app/release-cache/{$folder}/{$format}/{$marker}");
                    if (File::exists($path)) {
                        File::delete($path);
                        $this->info("Removed {$folder}/{$format}/{$marker}");
                        $removed++;
                    }
                }
            }
        }

        $this->info("Release cache markers cleared ({$removed} removed). Next request will re-fetch from GCS.");

        return 0;
    }
}
