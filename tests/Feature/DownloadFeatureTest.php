<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DownloadFeatureTest extends TestCase
{
    /**
     * Seed a fixture file + .meta.json into the release cache directory.
     */
    private function seedCacheFixture(string $format = 'csv', string $folder = 'legacy'): void
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
            'checked_at' => time(),
            'source' => 'fixture',
            'size' => strlen($content),
        ]), LOCK_EX);
    }

    /**
     * Seed a fixture with an expired checked_at so the cache appears stale.
     */
    private function seedStaleCacheFixture(string $format = 'csv', string $folder = 'legacy'): void
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
            'last_modified' => gmdate('D, d M Y H:i:s', strtotime('-2 days')) . ' GMT',
            'checked_at' => time() - 7200, // 2 hours ago — exceeds default 3600s TTL
            'source' => 'fixture',
            'size' => strlen($content),
        ]), LOCK_EX);
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
    public function download_page_shows_download_links_when_gcs_configured()
    {
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
    public function download_xls_returns_file()
    {
        $this->seedCacheFixture('xls');

        $response = $this->get('/download/action/submissions-export-xls');

        $response->assertStatus(200);
        $response->assertHeader('etag');
        $response->assertHeader('last-modified');
    }

    // ─── 404 when no cache and no GCS ────────────────────────────────

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
        config(['filesystems.disks.gcs.daily_quota' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->get('/download/action/submissions-export-csv')->assertStatus(200);
        }

        // 4th request should hit quota
        $response = $this->get('/download/action/submissions-export-csv');
        $response->assertStatus(429);
        $this->assertStringContains('Daily download limit reached', $response->getContent());
    }

    /** @test */
    public function download_304_does_not_count_against_quota()
    {
        $this->seedCacheFixture('csv');
        config(['filesystems.disks.gcs.daily_quota' => 3]);

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

    // ─── Stale cache warning tests ───────────────────────────────────

    /** @test */
    public function download_page_shows_stale_warning_when_cache_exists_but_ttl_expired_and_no_gcs()
    {
        $this->seedStaleCacheFixture('csv', 'legacy');
        config(['filesystems.disks.gcs.cache_ttl' => 3600]);

        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertSee('Downloads May Be Out of Date');
        $response->assertDontSee('Downloads Temporarily Unavailable');
    }

    /** @test */
    public function download_page_does_not_show_stale_warning_when_cache_is_fresh()
    {
        $this->seedCacheFixture('csv', 'legacy');

        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertDontSee('Downloads May Be Out of Date');
    }

    /** @test */
    public function download_page_does_not_show_stale_warning_when_gcs_is_configured()
    {
        $this->seedStaleCacheFixture('csv', 'legacy');
        config(['filesystems.disks.gcs.bucket' => 'test-bucket']);

        $response = $this->get('/download');

        $response->assertStatus(200);
        $response->assertDontSee('Downloads May Be Out of Date');
    }

    // ─── Helper ──────────────────────────────────────────────────────

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
