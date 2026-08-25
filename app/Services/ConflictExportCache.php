<?php

namespace App\Services;

use App\Exports\ConflictSubmissionExport;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

/** Private, content-addressed cache for conflict-viewer export files. */
class ConflictExportCache
{
    public const VERSION = 'conflict-export-v1';

    public function identity(string $format, array $state, array $rows): array
    {
        $payload = [
            'version' => self::VERSION,
            'format' => $format,
            'state' => $state,
            'rows' => $rows,
        ];
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
        );
        $hash = hash('sha256', $json);

        return [
            'hash' => $hash,
            'format' => $format,
            'etag' => 'W/"' . self::VERSION . '-' . $hash . '"',
        ];
    }

    /** Return a fresh cached variant without creating one. */
    public function find(array $identity): ?array
    {
        $this->prune();

        $metaPath = $this->metaPath($identity['hash']);
        if (! is_file($metaPath)) {
            return null;
        }

        $meta = json_decode((string) file_get_contents($metaPath), true);
        if (! is_array($meta)
            || ($meta['version'] ?? null) !== self::VERSION
            || ($meta['hash'] ?? null) !== $identity['hash']
            || ($meta['format'] ?? null) !== $identity['format']
            || ($meta['etag'] ?? null) !== $identity['etag']
            || ! isset($meta['generated_at'], $meta['size'])
        ) {
            return null;
        }

        $path = $this->filePath($identity['hash'], $identity['format']);
        if (! is_file($path) || !$this->isFresh((int) $meta['generated_at'])) {
            $this->removeVariant($metaPath, $path);

            return null;
        }

        $size = filesize($path);
        if ($size === false || (int) $meta['size'] !== $size) {
            $this->removeVariant($metaPath, $path);

            return null;
        }

        $meta['path'] = $path;

        return $meta;
    }

    /** Generate once per content key, with a post-lock cache recheck. */
    public function generate(array $identity, ConflictSubmissionExport $export): array
    {
        return Cache::lock('conflict-export-cache:' . $identity['hash'], 300)
            ->block(60, function () use ($identity, $export) {
                $cached = $this->find($identity);
                if ($cached) {
                    return $cached;
                }

                $this->ensureDirectory();
                $suffix = '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(8));
                $target = $this->filePath($identity['hash'], $identity['format']);
                $temporary = $target . $suffix;
                $relativeTemporary = 'conflict-export-cache/' . basename($temporary);
                $writer = $identity['format'] === 'xlsx' ? Excel::XLSX : Excel::CSV;

                try {
                    if (! ExcelFacade::store($export, $relativeTemporary, 'local', $writer)) {
                        throw new \RuntimeException('Spreadsheet writer did not create the conflict export.');
                    }

                    if (! is_file($temporary) || ! rename($temporary, $target)) {
                        throw new \RuntimeException('Unable to move the generated conflict export into the cache.');
                    }

                    $size = filesize($target);
                    if ($size === false) {
                        throw new \RuntimeException('Unable to determine the generated conflict export size.');
                    }

                    $meta = [
                        'version' => self::VERSION,
                        'hash' => $identity['hash'],
                        'format' => $identity['format'],
                        'etag' => $identity['etag'],
                        'generated_at' => now()->timestamp,
                        'size' => $size,
                    ];
                    $metaPath = $this->metaPath($identity['hash']);
                    $temporaryMeta = $metaPath . $suffix;
                    $encoded = json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

                    if (file_put_contents($temporaryMeta, $encoded, LOCK_EX) === false
                        || ! rename($temporaryMeta, $metaPath)) {
                        throw new \RuntimeException('Unable to publish conflict export metadata.');
                    }

                    $meta['path'] = $target;

                    return $meta;
                } catch (\Throwable $e) {
                    foreach ([$temporary, $temporaryMeta ?? null] as $path) {
                        if (is_string($path) && is_file($path)) {
                            @unlink($path);
                        }
                    }

                    if (is_file($target) && ! is_file($this->metaPath($identity['hash']))) {
                        @unlink($target);
                    }

                    throw $e;
                }
            });
    }

    /** Remove expired variants and abandoned temporary files during normal traffic. */
    public function prune(): void
    {
        $directory = $this->directory();
        if (! is_dir($directory)) {
            return;
        }

        $now = now()->timestamp;
        $ttl = $this->ttl();

        foreach (glob($directory . '/*.meta.json') ?: [] as $metaPath) {
            $meta = json_decode((string) @file_get_contents($metaPath), true);
            $format = is_array($meta) ? ($meta['format'] ?? '') : '';
            $hash = is_array($meta) ? ($meta['hash'] ?? '') : '';
            $generatedAt = is_array($meta) ? (int) ($meta['generated_at'] ?? 0) : 0;

            if (!$this->isKnownFormat($format)
                || ! preg_match('/^[a-f0-9]{64}$/D', (string) $hash)
                || $generatedAt + $ttl <= $now) {
                $path = $this->isKnownFormat($format) && is_string($hash)
                    ? $this->filePath($hash, $format)
                    : null;
                $this->removeVariant($metaPath, $path);
            }
        }

        foreach (glob($directory . '/*') ?: [] as $path) {
            if (! is_file($path)) {
                continue;
            }

            $name = basename($path);
            $modifiedAt = filemtime($path) ?: 0;

            if (strpos($name, '.tmp.') !== false && $modifiedAt + $ttl <= time()) {
                @unlink($path);
                continue;
            }

            if (preg_match('/^([a-f0-9]{64})\.(csv|tsv|xlsx)$/D', $name, $matches)
                && ! is_file($this->metaPath($matches[1]))
                && $modifiedAt + $ttl <= time()) {
                @unlink($path);
            }
        }
    }

    private function ttl(): int
    {
        return max(0, (int) config('downloads.conflict_export_cache_ttl'));
    }

    private function isFresh(int $generatedAt): bool
    {
        return $generatedAt + $this->ttl() > now()->timestamp;
    }

    private function directory(): string
    {
        return storage_path('app/conflict-export-cache');
    }

    private function ensureDirectory(): void
    {
        if (! is_dir($this->directory()) && ! mkdir($this->directory(), 0755, true) && ! is_dir($this->directory())) {
            throw new \RuntimeException('Unable to create the conflict export cache directory.');
        }
    }

    private function filePath(string $hash, string $format): string
    {
        return $this->directory() . '/' . $hash . '.' . $format;
    }

    private function metaPath(string $hash): string
    {
        return $this->directory() . '/' . $hash . '.meta.json';
    }

    private function removeVariant(string $metaPath, ?string $filePath): void
    {
        if (is_file($metaPath)) {
            @unlink($metaPath);
        }
        if (is_string($filePath) && is_file($filePath)) {
            @unlink($filePath);
        }
    }

    private function isKnownFormat($format): bool
    {
        return is_string($format) && in_array($format, ['csv', 'tsv', 'xlsx'], true);
    }
}
