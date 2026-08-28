<?php

namespace Tests\Feature;

use App\Classification;
use App\Disease;
use App\Exports\ConflictSubmissionExport;
use App\Gene;
use App\Inheritance;
use App\Services\ConflictExportCache;
use App\Services\ConflictFinder;
use App\Services\DownloadQuota;
use App\Submission;
use App\Submitter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ConflictViewerDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupConflictExportCache();
        Cache::flush();
        ConflictFinder::flush();
        Carbon::setTestNow('2026-08-24 12:00:00');
    }

    protected function tearDown(): void
    {
        $this->cleanupConflictExportCache();
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function cleanupConflictExportCache(): void
    {
        $directory = storage_path('app/conflict-export-cache');
        if (! is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }

    protected function classification(string $curie, string $name): Classification
    {
        return Classification::factory()->create([
            'curie' => $curie,
            'name' => $name,
        ]);
    }

    protected function submission(array $attributes): Submission
    {
        return Submission::factory()->create($attributes + [
            'is_live' => true,
            'status' => Submission::STATUS_PUBLISHED,
            'version_number' => 1,
            'report_date' => '2026-01-02',
        ]);
    }

    protected function responsePath($response): string
    {
        return $response->baseResponse->getFile()->getPathname();
    }

    protected function delimitedRows($response, string $delimiter): array
    {
        $handle = fopen($this->responsePath($response), 'r');
        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    protected function xlsxRows($response): array
    {
        return IOFactory::load($this->responsePath($response))
            ->getActiveSheet()
            ->toArray('', true, true, false);
    }

    /** Every SGC id ConflictFinder::downloadableConflicts() still exposes, sorted. */
    protected function downloadableSids(): array
    {
        $sids = ConflictFinder::downloadableConflicts()
            ->flatMap(fn ($group) => array_column($group['submissions'], 'sgc_id'))
            ->all();
        sort($sids);

        return $sids;
    }

    protected function basicConflict(string $prefix = 'BASIC'): void
    {
        $base = [
            'gene_id' => Gene::factory()->create(['symbol' => $prefix])->id,
            'disease_id' => Disease::factory()->create(['name' => $prefix . ' disease'])->id,
            'inheritance_id' => Inheritance::factory()->create()->id,
            'submitter_id' => Submitter::factory()->create(['downloadable' => true])->id,
        ];

        $this->submission($base + [
            'sid' => $prefix . '-D',
            'classification_id' => $this->classification('GENCC:100001', 'Definitive')->id,
        ]);
        $this->submission($base + [
            'sid' => $prefix . '-L',
            'classification_id' => $this->classification('GENCC:100004', 'Limited')->id,
        ]);
    }

    /** @test */
    public function all_formats_have_identical_values_metadata_and_xlsx_header_features()
    {
        $gene = Gene::factory()->create([
            'hgnc_id' => 'HGNC:1100',
            'symbol' => "=FORMULA",
        ]);
        $normalized = Disease::factory()->create([
            'curie' => 'MONDO:0000001',
            'name' => 'Normalized disease',
        ]);
        $originalOne = Disease::factory()->omim()->create([
            'curie' => 'OMIM:100001',
            'name' => 'Original one',
        ]);
        $originalTwo = Disease::factory()->orphanet()->create([
            'curie' => 'Orphanet:2',
            'name' => 'Original two',
        ]);
        $moi = Inheritance::factory()->create([
            'curie' => 'HP:0000006',
            'name' => 'Autosomal dominant',
        ]);
        $definitive = $this->classification('GENCC:100001', 'Definitive');
        $limited = $this->classification('GENCC:100004', 'Limited');
        $submitter = Submitter::factory()->create([
            'curie' => 'GENCC:000001',
            'name' => 'Same Submitter',
            'downloadable' => true,
        ]);
        $base = [
            'gene_id' => $gene->id,
            'disease_id' => $normalized->id,
            'inheritance_id' => $moi->id,
            'submitter_id' => $submitter->id,
        ];

        // Exact-key duplicates remain distinct rows, and both sides may come
        // from the same submitter.
        $this->submission($base + [
            'sid' => 'SGC-002',
            'original_disease_id' => $originalOne->id,
            'classification_id' => $definitive->id,
            'version_number' => 2,
        ]);
        $this->submission($base + [
            'sid' => 'SGC-001',
            'original_disease_id' => $originalOne->id,
            'classification_id' => $definitive->id,
        ]);
        $this->submission($base + [
            'sid' => 'SGC-003',
            'original_disease_id' => $originalTwo->id,
            'classification_id' => $limited->id,
            'report_date' => '2026-03-04',
        ]);

        $csv = $this->get('/conflict-viewer/download/csv')->assertOk();
        $tsv = $this->get('/conflict-viewer/download/tsv')->assertOk();
        $xlsx = $this->get('/conflict-viewer/download/xlsx')->assertOk();

        $csv->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('gencc-conflicts-2026-08-24.csv');
        $tsv->assertHeader('content-type', 'text/tab-separated-values; charset=UTF-8')
            ->assertDownload('gencc-conflicts-2026-08-24.tsv');
        $xlsx->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload('gencc-conflicts-2026-08-24.xlsx');

        $csvRows = $this->delimitedRows($csv, ',');
        $tsvRows = $this->delimitedRows($tsv, "\t");
        $spreadsheet = IOFactory::load($this->responsePath($xlsx));
        $sheet = $spreadsheet->getActiveSheet();
        $xlsxRows = $sheet->toArray('', true, true, false);

        $this->assertSame($csvRows, $tsvRows);
        $this->assertSame($csvRows, $xlsxRows);
        $expectedHeadings = [
            'sgc_id',
            'version_number',
            'gene_curie',
            'gene_symbol',
            'disease_curie',
            'disease_title',
            'disease_original_curie',
            'disease_original_title',
            'classification_group',
            'classification_curie',
            'classification_title',
            'moi_curie',
            'moi_title',
            'submitter_curie',
            'submitter_title',
            'submitted_as_date',
        ];
        $this->assertSame($expectedHeadings, ConflictSubmissionExport::HEADINGS);
        $this->assertSame($expectedHeadings, $csvRows[0]);

        // Dropping the conflict-viewer-only column leaves gencc-sub's release
        // heading order exactly, so the two published files read side by side.
        $this->assertSame([
            'sgc_id',
            'version_number',
            'gene_curie',
            'gene_symbol',
            'disease_curie',
            'disease_title',
            'disease_original_curie',
            'disease_original_title',
            'classification_curie',
            'classification_title',
            'moi_curie',
            'moi_title',
            'submitter_curie',
            'submitter_title',
            'submitted_as_date',
        ], array_values(array_diff($expectedHeadings, ['classification_group'])));
        $this->assertSame(['SGC-001', 'SGC-002', 'SGC-003'], array_column(array_slice($csvRows, 1), 0));
        $this->assertSame(['HGNC:1100', 'HGNC:1100', 'HGNC:1100'], array_column(array_slice($csvRows, 1), 2));
        $this->assertSame(['OMIM:100001', 'OMIM:100001', 'Orphanet:2'], array_column(array_slice($csvRows, 1), 6));
        $this->assertSame(["'=FORMULA", "'=FORMULA", "'=FORMULA"], array_column(array_slice($csvRows, 1), 3));
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('D2')->getDataType());
        $this->assertSame('A2', $sheet->getFreezePane());
        $this->assertSame('A1:P4', $sheet->getAutoFilter()->getRange());
        $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
    }

    /** @test */
    public function downloads_carry_every_participating_submission_regardless_of_download_permission()
    {
        $definitive = $this->classification('GENCC:100001', 'Definitive');
        $moderate = $this->classification('GENCC:100003', 'Moderate');
        $limited = $this->classification('GENCC:100004', 'Limited');
        $supportive = $this->classification('GENCC:100009', 'Supportive');
        $unknown = $this->classification('GENCC:199999', 'Future term');
        $downloadable = Submitter::factory()->create(['name' => 'Download Lab', 'downloadable' => true]);
        $flagged = Submitter::factory()->create(['name' => 'Flagged Lab', 'downloadable' => false]);
        $moi = Inheritance::factory()->create();

        $makeGroup = function (string $symbol) use ($moi) {
            return [
                'gene_id' => Gene::factory()->create(['symbol' => $symbol])->id,
                'disease_id' => Disease::factory()->create()->id,
                'inheritance_id' => $moi->id,
            ];
        };

        // Only conflicting because of the flagged submitter's weak assertion.
        $sole = $makeGroup('SOLE');
        $this->submission($sole + ['sid' => 'SOLE-D', 'classification_id' => $definitive->id, 'submitter_id' => $downloadable->id]);
        $this->submission($sole + ['sid' => 'SOLE-L', 'classification_id' => $limited->id, 'submitter_id' => $flagged->id]);

        // Conflicting on its own, and holds every kind of non-participating row.
        $kept = $makeGroup('KEPT');
        $this->submission($kept + ['sid' => 'KEEP-D', 'classification_id' => $definitive->id, 'submitter_id' => $downloadable->id]);
        $this->submission($kept + ['sid' => 'KEEP-L', 'classification_id' => $limited->id, 'submitter_id' => $downloadable->id]);
        $this->submission($kept + ['sid' => 'FLAG-M', 'classification_id' => $moderate->id, 'submitter_id' => $flagged->id]);
        $this->submission($kept + ['sid' => 'SUPPORT', 'classification_id' => $supportive->id, 'submitter_id' => $downloadable->id]);
        $this->submission($kept + ['sid' => 'UNKNOWN', 'classification_id' => $unknown->id, 'submitter_id' => $downloadable->id]);
        $this->submission($kept + ['sid' => 'DELETED', 'classification_id' => $limited->id, 'submitter_id' => $downloadable->id, 'deleted_at' => now()]);
        Submission::factory()->unpublished()->create($kept + ['sid' => 'HIDDEN', 'classification_id' => $limited->id, 'submitter_id' => $downloadable->id]);
        Submission::factory()->historical()->create($kept + ['sid' => 'HISTORY', 'classification_id' => $limited->id, 'submitter_id' => $downloadable->id]);

        $this->get('/conflict-viewer')
            ->assertOk()
            ->assertSee('Flagged Lab')
            ->assertSee('SOLE')
            ->assertSee('KEPT');

        $csv = $this->delimitedRows($this->get('/conflict-viewer/download/csv')->assertOk(), ',');
        $tsv = $this->delimitedRows($this->get('/conflict-viewer/download/tsv')->assertOk(), "\t");
        $xlsx = $this->xlsxRows($this->get('/conflict-viewer/download/xlsx')->assertOk());

        $this->assertSame($csv, $tsv);
        $this->assertSame($csv, $xlsx);

        $downloadRows = array_slice($csv, 1);
        $ids = array_column($downloadRows, 0);

        // Supportive, unrecognized, deleted, unpublished, and historical rows do
        // not participate. The downloadable flag is not consulted, so FLAG-M and
        // the SOLE group both export, matching the public page and the release
        // export. Larger group first, then strongest classification first.
        $this->assertSame(['KEEP-D', 'FLAG-M', 'KEEP-L', 'SOLE-D', 'SOLE-L'], $ids);
        $this->assertContains('Flagged Lab', array_column($downloadRows, 14));
    }

    /** @test */
    public function conflict_download_routes_do_not_set_session_cookies()
    {
        $this->basicConflict();

        $response = $this->get('/conflict-viewer/download/csv')->assertOk();
        $cookieNames = array_map(fn ($cookie) => $cookie->getName(), $response->headers->getCookies());

        $this->assertNotContains('XSRF-TOKEN', $cookieNames);
        $this->assertNotContains(config('session.cookie'), $cookieNames);
    }

    /**
     * @test
     *
     * No endpoint calls download filtering any more. These two cover
     * ConflictFinder::downloadableConflicts() directly so the retained
     * implementation keeps its coverage until it is wired back in.
     */
    public function revoking_permission_redacts_rows_and_drops_one_sided_groups_without_flushing_the_six_hour_cache()
    {
        $definitive = $this->classification('GENCC:100001', 'Definitive');
        $limited = $this->classification('GENCC:100004', 'Limited');
        $allowed = Submitter::factory()->create(['name' => 'Allowed Lab', 'downloadable' => true]);
        $revoked = Submitter::factory()->create(['name' => 'Revoked Lab', 'downloadable' => true]);
        $moi = Inheritance::factory()->create();

        $lost = ['gene_id' => Gene::factory()->create()->id, 'disease_id' => Disease::factory()->create()->id, 'inheritance_id' => $moi->id];
        $kept = ['gene_id' => Gene::factory()->create()->id, 'disease_id' => Disease::factory()->create()->id, 'inheritance_id' => $moi->id];

        $this->submission($lost + ['sid' => 'LOST-D', 'classification_id' => $definitive->id, 'submitter_id' => $allowed->id]);
        $this->submission($lost + ['sid' => 'LOST-L', 'classification_id' => $limited->id, 'submitter_id' => $revoked->id]);
        $this->submission($kept + ['sid' => 'KEEP-D', 'classification_id' => $definitive->id, 'submitter_id' => $allowed->id]);
        $this->submission($kept + ['sid' => 'KEEP-L', 'classification_id' => $limited->id, 'submitter_id' => $allowed->id]);
        $this->submission($kept + ['sid' => 'DROP-L', 'classification_id' => $limited->id, 'submitter_id' => $revoked->id]);

        $this->assertCount(2, ConflictFinder::conflicts());
        $this->assertSame(['DROP-L', 'KEEP-D', 'KEEP-L', 'LOST-D', 'LOST-L'], $this->downloadableSids());

        $revoked->update(['downloadable' => false]);

        // DROP-L is redacted row-for-row. LOST-D is permitted by itself, but its
        // whole group goes because redacting LOST-L removes the only L/P/R/N
        // side and it is no longer an exportable conflict.
        $this->assertCount(1, ConflictFinder::downloadableConflicts());
        $this->assertSame(['KEEP-D', 'KEEP-L'], $this->downloadableSids());

        // Permission is read live, off the still-cached conflict set.
        $this->assertCount(2, ConflictFinder::conflicts());
    }

    /** @test */
    public function granting_permission_restores_a_conflict_without_flushing_the_six_hour_cache()
    {
        $definitive = $this->classification('GENCC:100001', 'Definitive');
        $limited = $this->classification('GENCC:100004', 'Limited');
        $allowed = Submitter::factory()->create(['downloadable' => true]);
        $newlyAllowed = Submitter::factory()->create(['downloadable' => false]);
        $base = [
            'gene_id' => Gene::factory()->create()->id,
            'disease_id' => Disease::factory()->create()->id,
            'inheritance_id' => Inheritance::factory()->create()->id,
        ];

        $this->submission($base + ['sid' => 'GRANT-D', 'classification_id' => $definitive->id, 'submitter_id' => $allowed->id]);
        $this->submission($base + ['sid' => 'GRANT-L', 'classification_id' => $limited->id, 'submitter_id' => $newlyAllowed->id]);

        $this->assertCount(1, ConflictFinder::conflicts());
        $this->assertCount(0, ConflictFinder::downloadableConflicts());

        $newlyAllowed->update(['downloadable' => true]);

        $this->assertCount(1, ConflictFinder::downloadableConflicts());
        $this->assertSame(['GRANT-D', 'GRANT-L'], $this->downloadableSids());
        $this->assertCount(1, ConflictFinder::conflicts());
    }

    /** @test */
    public function formats_share_the_conflict_bucket_but_release_is_independent()
    {
        $this->basicConflict();
        config(['downloads.daily_quota' => 2]);

        $this->get('/conflict-viewer/download/csv')->assertOk();
        $this->get('/conflict-viewer/download/tsv')->assertOk();
        $this->get('/conflict-viewer/download/xlsx')->assertStatus(429);

        $this->assertTrue(app(DownloadQuota::class)->reserve(DownloadQuota::RELEASE, '127.0.0.1'));
        $this->assertFalse(app(DownloadQuota::class)->reserve(DownloadQuota::CONFLICT_VIEWER, '127.0.0.1'));
    }

    /** @test */
    public function conflict_quota_is_independent_per_ip()
    {
        $this->basicConflict();
        config(['downloads.daily_quota' => 1]);

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->get('/conflict-viewer/download/csv')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11'])
            ->get('/conflict-viewer/download/csv')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->get('/conflict-viewer/download/csv')->assertStatus(429);
    }

    /** @test */
    public function cold_and_cached_head_and_etag_304_responses_are_quota_free()
    {
        $this->basicConflict();
        config(['downloads.daily_quota' => 2]);

        $coldHead = $this->call('HEAD', '/conflict-viewer/download/csv')->assertOk();
        $etag = $coldHead->headers->get('ETag');
        $coldHead->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition')
            ->assertHeaderMissing('last-modified')
            ->assertHeaderMissing('content-length');
        $this->assertDirectoryDoesNotExist(storage_path('app/conflict-export-cache'));

        $this->get('/conflict-viewer/download/csv', ['If-None-Match' => $etag])
            ->assertStatus(304);
        $this->assertDirectoryDoesNotExist(storage_path('app/conflict-export-cache'));

        $full = $this->get('/conflict-viewer/download/csv')->assertOk();
        $cachedHead = $this->call('HEAD', '/conflict-viewer/download/csv')->assertOk();
        $cachedHead->assertHeader('etag', $etag)
            ->assertHeader('last-modified')
            ->assertHeader('content-length', (string) filesize($this->responsePath($full)));

        $this->get('/conflict-viewer/download/csv', [
            'If-Modified-Since' => $cachedHead->headers->get('Last-Modified'),
        ])->assertStatus(304);

        $this->get('/conflict-viewer/download/csv')->assertOk();
        $this->get('/conflict-viewer/download/csv')->assertStatus(429);
    }

    /** @test */
    public function etags_are_conditional_and_format_specific_without_extra_quota_use()
    {
        $this->basicConflict();
        config(['downloads.daily_quota' => 3]);
        $etags = [];

        foreach (['csv', 'tsv', 'xlsx'] as $format) {
            $response = $this->get('/conflict-viewer/download/' . $format)->assertOk();
            $etags[$format] = $response->headers->get('ETag');
            $this->get('/conflict-viewer/download/' . $format, ['If-None-Match' => $etags[$format]])
                ->assertStatus(304);
        }

        $this->assertCount(3, array_unique($etags));
        $this->get('/conflict-viewer/download/csv')->assertStatus(429);
    }

    /** @test */
    public function canonical_filters_reuse_cache_while_filters_and_formats_vary_it()
    {
        $this->basicConflict('CACHE');

        $first = $this->get('/conflict-viewer/download/csv?gene=%C2%A0CACHE%C2%A0')->assertOk();
        $second = $this->get('/conflict-viewer/download/csv?gene=CACHE')->assertOk();
        $differentFilter = $this->get('/conflict-viewer/download/csv?gene=missing')->assertOk();
        $differentFormat = $this->get('/conflict-viewer/download/tsv?gene=CACHE')->assertOk();

        $this->assertSame($this->responsePath($first), $this->responsePath($second));
        $this->assertSame($first->headers->get('ETag'), $second->headers->get('ETag'));
        $this->assertNotSame($first->headers->get('ETag'), $differentFilter->headers->get('ETag'));
        $this->assertNotSame($first->headers->get('ETag'), $differentFormat->headers->get('ETag'));
    }

    /** @test */
    public function expired_variants_regenerate_and_abandoned_temporary_files_are_pruned()
    {
        $this->basicConflict('EXPIRE');
        config(['downloads.conflict_export_cache_ttl' => 10]);

        $first = $this->get('/conflict-viewer/download/csv')->assertOk();
        $etag = $first->headers->get('ETag');
        $firstModified = $first->headers->get('Last-Modified');
        $temporary = storage_path('app/conflict-export-cache/abandoned.tmp.test');
        file_put_contents($temporary, 'partial');
        touch($temporary, time() - 20);

        Carbon::setTestNow(now()->addSeconds(11));
        $second = $this->get('/conflict-viewer/download/csv')->assertOk();

        $this->assertSame($etag, $second->headers->get('ETag'));
        $this->assertNotSame($firstModified, $second->headers->get('Last-Modified'));
        $this->assertFileDoesNotExist($temporary);
        $this->assertCount(1, glob(storage_path('app/conflict-export-cache/*.meta.json')) ?: []);
    }

    /** @test */
    public function quota_is_checked_before_generation_and_failed_generation_releases_it()
    {
        $this->basicConflict('FAIL');
        $failing = new class extends ConflictExportCache {
            public $calls = 0;

            public function generate(array $identity, ConflictSubmissionExport $export): array
            {
                $this->calls++;
                throw new \RuntimeException('simulated generation failure');
            }
        };
        app()->instance(ConflictExportCache::class, $failing);
        config(['downloads.daily_quota' => 0]);

        $this->get('/conflict-viewer/download/csv')->assertStatus(429);
        $this->assertSame(0, $failing->calls);

        config(['downloads.daily_quota' => 1]);
        $this->get('/conflict-viewer/download/csv')->assertStatus(503);
        $this->assertSame(1, $failing->calls);

        app()->forgetInstance(ConflictExportCache::class);
        $this->get('/conflict-viewer/download/csv')->assertOk();
        $this->get('/conflict-viewer/download/csv')->assertStatus(429);
    }

    /** @test */
    public function filters_sorting_page_ignoring_malformed_values_and_dropdown_links_are_supported()
    {
        $definitive = $this->classification('GENCC:100001', 'Definitive');
        $limited = $this->classification('GENCC:100004', 'Limited');
        $moi = Inheritance::factory()->create();
        $other = Submitter::factory()->create(['name' => 'Other Lab', 'downloadable' => true]);
        $hidden = Submitter::factory()->create(['name' => 'Hidden Lab', 'downloadable' => true]);

        foreach ([['AAA', 'Alpha disease', $other], ['ZZZ', 'Zeta disease', $hidden]] as $index => [$symbol, $name, $weakSubmitter]) {
            $gene = Gene::factory()->create(['symbol' => $symbol]);
            $disease = Disease::factory()->create(['name' => $name]);
            $base = ['gene_id' => $gene->id, 'disease_id' => $disease->id, 'inheritance_id' => $moi->id];
            $this->submission($base + ['sid' => "D-{$index}", 'classification_id' => $definitive->id, 'submitter_id' => $other->id]);
            $this->submission($base + ['sid' => "L-{$index}", 'classification_id' => $limited->id, 'submitter_id' => $weakSubmitter->id]);
        }

        $sortedRows = $this->delimitedRows($this->get(
            '/conflict-viewer/download/csv?sortField=gene_symbol&sortDirection=desc&page=999'
        )->assertOk(), ',');
        $this->assertSame(
            ['D-1', 'L-1', 'D-0', 'L-0'],
            array_column(array_slice($sortedRows, 1), 0)
        );

        $query = [
            'gene' => "\u{00A0}zzz\u{00A0}",
            'disease' => ' zeta   disease ',
            'hideSubmitters' => 'other-lab',
            'sortField' => 'gene_symbol',
            'sortDirection' => 'asc',
            'page' => 999,
        ];
        $downloadQuery = http_build_query($query);
        $csvRows = $this->delimitedRows(
            $this->get('/conflict-viewer/download/csv?' . $downloadQuery)->assertOk(),
            ','
        );
        $tsvRows = $this->delimitedRows(
            $this->get('/conflict-viewer/download/tsv?' . $downloadQuery)->assertOk(),
            "\t"
        );
        $xlsxRows = $this->xlsxRows(
            $this->get('/conflict-viewer/download/xlsx?' . $downloadQuery)->assertOk()
        );

        $this->assertSame($csvRows, $tsvRows);
        $this->assertSame($csvRows, $xlsxRows);
        $this->assertSame(['D-1', 'L-1'], array_column(array_slice($csvRows, 1), 0));

        $malformed = http_build_query([
            'gene' => ['ZZZ'],
            'disease' => ['Zeta'],
            'hideSubmitters' => ['hidden-lab'],
            'sortField' => ['gene_symbol'],
            'sortDirection' => ['asc'],
        ]);
        $malformedRows = $this->delimitedRows($this->get('/conflict-viewer/download/csv?' . $malformed)->assertOk(), ',');
        $this->assertCount(5, $malformedRows);

        $expectedQuery = [
            'gene' => 'ZZZ',
            'disease' => 'Zeta disease',
            'hideSubmitters' => 'hidden-lab',
            'sortField' => 'gene_symbol',
            'sortDirection' => 'asc',
        ];
        $page = $this->get('/conflict-viewer?' . http_build_query($expectedQuery + ['page' => 7]))->assertOk();
        foreach (['csv', 'tsv', 'xlsx'] as $format) {
            $page->assertSee(e(route('conflict-viewer-download', ['format' => $format] + $expectedQuery)), false);
        }
        $page->assertSee('Excel (.xlsx)')
            ->assertSee('Current filters apply to downloads.');

        $this->get('/conflict-viewer/download/pdf')->assertNotFound();
    }
}
