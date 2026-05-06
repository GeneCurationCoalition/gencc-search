<?php

namespace App\Http\Controllers;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{

    /**
     * MIME types for export formats.
     */
    private const MIME_TYPES = [
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'csv' => 'text/csv',
        'tsv' => 'text/tab-separated-values',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_meta['seo']['title'] = "Download GenCC Data";

        $ttl = config('downloads.cache_ttl');
        $gcsConfigured = !empty(config('filesystems.disks.gcs.bucket'));

        $allHealthy = true;
        foreach (array_keys(self::MIME_TYPES) as $format) {
            foreach (['legacy', 'current'] as $version) {
                if (!$this->isHealthyForServing($format, $version, $ttl, $gcsConfigured)) {
                    $allHealthy = false;
                    break 2;
                }
            }
        }

        return view('download.index', [
            'page_meta' => $page_meta,
            'downloads_available' => $allHealthy,
        ]);
    }

    private function isHealthyForServing(string $format, string $folder, int $ttl, bool $gcsConfigured): bool
    {
        $meta = $this->readMeta($format, $folder);
        $hasFile = file_exists($this->cachePath($format, $folder));

        if (!$meta || !$hasFile) {
            return $gcsConfigured && !$this->refreshBackoffActive($meta ?? [], $ttl);
        }

        $isFresh = (time() - ($meta['checked_at'] ?? 0)) < $ttl;
        if ($isFresh) {
            return true;
        }

        return $gcsConfigured && !$this->refreshBackoffActive($meta, $ttl);
    }

    // ─── Public export endpoints ─────────────────────────────────────

    public function export_XLSX() { return $this->handleExport('xlsx'); }
    public function export_CSV()  { return $this->handleExport('csv'); }
    public function export_TSV()  { return $this->handleExport('tsv'); }

    // ─── Main orchestration ──────────────────────────────────────────

    private function handleExport(string $format): Response
    {
        if (!isset(self::MIME_TYPES[$format])) {
            abort(404);
        }

        $folder = request()->query('format') === 'new' ? 'current' : 'legacy';
        $isHead = request()->isMethod('HEAD');

        // 1. Fast 304 check against cached metadata (no GCS call, no quota impact)
        $notModified = $this->checkNotModified($format, $folder);
        if ($notModified) {
            return $notModified;
        }

        // 2. Resolve the file (local cache or GCS)
        $filePath = $this->resolveCachedFile($format, $folder);
        if ($filePath === null) {
            abort(503, 'Release files are temporarily unavailable. Please try again later.');
        }

        // 3. Build BinaryFileResponse with ETag/Last-Modified headers
        //    Symfony automatically strips the body for HEAD requests
        $response = $this->buildFileResponse($format, $folder);

        // 4. Let Symfony check if response is actually 304
        //    (edge case: newly-fetched file matches client conditional headers)
        if ($response->isNotModified(request())) {
            return $response;
        }

        // 5. Enforce quota only before serving a full GET body.
        if (!$isHead) {
            $quotaResponse = $this->enforceDownloadQuota();
            if ($quotaResponse) {
                return $quotaResponse;
            }
        }

        // 6. Full download — count against quota after successful response.
        if (!$isHead) {
            $this->incrementDownloadQuota();
        }

        return $response;
    }

    // ─── Conditional request handling (304) ───────────────────────────

