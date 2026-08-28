<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/** Atomically reserves independent daily per-IP download quotas. */
class DownloadQuota
{
    public const RELEASE = 'release';
    public const CONFLICT_VIEWER = 'conflict-viewer';

    /** Reserve one full-file transfer from a page-specific bucket. */
    public function reserve(string $bucket, string $ip): bool
    {
        $this->validateBucket($bucket);

        return Cache::lock($this->lockKey($bucket, $ip), 10)->block(5, function () use ($bucket, $ip) {
            $key = $this->key($bucket, $ip);
            $count = (int) Cache::get($key, 0);
            $limit = $this->limit();

            if ($count >= $limit) {
                return false;
            }

            Cache::put($key, $count + 1, now()->endOfDay());

            return true;
        });
    }

    /** Return a reservation when preparing its response did not succeed. */
    public function release(string $bucket, string $ip): void
    {
        $this->validateBucket($bucket);

        Cache::lock($this->lockKey($bucket, $ip), 10)->block(5, function () use ($bucket, $ip) {
            $key = $this->key($bucket, $ip);
            $count = (int) Cache::get($key, 0);

            if ($count <= 1) {
                Cache::forget($key);
            } else {
                Cache::put($key, $count - 1, now()->endOfDay());
            }
        });
    }

    public function rejectionResponse(): Response
    {
        $limit = $this->limit();

        return response(
            "Daily download limit reached ({$limit} per day per IP).\n"
            . "This data is updated weekly. Please reduce your polling frequency.\n"
            . "Tip: use a HEAD request and If-None-Match or If-Modified-Since headers to\n"
            . "check for changes without counting against this limit.\n",
            429,
            [
                'Content-Type' => 'text/plain',
                'Retry-After' => (string) (now()->endOfDay()->timestamp - now()->timestamp + 1),
            ]
        );
    }

    private function limit(): int
    {
        return max(0, (int) config('downloads.daily_quota'));
    }

    private function key(string $bucket, string $ip): string
    {
        return 'download-quota:' . $bucket . ':' . $ip . ':' . now()->toDateString();
    }

    private function lockKey(string $bucket, string $ip): string
    {
        return $this->key($bucket, $ip) . ':lock';
    }

    private function validateBucket(string $bucket): void
    {
        if (! in_array($bucket, [self::RELEASE, self::CONFLICT_VIEWER], true)) {
            throw new \InvalidArgumentException("Unknown download quota bucket: {$bucket}");
        }
    }
}
