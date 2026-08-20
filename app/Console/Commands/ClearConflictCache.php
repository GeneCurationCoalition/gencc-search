<?php

namespace App\Console\Commands;

use App\Services\ConflictFinder;
use Illuminate\Console\Command;

class ClearConflictCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conflicts:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the cached conflict-viewer result set so the next request recomputes it from the database';

    /**
     * Execute the console command.
     *
     * The conflict set is cached for a few hours as a convenience; this gives ops a
     * way to refresh it right after a release instead of waiting out the TTL.
     *
     * Idempotent: a missing cache entry is fine.
     *
     * @return int
     */
    public function handle()
    {
        ConflictFinder::flush();

        $this->info('Conflict viewer cache cleared. The next request will recompute from the database.');

        return 0;
    }
}