    private function checkNotModified(string $format, string $folder): ?Response
    {
        // Return a 304 Not Modified if the client's cached version is still valid based on our metadata.
        // Otherwise return null to proceed with normal file resolution and response building.
        $meta = $this->readMeta($format, $folder);
        if (!$meta) {
            return null;
        }

        // Ensure the corresponding cached file actually exists before
        // serving a 304 based solely on metadata.
        $cacheFile = $this->cachePath($format, $folder);
        if (!is_string($cacheFile) || $cacheFile === '' || !file_exists($cacheFile)) {
            return null;
        }

        $ttl = config('downloads.cache_ttl');
        if ((time() - ($meta['checked_at'] ?? 0)) >= $ttl) {
            return null;
        }

        $ifNoneMatch = request()->header('If-None-Match');
        $ifModifiedSince = request()->header('If-Modified-Since');

        if (!$ifNoneMatch && !$ifModifiedSince) {
            return null;
        }

        // ETag match
        if ($ifNoneMatch && isset($meta['http_etag']) && $meta['http_etag'] !== '') {
            $clientEtags = array_map('trim', explode(',', $ifNoneMatch));
            foreach ($clientEtags as $clientEtag) {
                if ($clientEtag === $meta['http_etag'] || $clientEtag === '*') {
                    return $this->notModifiedResponse($meta);
                }
            }
            return null;
        }

        // Last-Modified match
        if ($ifModifiedSince && !empty($meta['source_last_modified'])) {
            $clientTime = strtotime($ifModifiedSince);
            $serverTime = strtotime($meta['source_last_modified']);
            if ($clientTime !== false && $serverTime !== false && $serverTime <= $clientTime) {
                return $this->notModifiedResponse($meta);
            }
        }

        return null;
    }

    private function notModifiedResponse(array $meta): Response
    {
        $headers = ['Cache-Control' => 'public, no-cache'];
        if (isset($meta['http_etag']) && $meta['http_etag'] !== '') {
            $headers['ETag'] = $meta['http_etag'];
        }
        if (!empty($meta['source_last_modified'])) {
            $headers['Last-Modified'] = $meta['source_last_modified'];
        }
        return response('', 304, $headers);
    }

    // ─── Daily per-IP download quota ─────────────────────────────────

    private function downloadQuotaKey(): string
    {
        return 'download-quota:' . request()->ip() . ':' . date('Y-m-d');
    }

    private function enforceDownloadQuota(): ?Response
    {
        $dailyQuota = config('downloads.daily_quota');
        $key = $this->downloadQuotaKey();
        $count = Cache::get($key, 0);

        if ($count >= $dailyQuota) {
            return response(
                "Daily download limit reached ({$dailyQuota} per day per IP).\n"
                . "This data is updated weekly. Please reduce your polling frequency.\n"
                . "Tip: use a HEAD request and If-None-Match or If-Modified-Since headers to\n"
                . "check for changes without counting against this limit.\n",
                429,
                [
                    'Content-Type' => 'text/plain',
                    'Retry-After' => (string) (strtotime('tomorrow') - time()),
                ]
            );
        }

        return null;
    }

    private function incrementDownloadQuota(): void
    {
        $key = $this->downloadQuotaKey();

        if (Cache::add($key, 1, now()->endOfDay())) {
            return; // Key was new, add() set it to 1
        }

        Cache::increment($key);
    }

    // ─── Local file caching ──────────────────────────────────────────

    private function resolveCachedFile(string $format, string $folder): ?string
    {
        $filePath = $this->cachePath($format, $folder);
        $meta = $this->readMeta($format, $folder);
        $ttl = config('downloads.cache_ttl');

        // Cache exists and TTL not expired — serve from disk
        if ($meta && file_exists($filePath) && (time() - ($meta['checked_at'] ?? 0)) < $ttl) {
            return $filePath;
        }

        // A recent refresh attempt failed — fail closed to avoid hammering GCS
        // and to avoid silently serving stale data.
        if ($meta && $this->refreshBackoffActive($meta, $ttl)) {
            Log::warning("Refusing to serve stale {$folder}/{$format} during GCS refresh backoff");
            return null;
        }

        $client = $this->getGcsClient();
        if ($client) {
            return $this->resolveFromGcs($client, $format, $folder, $filePath, $meta);
        }

        Log::error("Download unavailable: GCS not configured and cache for {$folder}/{$format} is missing or stale");
        return null;
    }

