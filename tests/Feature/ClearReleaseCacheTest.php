<?php

namespace Tests\Feature;

use Tests\TestCase;

class ClearReleaseCacheTest extends TestCase
{
    public function test_clear_cache_removes_markers_but_leaves_data_files(): void
    {
        $dataFiles = [];

        foreach (['current', 'legacy'] as $folder) {
            foreach (['csv', 'tsv', 'xlsx'] as $format) {
                $dir = storage_path("app/release-cache/{$folder}/{$format}");
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $dataFile = "{$dir}/gencc-submissions.{$format}";
                file_put_contents($dataFile, 'data');
                file_put_contents("{$dir}/.meta.json", '{"checked_at":1}');
                file_put_contents("{$dir}/.refresh-failed", (string) time());

                $dataFiles[] = $dataFile;
            }
        }

        $this->artisan('releases:clear-cache')->assertExitCode(0);

        foreach (['current', 'legacy'] as $folder) {
            foreach (['csv', 'tsv', 'xlsx'] as $format) {
                $dir = storage_path("app/release-cache/{$folder}/{$format}");
                $this->assertFileDoesNotExist("{$dir}/.meta.json");
                $this->assertFileDoesNotExist("{$dir}/.refresh-failed");
            }
        }

        // Data files are left in place so a transient GCS hiccup still serves last-good.
        foreach ($dataFiles as $dataFile) {
            $this->assertFileExists($dataFile);
        }
    }

    public function test_clear_cache_is_idempotent_when_markers_are_absent(): void
    {
        $this->artisan('releases:clear-cache')->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        foreach (['current', 'legacy'] as $folder) {
            foreach (['csv', 'tsv', 'xlsx'] as $format) {
                $dir = storage_path("app/release-cache/{$folder}/{$format}");
                foreach (['gencc-submissions.' . $format, '.meta.json', '.refresh-failed'] as $file) {
                    $path = "{$dir}/{$file}";
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
            }
        }

        parent::tearDown();
    }
}
