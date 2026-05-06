<?php

namespace App\Http\Controllers;

use App\Exports\SubmissionExport;
use App\Exports\SubmissionTSVExport;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

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
        return view('download.index', ['page_meta' => $page_meta]);
    }

    /**
     * Get a configured GCS StorageClient instance.
     *
     * @return StorageClient|null
     */
    private function getGcsClient(): ?StorageClient
    {
        $bucket = config('filesystems.disks.gcs.bucket');

        if (empty($bucket)) {
            return null;
        }

        // Uses Application Default Credentials by default:
        // - GCE metadata server in production
        // - gcloud auth application-default login for local dev
        $config = ['suppressKeyFileNotice' => true];

        // Optional overrides (not needed when ADC is available)
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

    /**
     * Attempt to download a pre-generated file from GCS.
     * Falls back to on-the-fly generation if GCS is not configured or file not found.
     *
     * @param string $format The export format (xlsx, csv, tsv)
     * @param bool $useLegacy Whether to use legacy UUID format
     * @param callable $fallbackGenerator Function to generate the file on-the-fly
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function downloadFromGcsOrFallback(string $format, bool $useLegacy, callable $fallbackGenerator)
    {
        $client = $this->getGcsClient();

        if (!$client) {
            Log::debug('GCS not configured, falling back to on-the-fly generation');
            return $fallbackGenerator();
        }

        try {
            $bucketName = config('filesystems.disks.gcs.bucket');
            $bucket = $client->bucket($bucketName);

            // Build path matching GenccRelease.php structure:
            // {path_prefix}/{current|legacy}/{format}/gencc-submissions.{format}
            $prefix = config('filesystems.disks.gcs.path_prefix', 'releases');
            $folder = $useLegacy ? 'legacy' : 'current';
            $path = "{$prefix}/{$folder}/{$format}/gencc-submissions.{$format}";

            $object = $bucket->object($path);

            if (!$object->exists()) {
                Log::warning("GCS file not found: {$path}, falling back to on-the-fly generation");
                return $fallbackGenerator();
            }

            $contents = $object->downloadAsString();
            $filename = "gencc-submissions.{$format}";
            $mimeType = self::MIME_TYPES[$format] ?? 'application/octet-stream';

            return response($contents, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Length' => strlen($contents),
            ]);
        } catch (\Exception $e) {
            Log::error("GCS download failed: {$e->getMessage()}, falling back to on-the-fly generation");
            return $fallbackGenerator();
        }
    }

    public function export_XLSX()
    {
        $useLegacy = request()->query('format') !== 'new';

        return $this->downloadFromGcsOrFallback('xlsx', $useLegacy, function () use ($useLegacy) {
            return Excel::download(new SubmissionExport($useLegacy), 'gencc-submissions.xlsx');
        });
    }

    public function export_CSV()
    {
        $useLegacy = request()->query('format') !== 'new';

        return $this->downloadFromGcsOrFallback('csv', $useLegacy, function () use ($useLegacy) {
            return Excel::download(new SubmissionExport($useLegacy), 'gencc-submissions.csv', \Maatwebsite\Excel\Excel::CSV);
        });
    }

    public function export_TSV()
    {
        $useLegacy = request()->query('format') !== 'new';

        return $this->downloadFromGcsOrFallback('tsv', $useLegacy, function () use ($useLegacy) {
            return Excel::download(new SubmissionTSVExport($useLegacy), 'gencc-submissions.tsv', \Maatwebsite\Excel\Excel::TSV);
        });
    }
}
