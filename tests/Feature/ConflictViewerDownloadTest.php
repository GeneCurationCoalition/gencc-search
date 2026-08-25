<?php

namespace Tests\Feature;

use App\Classification;
use App\Disease;
use App\Exports\ConflictSubmissionExport;
use App\Gene;
use App\Inheritance;
use App\Services\ConflictFinder;
use App\Submission;
use App\Submitter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ConflictViewerDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ConflictFinder::flush();
        Carbon::setTestNow('2026-08-24 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
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
        $this->assertSame(ConflictSubmissionExport::HEADINGS, $csvRows[0]);
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
    public function non_downloadable_submitter_is_visible_in_viewer_but_has_no_rows_in_any_download()
    {
        $definitive = $this->classification('GENCC:100001', 'Definitive');
        $moderate = $this->classification('GENCC:100003', 'Moderate');
        $limited = $this->classification('GENCC:100004', 'Limited');
        $supportive = $this->classification('GENCC:100009', 'Supportive');
        $unknown = $this->classification('GENCC:199999', 'Future term');
        $downloadable = Submitter::factory()->create(['name' => 'Download Lab', 'downloadable' => true]);
        $blocked = Submitter::factory()->create(['name' => 'Blocked Lab', 'downloadable' => false]);
        $moi = Inheritance::factory()->create();

        $makeGroup = function (string $symbol) use ($moi) {
            return [
                'gene_id' => Gene::factory()->create(['symbol' => $symbol])->id,
                'disease_id' => Disease::factory()->create()->id,
                'inheritance_id' => $moi->id,
            ];
        };

        // Loses the weak side after the blocked submitter is removed.
        $lost = $makeGroup('LOST');
        $this->submission($lost + ['sid' => 'LOST-D', 'classification_id' => $definitive->id, 'submitter_id' => $downloadable->id]);
        $this->submission($lost + ['sid' => 'LOST-L', 'classification_id' => $limited->id, 'submitter_id' => $blocked->id]);

        // Retains both sides, but exports none of the blocked or excluded rows.
        $kept = $makeGroup('KEPT');
        $this->submission($kept + ['sid' => 'KEEP-D', 'classification_id' => $definitive->id, 'submitter_id' => $downloadable->id]);
        $this->submission($kept + ['sid' => 'KEEP-L', 'classification_id' => $limited->id, 'submitter_id' => $downloadable->id]);
        $this->submission($kept + ['sid' => 'BLOCK-M', 'classification_id' => $moderate->id, 'submitter_id' => $blocked->id]);
        $this->submission($kept + ['sid' => 'SUPPORT', 'classification_id' => $supportive->id, 'submitter_id' => $downloadable->id]);
        $this->submission($kept + ['sid' => 'UNKNOWN', 'classification_id' => $unknown->id, 'submitter_id' => $downloadable->id]);
        Submission::factory()->unpublished()->create($kept + ['sid' => 'HIDDEN', 'classification_id' => $limited->id, 'submitter_id' => $downloadable->id]);
        Submission::factory()->historical()->create($kept + ['sid' => 'HISTORY', 'classification_id' => $limited->id, 'submitter_id' => $downloadable->id]);

        // Download permission does not affect the public viewer.
        $this->get('/conflict-viewer')
            ->assertOk()
            ->assertSee('Blocked Lab')
            ->assertSee('LOST')
            ->assertSee('KEPT');

        $csv = $this->delimitedRows($this->get('/conflict-viewer/download/csv')->assertOk(), ',');
        $tsv = $this->delimitedRows($this->get('/conflict-viewer/download/tsv')->assertOk(), "\t");
        $xlsx = $this->xlsxRows($this->get('/conflict-viewer/download/xlsx')->assertOk());

        $this->assertSame($csv, $tsv);
        $this->assertSame($csv, $xlsx);

        $downloadRows = array_slice($csv, 1);
        $ids = array_column($downloadRows, 0);

        // BLOCK-M is redacted row-for-row. LOST-D is permitted by itself, but
        // its whole group is omitted because redacting LOST-L removes the only
        // L/P/R/N side and it is no longer an exportable conflict.
        $this->assertSame(['KEEP-D', 'KEEP-L'], $ids);
        $this->assertNotContains('Blocked Lab', array_column($downloadRows, 14));
    }

    /** @test */
    public function revoking_download_permission_takes_effect_without_flushing_the_six_hour_cache()
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
        $revoked->update(['downloadable' => false]);

        $rows = $this->delimitedRows($this->get('/conflict-viewer/download/csv')->assertOk(), ',');

        $this->assertSame(['KEEP-D', 'KEEP-L'], array_column(array_slice($rows, 1), 0));
    }

    /** @test */
    public function granting_download_permission_takes_effect_without_flushing_the_six_hour_cache()
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

        $rows = $this->delimitedRows($this->get('/conflict-viewer/download/csv')->assertOk(), ',');

        $this->assertSame(['GRANT-D', 'GRANT-L'], array_column(array_slice($rows, 1), 0));
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
            ->assertSee('Downloads include only data from submitters that permit downloads.');

        $this->get('/conflict-viewer/download/pdf')->assertNotFound();
    }
}