    private function resolveFromGcs(
        StorageClient $client,
        string $format,
        string $folder,
        string $filePath,
        ?array $meta
    ): ?string {
        $bucketName = config('filesystems.disks.gcs.bucket');
        $bucket = $client->bucket($bucketName);
        $prefix = config('filesystems.disks.gcs.path_prefix', 'releases');
        $path = "{$prefix}/{$folder}/{$format}/gencc-submissions.{$format}";
        $object = $bucket->object($path);

        try {
            if (!$object->exists()) {
                Log::warning("GCS file not found: {$path}");
                if ($meta) {
                    $this->writeMeta($format, $folder, $this->markRefreshFailed($meta));
                }
                return null;
            }

            $info = $object->info();
            $remoteSourceEtag = $info['etag'] ?? null;
            $remoteCrc32c = $info['crc32c'] ?? null;
            $sourceLastModified = $this->sourceLastModifiedFromInfo($info);

            if ($meta && file_exists($filePath) && $this->remoteUnchanged($meta, $remoteSourceEtag, $remoteCrc32c)) {
                $this->writeMeta($format, $folder, $this->updateSourceMeta(
                    $meta,
                    $remoteSourceEtag,
                    $sourceLastModified,
                    time()
                ));
                return $filePath;
            }

            // Changed or no cache — download to temp file, then atomic rename
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $tempPath = $filePath . '.tmp.' . getmypid();
            $object->downloadToFile($tempPath);

            $downloadedMeta = $this->buildFileMeta(
                $tempPath,
                'gcs',
                $remoteSourceEtag,
                $sourceLastModified,
                time()
            );
            if ($remoteCrc32c && ($downloadedMeta['content_crc32c'] ?? null) !== $remoteCrc32c) {
                throw new \RuntimeException("Downloaded file CRC32C did not match GCS metadata for {$path}");
            }

            rename($tempPath, $filePath);
            $this->writeMeta($format, $folder, $downloadedMeta);

            return $filePath;
        } catch (\Exception $e) {
            Log::error("GCS cache refresh failed for {$folder}/{$format}: {$e->getMessage()}");

            if (isset($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }

            if ($meta) {
                $this->writeMeta($format, $folder, $this->markRefreshFailed($meta));
            }

            return null;
        }
    }

    // ─── Response builder ────────────────────────────────────────────

    private function buildFileResponse(string $format, string $folder): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filePath = $this->cachePath($format, $folder);
        $meta = $this->readMeta($format, $folder);
        $mimeType = self::MIME_TYPES[$format] ?? 'application/octet-stream';
        $lastModified = $meta['source_last_modified'] ?? '';

        $headers = ['Content-Type' => $mimeType, 'Cache-Control' => 'public, no-cache'];
        if (!empty($meta['http_etag'])) {
            $headers['ETag'] = $meta['http_etag'];
        }
        $response = response()->download($filePath, "gencc-submissions.{$format}", $headers);

        // Set Last-Modified after BinaryFileResponse construction, because
        // setFile() auto-sets it to the file's mtime on disk, overriding
        // any value passed in the headers array.
        $lastModifiedDate = $this->dateTimeFromHttpDate($lastModified);
        if ($lastModifiedDate) {
            $response->setLastModified($lastModifiedDate);
        }

        return $response;
    }

    // ─── Cache path helpers ──────────────────────────────────────────

    private function cachePath(string $format, string $folder): string
    {
        return storage_path("app/release-cache/{$folder}/{$format}/gencc-submissions.{$format}");
    }

    private function metaPath(string $format, string $folder): string
    {
        return storage_path("app/release-cache/{$folder}/{$format}/.meta.json");
    }

