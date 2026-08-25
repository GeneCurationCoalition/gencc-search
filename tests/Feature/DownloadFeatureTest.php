<?php

namespace Tests\Feature;

use App\Http\Controllers\DownloadController;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class DownloadFeatureTest extends TestCase
{
    /**
     * Seed a fixture file + .meta.json into the release cache directory.
     */
    private function seedCacheFixture(
        string $format = 'csv',
        string $folder = 'legacy',
        ?int $checkedAt = null,
        ?string $sourceEtag = null,
        ?int $refreshFailedAt = null
    ): string
    {
        $dir = storage_path("app/release-cache/{$folder}/{$format}");
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = "header1,header2\nvalue1,value2\n";
        file_put_contents("{$dir}/gencc-submissions.{$format}", $content);
        $meta = [
            'content_crc32c' => $this->crc32c($content),
            'content_md5' => base64_encode(hex2bin(md5($content))),
            'http_etag' => '"' . md5($content) . '"',
            'source_etag' => $sourceEtag ?? '"fixture-source-etag"',
            'source_last_modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            'checked_at' => $checkedAt ?? time(),
            'source' => 'fixture',
            'size' => strlen($content),
        ];
        file_put_contents("{$dir}/.meta.json", json_encode($meta), LOCK_EX);
        if ($refreshFailedAt !== null) {
            file_put_contents("{$dir}/.refresh-failed", (string) $refreshFailedAt, LOCK_EX);
        }

        return $content;
    }

    private function refreshFailedMarker(string $format, string $folder): ?int
    {
        $path = storage_path("app/release-cache/{$folder}/{$format}/.refresh-failed");
        if (!file_exists($path)) {
            return null;
        }
        return (int) trim(file_get_contents($path));
    }

    /**
     * Seed a fixture with an expired checked_at so the cache appears stale.
     */
    private function seedStaleCacheFixture(string $format = 'csv', string $folder = 'legacy'): string
    {
        return $this->seedCacheFixture($format, $folder, time() - 7200);
    }

    /**
     * Seed only the cached release file, without .meta.json.
     */
    private function seedCacheFileWithoutMeta(string $format = 'csv', string $folder = 'legacy'): string
    {
        $dir = storage_path("app/release-cache/{$folder}/{$format}");
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = "header1,header2\nvalue1,value2\n";
        file_put_contents("{$dir}/gencc-submissions.{$format}", $content);

        return $content;
    }

    /**
     * Seed a cached release file with the pre-content-hash metadata shape.
     */
    private function seedLegacyMetaCacheFixture(string $format = 'csv', string $folder = 'legacy'): string
    {
        $dir = storage_path("app/release-cache/{$folder}/{$format}");
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = "header1,header2\nvalue1,value2\n";
        file_put_contents("{$dir}/gencc-submissions.{$format}", $content);
        file_put_contents("{$dir}/.meta.json", json_encode([
            'md5_hash' => null,
            'etag' => '"' . md5($content) . '"',
            'last_modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            'checked_at' => time() - 7200,
            'source' => 'fixture',
            'size' => strlen($content),
        ]), LOCK_EX);

        return $content;
    }

    private function cleanupReleaseCache(): void
    {
        $cacheDir = storage_path('app/release-cache');
        if (is_dir($cacheDir)) {
            $this->recursiveDelete($cacheDir);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        rmdir($dir);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupReleaseCache();
        Cache::flush();

        // Disable GCS so tests use local cache only (no real GCS calls).
        config(['filesystems.disks.gcs.bucket' => null]);
    }

    protected function tearDown(): void
    {
        $this->cleanupReleaseCache();
        Mockery::close();
        parent::tearDown();
    }

    // ─── Basic download tests ────────────────────────────────────────

    /** @test */
    public function download_page_returns_200()
    {
        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertViewIs('download.index');
    }

    /** @test */
    public function download_page_shows_unavailable_banner_when_gcs_not_configured()
    {
        // GCS is already disabled in setUp
        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertSee('Downloads Temporarily Unavailable');
        $response->assertSee('pointer-events-none');
    }

    /** @test */
    public function download_page_shows_links_with_no_cache_when_gcs_configured()
    {
        // The action routes warm the release cache on demand. The page only
        // disables links when a recent refresh failure is known.
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);

        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertDontSee('Downloads Temporarily Unavailable');
        $response->assertDontSee('pointer-events-none');
    }

    /** @test */
    public function download_csv_returns_file()
    {
        $this->seedCacheFixture('csv');

        $response = $this->get('/download/action/submissions-export-csv');

        $response->assertStatus(200);
        $response->assertHeader('etag');
        $response->assertHeader('last-modified');
        $cacheControl = $response->headers->get('cache-control');
        $this->assertStringContains('no-cache', $cacheControl);
        $this->assertStringContains('public', $cacheControl);
    }

    /** @test */
    public function download_tsv_returns_file()
    {
        $this->seedCacheFixture('tsv');

        $response = $this->get('/download/action/submissions-export-tsv');

        $response->assertStatus(200);
        $response->assertHeader('etag');
        $response->assertHeader('last-modified');
    }

    /** @test */
    public function download_xlsx_returns_file()
    {
        $this->seedCacheFixture('xlsx');

        $response = $this->get('/download/action/submissions-export-xlsx');

        $response->assertStatus(200);
        $response->assertHeader('etag');
        $response->assertHeader('last-modified');
    }

    /** @test */
    public function download_xls_route_is_removed()
    {
        $response = $this->get('/download/action/submissions-export-xls');

        $response->assertStatus(404);
    }

    // ─── 503 when no cache and no GCS ────────────────────────────────

    /** @test */
    public function download_returns_503_when_no_cache_and_no_gcs()
    {
        // No fixture seeded, GCS disabled in setUp
        $response = $this->get('/download/action/submissions-export-csv');

        $response->assertStatus(503);
    }

    // ─── Conditional request tests ───────────────────────────────────

    /** @test */
    public function download_returns_304_on_matching_etag()
    {
        $this->seedCacheFixture('csv');

        // First request — get the ETag
        $response = $this->get('/download/action/submissions-export-csv');
        $response->assertStatus(200);
        $etag = $response->headers->get('ETag');
        $this->assertNotEmpty($etag);

        // Second request with If-None-Match — should get 304
        $response = $this->get('/download/action/submissions-export-csv', [
            'If-None-Match' => $etag,
        ]);
        $response->assertStatus(304);
    }

    /** @test */
    public function download_returns_304_on_matching_if_modified_since()
    {
        $this->seedCacheFixture('csv');

        // First request — get Last-Modified
        $response = $this->get('/download/action/submissions-export-csv');
        $response->assertStatus(200);
        $lastModified = $response->headers->get('Last-Modified');
        $this->assertNotEmpty($lastModified);

        // Second request with If-Modified-Since — should get 304
        $response = $this->get('/download/action/submissions-export-csv', [
            'If-Modified-Since' => $lastModified,
        ]);
        $response->assertStatus(304);
    }

    /** @test */
    public function download_returns_200_on_mismatched_etag()
    {
        $this->seedCacheFixture('csv');

        // Request with wrong ETag — should get 200
        $response = $this->get('/download/action/submissions-export-csv', [
            'If-None-Match' => '"bogus-etag"',
        ]);
        $response->assertStatus(200);
    }

    // ─── Cache behavior tests ────────────────────────────────────────

    /** @test */
    public function download_serves_from_cache_with_consistent_etag()
    {
        $this->seedCacheFixture('csv');

        $response1 = $this->get('/download/action/submissions-export-csv');
        $response1->assertStatus(200);
        $etag1 = $response1->headers->get('ETag');

        $response2 = $this->get('/download/action/submissions-export-csv');
        $response2->assertStatus(200);
        $etag2 = $response2->headers->get('ETag');

        $this->assertEquals($etag1, $etag2);
    }

    /** @test */
    public function fresh_cache_is_served_without_gcs_check()
    {
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);
        $this->seedCacheFixture('csv');

        $client = Mockery::mock(StorageClient::class);
        $client->shouldNotReceive('bucket');
        app()->instance(DownloadController::class, new class($client) extends DownloadController {
            private $client;

            public function __construct(StorageClient $client)
            {
                $this->client = $client;
            }

            protected function getGcsClient(): ?StorageClient
            {
                return $this->client;
            }
        });

        $this->get('/download/action/submissions-export-csv')->assertStatus(200);
    }

    /** @test */
    public function cached_file_without_meta_returns_503_when_no_gcs_is_available()
    {
        $this->seedCacheFileWithoutMeta('csv');

        $this->get('/download/action/submissions-export-csv')->assertStatus(503);
    }

    /** @test */
    public function cached_file_without_meta_redownloads_from_gcs_and_writes_meta()
    {
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);
        $this->seedCacheFileWithoutMeta('csv');
        $downloadedContent = "new-header\nnew-value\n";
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andReturn(true);
        $object->shouldReceive('info')->once()->andReturn([
            'etag' => '"remote-v2"',
            'crc32c' => $this->crc32c($downloadedContent),
            'updated' => '2026-05-01T12:00:00.000Z',
        ]);
        $object->shouldReceive('downloadToFile')->once()->with(Mockery::type('string'))->andReturnUsing(function ($path) use ($downloadedContent) {
            file_put_contents($path, $downloadedContent);
        });
        $this->bindMockGcsObject($object);

        $response = $this->get('/download/action/submissions-export-csv');

        $response->assertStatus(200);
        $meta = json_decode(file_get_contents(storage_path('app/release-cache/legacy/csv/.meta.json')), true);
        $this->assertSame($this->crc32c($downloadedContent), $meta['content_crc32c']);
        $this->assertSame(base64_encode(hex2bin(md5($downloadedContent))), $meta['content_md5']);
        $this->assertSame('"' . md5($downloadedContent) . '"', $meta['http_etag']);
        $this->assertSame('"remote-v2"', $meta['source_etag']);
        $this->assertSame('Fri, 01 May 2026 12:00:00 GMT', $meta['source_last_modified']);
    }

    /** @test */
    public function legacy_meta_redownloads_from_gcs_and_writes_current_meta()
    {
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);
        $this->seedLegacyMetaCacheFixture('csv');
        $downloadedContent = "new-header\nnew-value\n";
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andReturn(true);
        $object->shouldReceive('info')->once()->andReturn([
            'etag' => '"remote-v2"',
            'crc32c' => $this->crc32c($downloadedContent),
            'updated' => '2026-05-01T12:00:00.000Z',
        ]);
        $object->shouldReceive('downloadToFile')->once()->with(Mockery::type('string'))->andReturnUsing(function ($path) use ($downloadedContent) {
            file_put_contents($path, $downloadedContent);
        });
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv')->assertStatus(200);

        $this->assertSame($downloadedContent, file_get_contents(storage_path('app/release-cache/legacy/csv/gencc-submissions.csv')));
        $meta = json_decode(file_get_contents(storage_path('app/release-cache/legacy/csv/.meta.json')), true);
        $this->assertSame($this->crc32c($downloadedContent), $meta['content_crc32c']);
        $this->assertSame('"' . md5($downloadedContent) . '"', $meta['http_etag']);
        $this->assertArrayNotHasKey('etag', $meta);
        $this->assertArrayNotHasKey('last_modified', $meta);
    }

    /** @test */
    public function gcs_updated_timestamp_is_stored_and_returned_as_utc_http_date()
    {
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);
        $this->seedCacheFileWithoutMeta('csv');
        $downloadedContent = "new-header\nnew-value\n";
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andReturn(true);
        $object->shouldReceive('info')->once()->andReturn([
            'etag' => '"remote-v2"',
            'crc32c' => $this->crc32c($downloadedContent),
            'updated' => '2026-05-01T08:00:00-04:00',
        ]);
        $object->shouldReceive('downloadToFile')->once()->with(Mockery::type('string'))->andReturnUsing(function ($path) use ($downloadedContent) {
            file_put_contents($path, $downloadedContent);
        });
        $this->bindMockGcsObject($object);

        $response = $this->get('/download/action/submissions-export-csv');

        $response->assertStatus(200);
        $this->assertSame('Fri, 01 May 2026 12:00:00 GMT', $response->headers->get('Last-Modified'));
        $meta = json_decode(file_get_contents(storage_path('app/release-cache/legacy/csv/.meta.json')), true);
        $this->assertSame('Fri, 01 May 2026 12:00:00 GMT', $meta['source_last_modified']);
    }

    /** @test */
    public function expired_cache_with_matching_source_etag_bumps_ttl_without_download()
    {
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);
        $this->seedCacheFixture('csv', 'legacy', time() - 7200, '"remote-v1"');
        $before = json_decode(file_get_contents(storage_path('app/release-cache/legacy/csv/.meta.json')), true);
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andReturn(true);
        $object->shouldReceive('info')->once()->andReturn([
            'etag' => '"remote-v1"',
            'crc32c' => 'different-but-ignored-because-etag-matches',
            'updated' => '2026-05-01T12:00:00.000Z',
        ]);
        $object->shouldNotReceive('downloadToFile');
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv')->assertStatus(200);

        $after = json_decode(file_get_contents(storage_path('app/release-cache/legacy/csv/.meta.json')), true);
        $this->assertSame($before['content_crc32c'], $after['content_crc32c']);
        $this->assertGreaterThan($before['checked_at'], $after['checked_at']);
        $this->assertSame('Fri, 01 May 2026 12:00:00 GMT', $after['source_last_modified']);
    }

    /** @test */
    public function expired_cache_with_matching_crc32c_bumps_ttl_without_download()
    {
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);
        $content = $this->seedCacheFixture('csv', 'legacy', time() - 7200, '"remote-v1"');
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andReturn(true);
        $object->shouldReceive('info')->once()->andReturn([
            'etag' => '"remote-v2"',
            'crc32c' => $this->crc32c($content),
            'updated' => '2026-05-02T12:00:00.000Z',
        ]);
        $object->shouldNotReceive('downloadToFile');
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv')->assertStatus(200);

        $meta = json_decode(file_get_contents(storage_path('app/release-cache/legacy/csv/.meta.json')), true);
        $this->assertSame('"remote-v2"', $meta['source_etag']);
        $this->assertSame('Sat, 02 May 2026 12:00:00 GMT', $meta['source_last_modified']);
        $this->assertGreaterThan(time() - 60, $meta['checked_at']);
    }

    /** @test */
    public function expired_cache_with_changed_validators_downloads_and_replaces_cache()
    {
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);
        $this->seedCacheFixture('csv', 'legacy', time() - 7200, '"remote-v1"');
        $downloadedContent = "changed-header\nchanged-value\n";
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andReturn(true);
        $object->shouldReceive('info')->once()->andReturn([
            'etag' => '"remote-v2"',
            'crc32c' => $this->crc32c($downloadedContent),
            'updated' => '2026-05-03T12:00:00.000Z',
        ]);
        $object->shouldReceive('downloadToFile')->once()->with(Mockery::type('string'))->andReturnUsing(function ($path) use ($downloadedContent) {
            file_put_contents($path, $downloadedContent);
        });
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv')->assertStatus(200);

        $this->assertSame($downloadedContent, file_get_contents(storage_path('app/release-cache/legacy/csv/gencc-submissions.csv')));
        $meta = json_decode(file_get_contents(storage_path('app/release-cache/legacy/csv/.meta.json')), true);
        $this->assertSame($this->crc32c($downloadedContent), $meta['content_crc32c']);
        $this->assertSame('"remote-v2"', $meta['source_etag']);
    }

    /** @test */
    public function failed_gcs_check_with_usable_stale_meta_returns_503_and_writes_marker()
    {
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);
        $this->seedCacheFixture('csv', 'legacy', time() - 7200, '"remote-v1"');
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andThrow(new \RuntimeException('GCS unavailable'));
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv')->assertStatus(503);

        $this->assertGreaterThan(time() - 60, $this->refreshFailedMarker('csv', 'legacy'));
    }

    /** @test */
    public function failed_gcs_refresh_sets_backoff_and_subsequent_requests_skip_gcs_and_return_503()
    {
        config([
            'downloads.cache_ttl' => 3600,
            'filesystems.disks.gcs.bucket' => 'test-bucket',
        ]);
        $this->seedCacheFixture('csv', 'legacy', time() - 7200, '"remote-v1"');
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andThrow(new \RuntimeException('GCS unavailable'));
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv')->assertStatus(503);
        $this->assertGreaterThan(time() - 60, $this->refreshFailedMarker('csv', 'legacy'));

        // Second request must not hit GCS — `exists` was set to once() above.
        $this->get('/download/action/submissions-export-csv')->assertStatus(503);
    }

    /** @test */
    public function expired_refresh_backoff_retries_gcs_and_clears_failure_marker_on_success()
    {
        config([
            'downloads.cache_ttl' => 3600,
            'filesystems.disks.gcs.bucket' => 'test-bucket',
        ]);
        $content = $this->seedCacheFixture('csv', 'legacy', time() - 7200, '"remote-v1"', time() - 7200);
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andReturn(true);
        $object->shouldReceive('info')->once()->andReturn([
            'etag' => '"remote-v1"',
            'crc32c' => $this->crc32c($content),
            'updated' => '2026-05-01T12:00:00.000Z',
        ]);
        $object->shouldNotReceive('downloadToFile');
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv')->assertStatus(200);

        $meta = json_decode(file_get_contents(storage_path('app/release-cache/legacy/csv/.meta.json')), true);
        $this->assertNull($this->refreshFailedMarker('csv', 'legacy'));
        $this->assertGreaterThan(time() - 60, $meta['checked_at']);
    }

    /** @test */
    public function missing_gcs_object_returns_503_and_writes_marker()
    {
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);
        $this->seedCacheFixture('csv', 'legacy', time() - 7200, '"remote-v1"');
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andReturn(false);
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv')->assertStatus(503);

        $this->assertGreaterThan(time() - 60, $this->refreshFailedMarker('csv', 'legacy'));
    }

    /** @test */
    public function downloaded_crc32c_mismatch_returns_503_without_replacing_cache()
    {
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);
        $originalContent = $this->seedCacheFixture('csv', 'legacy', time() - 7200, '"remote-v1"');
        $downloadedContent = "changed-header\nchanged-value\n";
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andReturn(true);
        $object->shouldReceive('info')->once()->andReturn([
            'etag' => '"remote-v2"',
            'crc32c' => 'not-the-downloaded-crc32c',
            'updated' => '2026-05-01T12:00:00.000Z',
        ]);
        $object->shouldReceive('downloadToFile')->once()->with(Mockery::type('string'))->andReturnUsing(function ($path) use ($downloadedContent) {
            file_put_contents($path, $downloadedContent);
        });
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv')->assertStatus(503);
        $this->assertSame($originalContent, file_get_contents(storage_path('app/release-cache/legacy/csv/gencc-submissions.csv')));
        $this->assertGreaterThan(time() - 60, $this->refreshFailedMarker('csv', 'legacy'));
    }

    /** @test */
    public function failed_gcs_check_with_missing_meta_returns_503()
    {
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);
        $this->seedCacheFileWithoutMeta('csv');
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andThrow(new \RuntimeException('GCS unavailable'));
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv')->assertStatus(503);
    }

    /** @test */
    public function failed_gcs_with_no_meta_writes_backoff_marker_so_subsequent_requests_skip_gcs()
    {
        // Cold-start case: no .meta.json on disk and GCS is broken.
        // Without a sidecar marker the controller would re-hit GCS forever.
        config([
            'downloads.cache_ttl' => 3600,
            'filesystems.disks.gcs.bucket' => 'test-bucket',
        ]);
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andThrow(new \RuntimeException('GCS unavailable'));
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv')->assertStatus(503);
        $this->assertGreaterThan(time() - 60, $this->refreshFailedMarker('csv', 'legacy'));

        // Second request must not hit GCS — `exists` was set to once() above.
        $this->get('/download/action/submissions-export-csv')->assertStatus(503);
    }

    /** @test */
    public function backoff_active_returns_503_without_calling_gcs()
    {
        config([
            'downloads.cache_ttl' => 3600,
            'filesystems.disks.gcs.bucket' => 'test-bucket',
        ]);
        $this->seedCacheFixture('csv', 'legacy', time() - 7200, '"remote-v1"', time() - 60);
        $client = Mockery::mock(StorageClient::class);
        $client->shouldNotReceive('bucket');
        app()->instance(DownloadController::class, new class($client) extends DownloadController {
            private $client;

            public function __construct(StorageClient $client)
            {
                $this->client = $client;
            }

            protected function getGcsClient(): ?StorageClient
            {
                return $this->client;
            }
        });

        $this->get('/download/action/submissions-export-csv')->assertStatus(503);
    }

    // ─── No session cookies test ─────────────────────────────────────

    /** @test */
    public function download_routes_do_not_set_session_cookies()
    {
        $this->seedCacheFixture('csv');

        $response = $this->get('/download/action/submissions-export-csv');
        $response->assertStatus(200);

        $cookies = $response->headers->getCookies();
        $cookieNames = array_map(function ($c) { return $c->getName(); }, $cookies);

        $this->assertNotContains('XSRF-TOKEN', $cookieNames);
        $this->assertNotContains(config('session.cookie'), $cookieNames);
    }

    // ─── Daily quota tests ───────────────────────────────────────────

    /** @test */
    public function download_enforces_daily_quota()
    {
        $this->seedCacheFixture('csv');
        config(['downloads.daily_quota' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->get('/download/action/submissions-export-csv')->assertStatus(200);
        }

        // 4th request should hit quota
        $response = $this->get('/download/action/submissions-export-csv');
        $response->assertStatus(429);
        $this->assertStringContains('Daily download limit reached', $response->getContent());
    }

    /** @test */
    public function release_formats_and_versions_share_one_quota_bucket()
    {
        $this->seedCacheFixture('csv', 'legacy');
        $this->seedCacheFixture('tsv', 'current');
        config(['downloads.daily_quota' => 2]);

        $this->get('/download/action/submissions-export-csv')->assertOk();
        $this->get('/download/action/submissions-export-tsv?format=new')->assertOk();
        $this->get('/download/action/submissions-export-csv')->assertStatus(429);
    }

    /** @test */
    public function download_304_does_not_count_against_quota()
    {
        $this->seedCacheFixture('csv');
        config(['downloads.daily_quota' => 3]);

        // First request — get ETag
        $response = $this->get('/download/action/submissions-export-csv');
        $response->assertStatus(200);
        $etag = $response->headers->get('ETag');

        // Make many conditional requests — none should count
        for ($i = 0; $i < 10; $i++) {
            $this->get('/download/action/submissions-export-csv', [
                'If-None-Match' => $etag,
            ])->assertStatus(304);
        }

        // Should still be able to make 2 more full downloads (quota is 3, used 1)
        $this->get('/download/action/submissions-export-csv')->assertStatus(200);
        $this->get('/download/action/submissions-export-csv')->assertStatus(200);

        // 4th full download should hit quota
        $this->get('/download/action/submissions-export-csv')->assertStatus(429);
    }

    /** @test */
    public function over_quota_conditional_request_can_refresh_expired_cache_and_return_304()
    {
        config([
            'downloads.daily_quota' => 0,
            'filesystems.disks.gcs.bucket' => 'test-bucket',
        ]);
        $content = $this->seedCacheFixture('csv', 'legacy', time() - 7200, '"remote-v1"');
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andReturn(true);
        $object->shouldReceive('info')->once()->andReturn([
            'etag' => '"remote-v1"',
            'crc32c' => $this->crc32c($content),
            'updated' => '2026-05-01T12:00:00.000Z',
        ]);
        $object->shouldNotReceive('downloadToFile');
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv', [
            'If-None-Match' => '"' . md5($content) . '"',
        ])->assertStatus(304);
    }

    /** @test */
    public function over_quota_conditional_request_returns_429_when_remote_file_changed()
    {
        config([
            'downloads.daily_quota' => 0,
            'filesystems.disks.gcs.bucket' => 'test-bucket',
        ]);
        $content = $this->seedCacheFixture('csv', 'legacy', time() - 7200, '"remote-v1"');
        $downloadedContent = "changed-header\nchanged-value\n";
        $object = Mockery::mock();
        $object->shouldReceive('exists')->once()->andReturn(true);
        $object->shouldReceive('info')->once()->andReturn([
            'etag' => '"remote-v2"',
            'crc32c' => $this->crc32c($downloadedContent),
            'updated' => '2026-05-01T12:00:00.000Z',
        ]);
        $object->shouldReceive('downloadToFile')->once()->with(Mockery::type('string'))->andReturnUsing(function ($path) use ($downloadedContent) {
            file_put_contents($path, $downloadedContent);
        });
        $this->bindMockGcsObject($object);

        $this->get('/download/action/submissions-export-csv', [
            'If-None-Match' => '"' . md5($content) . '"',
        ])->assertStatus(429);
    }

    // ─── Page health tests ───────────────────────────────────────────

    /** @test */
    public function download_page_disables_links_when_cache_stale_and_no_gcs()
    {
        // Need every (format, version) cached but stale, so the only failing
        // health check is "stale and cannot refresh".
        config(['downloads.cache_ttl' => 3600]);
        foreach (['xlsx', 'csv', 'tsv'] as $format) {
            foreach (['legacy', 'current'] as $version) {
                $this->seedCacheFixture($format, $version, time() - 7200);
            }
        }

        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertSee('Downloads Temporarily Unavailable');
        $response->assertSee('pointer-events-none');
        $response->assertDontSee('Downloads May Be Out of Date');
    }

    /** @test */
    public function download_page_disables_links_when_backoff_active_even_with_gcs_configured()
    {
        config([
            'downloads.cache_ttl' => 3600,
            'filesystems.disks.gcs.bucket' => 'test-bucket',
        ]);
        // Every (format, version) is stale AND in backoff — health check fails everywhere.
        foreach (['xlsx', 'csv', 'tsv'] as $format) {
            foreach (['legacy', 'current'] as $version) {
                $this->seedCacheFixture($format, $version, time() - 7200, '"remote-v1"', time() - 60);
            }
        }

        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertSee('Downloads Temporarily Unavailable');
    }

    /** @test */
    public function download_page_enables_links_when_every_format_has_fresh_cache()
    {
        foreach (['xlsx', 'csv', 'tsv'] as $format) {
            foreach (['legacy', 'current'] as $version) {
                $this->seedCacheFixture($format, $version);
            }
        }

        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertDontSee('Downloads Temporarily Unavailable');
        $response->assertDontSee('pointer-events-none');
    }

    /** @test */
    public function download_page_shows_links_when_all_caches_stale_if_gcs_configured()
    {
        // Stale cache entries are refreshed by the action routes.
        $this->seedStaleCacheFixture('csv', 'legacy');
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);

        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertDontSee('Downloads Temporarily Unavailable');
        $response->assertDontSee('pointer-events-none');
    }

    /** @test */
    public function download_page_enables_links_when_at_least_one_combo_has_fresh_cache_without_gcs()
    {
        $this->seedCacheFixture('csv', 'legacy');
        $this->seedStaleCacheFixture('xlsx', 'current');

        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertDontSee('Downloads Temporarily Unavailable');
        $response->assertDontSee('pointer-events-none');
    }

    /** @test */
    public function download_page_ignores_and_clears_failure_marker_older_than_fresh_cache()
    {
        config(['downloads.cache_ttl' => 3600]);
        $this->seedCacheFixture('csv', 'legacy', time(), '"remote-v1"', time() - 60);

        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertDontSee('Downloads Temporarily Unavailable');
        $response->assertDontSee('pointer-events-none');
        $this->assertNull($this->refreshFailedMarker('csv', 'legacy'));
    }

    // ─── HEAD request tests ──────────────────────────────────────────

    /** @test */
    public function head_request_returns_200_with_etag_headers()
    {
        $this->seedCacheFixture('csv');

        $response = $this->call('HEAD', '/download/action/submissions-export-csv');

        $response->assertStatus(200);
        $response->assertHeader('etag');
        $response->assertHeader('last-modified');
        $this->assertEmpty($response->getContent());
    }

    /** @test */
    public function head_request_returns_503_when_no_cache_and_no_gcs()
    {
        // No fixture seeded, GCS disabled in setUp
        $response = $this->call('HEAD', '/download/action/submissions-export-csv');

        $response->assertStatus(503);
    }

    /** @test */
    public function head_request_does_not_count_against_quota()
    {
        $this->seedCacheFixture('csv');
        config(['downloads.daily_quota' => 2]);

        // Exhaust the quota with GET requests
        $this->get('/download/action/submissions-export-csv')->assertStatus(200);
        $this->get('/download/action/submissions-export-csv')->assertStatus(200);
        $this->get('/download/action/submissions-export-csv')->assertStatus(429);

        // HEAD should still succeed despite exhausted quota
        $response = $this->call('HEAD', '/download/action/submissions-export-csv');
        $response->assertStatus(200);
    }

    // ─── Helper ──────────────────────────────────────────────────────

    private function crc32c(string $content): string
    {
        return base64_encode(hex2bin(hash('crc32c', $content)));
    }

    private function bindMockGcsObject(
        $object,
        string $bucketName = 'test-bucket',
        string $path = 'releases/legacy/csv/gencc-submissions.csv'
    ): void {
        $client = Mockery::mock(StorageClient::class);
        $bucket = Mockery::mock();

        $client->shouldReceive('bucket')->with($bucketName)->andReturn($bucket);
        $bucket->shouldReceive('object')->with($path)->andReturn($object);

        app()->instance(DownloadController::class, new class($client) extends DownloadController {
            private $client;

            public function __construct(StorageClient $client)
            {
                $this->client = $client;
            }

            protected function getGcsClient(): ?StorageClient
            {
                return $this->client;
            }
        });
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
