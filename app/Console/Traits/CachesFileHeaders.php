<?php

namespace App\Console\Traits;

use App\StaticFileHeader;
use Illuminate\Support\Facades\Http;

/**
 * Trait CachesFileHeaders
 *
 * Provides HTTP header caching functionality for Console commands that download
 * external static files. This enables skipping downloads when files haven't changed
 * based on ETags, Last-Modified dates, and Content-Length headers.
 *
 * @package App\Console\Traits
 */
trait CachesFileHeaders
{
    /**
     * Fetch HTTP headers for a URL
     *
     * Note: HTTP header names are case-insensitive per RFC 7230.
     * Laravel's HTTP client (via Guzzle) handles this automatically,
     * so ->header('etag') will match 'ETag', 'etag', or 'ETAG' from the server.
     *
     * @param string $url
     * @return array|null Returns array with lowercase header keys, or null on failure
     */
    protected function fetchHeaders($url)
    {
        try {
            $response = Http::head($url);

            if ($response->failed()) {
                return null;
            }

            // Return headers with normalized lowercase keys for consistency
            return [
                'etag' => $response->header('etag'),
                'last-modified' => $response->header('last-modified'),
                'content-length' => $response->header('content-length'),
            ];

        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * Check if file needs to be updated based on cached headers
     *
     * @param string $fileIdentifier
     * @param string $url
     * @param string|null $tableName Optional table name to check if empty (e.g., 'genes', 'diseases')
     * @return bool True if file needs updating, false if cached version is current
     */
    protected function shouldUpdateFile($fileIdentifier, $url, $tableName = null)
    {
        $this->info("......checking if {$fileIdentifier} needs updating");

        // If table name provided, check if table is empty
        if ($tableName !== null) {
            $tableCount = \DB::table($tableName)->count();
            if ($tableCount === 0) {
                $this->info("......table '{$tableName}' is empty, will download");
                return true;
            }
        }

        // Fetch current headers from the URL
        $currentHeaders = $this->fetchHeaders($url);

        if ($currentHeaders === null) {
            $this->warn("......failed to fetch headers, proceeding with update");
            return true;
        }

        // Try to find cached headers
        $cachedHeader = StaticFileHeader::latest($fileIdentifier);

        if ($cachedHeader === null) {
            $this->info("......no cached headers found, will download");
            return true;
        }

        // Compare headers
        if ($cachedHeader->matchesHeaders($currentHeaders)) {
            $this->info("......file unchanged, skipping download");
            return false;
        }

        $this->info("......file has changed, will download");
        return true;
    }

    /**
     * Check if file needs to be updated and optionally truncate table if file has changed
     *
     * @param string $fileIdentifier
     * @param string $url
     * @param string|null $tableName Optional table name to truncate if file changed
     * @return bool True if file needs updating, false if cached version is current
     */
    protected function shouldUpdateFileAndTruncate($fileIdentifier, $url, $tableName = null)
    {
        $this->info("......checking if {$fileIdentifier} needs updating");

        // If table name provided, check if table is empty
        if ($tableName !== null) {
            $tableCount = \DB::table($tableName)->count();
            if ($tableCount === 0) {
                $this->info("......table '{$tableName}' is empty, will download");
                return true;
            }
        }

        // Fetch current headers from the URL
        $currentHeaders = $this->fetchHeaders($url);

        if ($currentHeaders === null) {
            $this->warn("......failed to fetch headers, proceeding with update");
            if ($tableName !== null) {
                $this->info("......truncating table '{$tableName}'");
                \DB::table($tableName)->truncate();
            }
            return true;
        }

        // Try to find cached headers
        $cachedHeader = StaticFileHeader::latest($fileIdentifier);

        if ($cachedHeader === null) {
            $this->info("......no cached headers found, will download");
            if ($tableName !== null) {
                $this->info("......truncating table '{$tableName}'");
                \DB::table($tableName)->truncate();
            }
            return true;
        }

        // Compare headers
        if ($cachedHeader->matchesHeaders($currentHeaders)) {
            $this->info("......file unchanged, skipping download");
            return false;
        }

        $this->info("......file has changed, will download");
        if ($tableName !== null) {
            $this->info("......truncating table '{$tableName}'");
            \DB::table($tableName)->truncate();
        }
        return true;
    }


    /**
     * Create a new cached header record for a file
     *
     * This creates a new record each time, maintaining an append-only log
     * of header changes over time.
     *
     * @param string $fileIdentifier
     * @param string $url
     * @return void
     */
    protected function updateCachedHeaders($fileIdentifier, $url)
    {
        $headers = $this->fetchHeaders($url);

        if ($headers === null) {
            $this->warn("......failed to update cached headers");
            return;
        }

        StaticFileHeader::create([
            'file_identifier' => $fileIdentifier,
            'etag' => $headers['etag'] ?? null,
            'last_modified' => $headers['last-modified'] ?? null,
            'content_length' => $headers['content-length'] ?? null,
        ]);

        $this->info("......cached headers updated");
    }


    /**
     * Download a file and cache it to the data directory
     *
     * @param string $url
     * @param string $cacheFilename
     * @return string|null The file contents, or null on failure
     */
    protected function downloadAndCacheFile($url, $cacheFilename)
    {
        try {
            $this->info("......downloading file to cache");
            $data = file_get_contents($url);

            if ($data === false) {
                $this->error("......failed to download file");
                return null;
            }

            $cachePath = base_path('data/' . $cacheFilename);

            // Ensure data directory exists
            $dataDir = base_path('data');
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }

            file_put_contents($cachePath, $data);
            $this->info("......file cached to {$cacheFilename}");

            return $data;

        } catch (\Exception $e) {
            $this->error("......failed to download and cache file: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Get a cached file from the data directory
     *
     * @param string $cacheFilename
     * @return string|null The file contents, or null if not found
     */
    protected function getCachedFile($cacheFilename)
    {
        $cachePath = base_path('data/' . $cacheFilename);

        if (!file_exists($cachePath)) {
            $this->warn("......cached file {$cacheFilename} not found");
            return null;
        }

        $this->info("......reading cached file {$cacheFilename}");
        return file_get_contents($cachePath);
    }
}