    private function readMeta(string $format, string $folder): ?array
    {
        $path = $this->metaPath($format, $folder);
        if (!file_exists($path)) {
            return null;
        }
        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data)) {
            return null;
        }

        return $this->isUsableMeta($data) ? $data : null;
    }

    private function buildFileMeta(
        string $filePath,
        string $source,
        ?string $sourceEtag = null,
        ?string $sourceLastModified = null,
        ?int $checkedAt = null
    ): array {
        $localMd5Hex = md5_file($filePath);
        if ($localMd5Hex === false) {
            throw new \RuntimeException("Unable to hash cached release file: {$filePath}");
        }
        $crc32cHex = hash_file('crc32c', $filePath);
        if ($crc32cHex === false) {
            throw new \RuntimeException("Unable to calculate CRC32C for cached release file: {$filePath}");
        }

        return [
            'content_crc32c' => base64_encode(hex2bin($crc32cHex)),
            'content_md5' => base64_encode(hex2bin($localMd5Hex)),
            'http_etag' => '"' . $localMd5Hex . '"',
            'source_etag' => $sourceEtag,
            'source_last_modified' => $sourceLastModified ?: gmdate('D, d M Y H:i:s') . ' GMT',
            'checked_at' => $checkedAt ?? time(),
            'source' => $source,
            'size' => filesize($filePath),
        ];
    }

    private function remoteUnchanged(array $meta, ?string $remoteSourceEtag, ?string $remoteCrc32c): bool
    {
        if ($remoteSourceEtag && !empty($meta['source_etag']) && $remoteSourceEtag === $meta['source_etag']) {
            return true;
        }

        return $remoteCrc32c
            && !empty($meta['content_crc32c'])
            && $remoteCrc32c === $meta['content_crc32c'];
    }

    private function updateSourceMeta(
        array $meta,
        ?string $sourceEtag,
        ?string $sourceLastModified,
        ?int $checkedAt = null
    ): array {
        $meta = $this->clearRefreshFailure($meta);
        $meta['source_etag'] = $sourceEtag;
        if ($sourceLastModified) {
            $meta['source_last_modified'] = $sourceLastModified;
        }
        $meta['checked_at'] = $checkedAt ?? time();
        return $meta;
    }

    private function sourceLastModifiedFromInfo(array $info): ?string
    {
        if (!isset($info['updated'])) {
            return null;
        }

        return $this->httpDateFromDateTime($info['updated']);
    }

    private function refreshBackoffActive(array $meta, int $ttl): bool
    {
        if (empty($meta['refresh_failed_at']) || !is_numeric($meta['refresh_failed_at'])) {
            return false;
        }

        return (time() - (int) $meta['refresh_failed_at']) < $ttl;
    }

    private function markRefreshFailed(array $meta, ?int $failedAt = null): array
    {
        $meta['refresh_failed_at'] = $failedAt ?? time();
        return $meta;
    }

    private function clearRefreshFailure(array $meta): array
    {
        unset($meta['refresh_failed_at']);
        return $meta;
    }

    // Convert a source timestamp to the UTC HTTP-date format used in response headers.
    private function httpDateFromDateTime(string $dateTime): string
    {
        return (new \DateTimeImmutable($dateTime))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('D, d M Y H:i:s \G\M\T');
    }

    // Parse a stored HTTP-date into a UTC DateTime for Symfony response handling.
    private function dateTimeFromHttpDate(?string $httpDate): ?\DateTimeImmutable
    {
        if (!$httpDate) {
            return null;
        }

        $timestamp = strtotime($httpDate);
        if ($timestamp === false) {
            return null;
        }

        return (new \DateTimeImmutable("@{$timestamp}"))->setTimezone(new \DateTimeZone('UTC'));
    }

    private function isUsableMeta(array $meta): bool
    {
        return !empty($meta['content_crc32c'])
            && !empty($meta['content_md5'])
            && !empty($meta['http_etag'])
            && !empty($meta['source_last_modified'])
            && array_key_exists('checked_at', $meta)
            && is_numeric($meta['checked_at'])
            && array_key_exists('size', $meta)
            && is_numeric($meta['size'])
            && (!array_key_exists('refresh_failed_at', $meta) || is_numeric($meta['refresh_failed_at']));
    }

    private function writeMeta(string $format, string $folder, array $meta): void
    {
        $path = $this->metaPath($format, $folder);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    // ─── GCS client ──────────────────────────────────────────────────

    protected function getGcsClient(): ?StorageClient
    {
        $bucket = config('filesystems.disks.gcs.bucket');

        if (empty($bucket)) {
            return null;
        }

        $config = ['suppressKeyFileNotice' => true];

        $projectId = config('filesystems.disks.gcs.project_id');
        if (!empty($projectId)) {
            $config['projectId'] = $projectId;
        }

        $keyFile = config('filesystems.disks.gcs.key_file');
        if (!empty($keyFile) && file_exists($keyFile)) {
            $config['keyFilePath'] = $keyFile;
        }

        return new StorageClient($config);
    }
}
