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
        'xls' => 'application/vnd.ms-excel',
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
        $downloadsAvailable = !empty(config('filesystems.disks.gcs.bucket'));

        return view('download.index', [
            'page_meta' => $page_meta,
            'downloads_available' => $downloadsAvailable,
        ]);
    }

    // ─── Public export endpoints ─────────────────────────────────────

    public function export_XLSX() { return $this->handleExport('xlsx'); }
    public function export_CSV()  { return $this->handleExport('csv'); }
    public function export_TSV()  { return $this->handleExport('tsv'); }
    public function export_XLS()  { return $this->handleExport('xls'); }

    // ─── Main orchestration ──────────────────────────────────────────

    private function handleExport(string $format): Response
    {
        $folder = request()->query('format') === 'new' ? 'current' : 'legacy';

        // 1. Fast 304 check against cached metadata (no GCS call, no quota impact)
        $notModified = $this->checkNotModified($format, $folder);
        if ($notModified) {
            return $notModified;
        }

        // 2. Enforce daily per-IP quota (only full downloads count)
        $quotaResponse = $this->enforceDownloadQuota();
        if ($quotaResponse) {
            return $quotaResponse;
        }

        // 3. Resolve the file (local cache or GCS)
        $filePath = $this->resolveCachedFile($format, $folder);
        if ($filePath === null) {
            abort(503, 'Release files are temporarily unavailable. Please try again later.');
        }

        // 4. Build BinaryFileResponse with ETag/Last-Modified headers
        $response = $this->buildFileResponse($format, $folder);

        // 5. Let Symfony check if response is actually 304
        //    (edge case: newly-fetched file matches client conditional headers)
        if ($response->isNotModified(request())) {
            return $response;
        }

        // 6. Full download — count against quota
        $this->incrementDownloadQuota();

        return $response;
    }

    // ─── Conditional request handling (304) ───────────────────────────

    private function checkNotModified(string $format, string $folder): ?Response
    {
        $meta = $this->readMeta($format, $folder);
        if (!$meta) {
            return null;
        }

        $ifNoneMatch = request()->header('If-None-Match');
        $ifModifiedSince = request()->header('If-Modified-Since');

        if (!$ifNoneMatch && !$ifModifiedSince) {
            return null;
        }

        // ETag match
        if ($ifNoneMatch && isset($meta['etag'])) {
            $clientEtags = array_map('trim', explode(',', $ifNoneMatch));
            foreach ($clientEtags as $clientEtag) {
                if ($clientEtag === $meta['etag'] || $clientEtag === '"*"') {
                    return $this->notModifiedResponse($meta);
                }
            }
            return null;
        }

        // Last-Modified match
        if ($ifModifiedSince && isset($meta['last_modified'])) {
            $clientTime = strtotime($ifModifiedSince);
            $serverTime = strtotime($meta['last_modified']);
            if ($clientTime !== false && $serverTime !== false && $serverTime <= $clientTime) {
                return $this->notModifiedResponse($meta);
            }
        }

        return null;
    }

    private function notModifiedResponse(array $meta): Response
    {
        return response('', 304, [
            'ETag' => $meta['etag'],
            'Last-Modified' => $meta['last_modified'] ?? '',
            'Cache-Control' => 'public, no-cache',
        ]);
    }

    // ─── Daily per-IP download quota ─────────────────────────────────

    private function enforceDownloadQuota(): ?Response
    {
        $dailyQuota = config('filesystems.disks.gcs.daily_quota', 20);
        $key = 'download-quota:' . request()->ip() . ':' . date('Y-m-d');
        $count = Cache::get($key, 0);

        if ($count >= $dailyQuota) {
            return response(
                "Daily download limit reached ({$dailyQuota} per day per IP).\n"
                . "This data is updated weekly. Please reduce your polling frequency.\n"
                . "Tip: use If-None-Match or If-Modified-Since headers to check for changes without counting against this limit.\n",
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
        $key = 'download-quota:' . request()->ip() . ':' . date('Y-m-d');

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
        $ttl = config('filesystems.disks.gcs.cache_ttl', 3600);

        // Cache exists and TTL not expired — serve from disk
        if ($meta && file_exists($filePath) && (time() - ($meta['checked_at'] ?? 0)) < $ttl) {
            return $filePath;
        }

        // GCS configured — check for changes, re-download if needed
        $client = $this->getGcsClient();
        if ($client) {
            return $this->resolveFromGcs($client, $format, $folder, $filePath, $meta);
        }

        // No GCS, but stale cache exists — serve it
        if (file_exists($filePath)) {
            Log::warning("GCS not configured, serving stale cached file for {$folder}/{$format}");
            return $filePath;
        }

        Log::error("Download unavailable: GOOGLE_CLOUD_STORAGE_BUCKET is not configured and no cached file exists for {$folder}/{$format}");
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
                return null;
            }

            $info = $object->info();
            $remoteMd5 = $info['md5Hash'] ?? null;

            // MD5 unchanged — just update checked_at
            if ($meta && file_exists($filePath) && $remoteMd5 && ($meta['md5_hash'] ?? null) === $remoteMd5) {
                $meta['checked_at'] = time();
                $this->writeMeta($format, $folder, $meta);
                return $filePath;
            }

            // Changed or no cache — download to temp file, then atomic rename
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $tempPath = $filePath . '.tmp.' . getmypid();
            $object->downloadToFile($tempPath);
            rename($tempPath, $filePath);

            $lastModified = isset($info['updated'])
                ? (new \DateTime($info['updated']))->format('D, d M Y H:i:s') . ' GMT'
                : gmdate('D, d M Y H:i:s') . ' GMT';

            $etag = $remoteMd5
                ? '"' . bin2hex(base64_decode($remoteMd5)) . '"'
                : '"' . md5_file($filePath) . '"';

            $this->writeMeta($format, $folder, [
                'md5_hash' => $remoteMd5,
                'etag' => $etag,
                'last_modified' => $lastModified,
                'checked_at' => time(),
                'source' => 'gcs',
                'size' => filesize($filePath),
            ]);

            return $filePath;
        } catch (\Exception $e) {
            Log::error("GCS cache refresh failed for {$folder}/{$format}: {$e->getMessage()}");

            // Serve stale cache if available
            if (file_exists($filePath)) {
                Log::warning("Serving stale cached file for {$folder}/{$format} after GCS error");
                return $filePath;
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
        $lastModified = $meta['last_modified'] ?? '';

        $response = response()->download($filePath, "gencc-submissions.{$format}", [
            'Content-Type' => $mimeType,
            'ETag' => $meta['etag'] ?? '',
            'Cache-Control' => 'public, no-cache',
        ]);

        // Set Last-Modified after BinaryFileResponse construction, because
        // setFile() auto-sets it to the file's mtime on disk, overriding
        // any value passed in the headers array.
        if ($lastModified) {
            $response->setLastModified(new \DateTime($lastModified));
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
        return is_array($data) ? $data : null;
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

    private function getGcsClient(): ?StorageClient
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
