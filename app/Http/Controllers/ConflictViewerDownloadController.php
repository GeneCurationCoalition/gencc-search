<?php

namespace App\Http\Controllers;

use App\Exports\ConflictSubmissionExport;
use App\Services\ConflictExportCache;
use App\Services\ConflictFinder;
use App\Services\ConflictViewerFilters;
use App\Services\DownloadQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ConflictViewerDownloadController extends Controller
{
    protected const MIME_TYPES = [
        'csv' => 'text/csv',
        'tsv' => 'text/tab-separated-values',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function __invoke(Request $request, string $format): Response
    {
        abort_unless(isset(self::MIME_TYPES[$format]), 404);

        $filters = new ConflictViewerFilters();
        $all = ConflictFinder::downloadableConflicts();
        $state = $filters->normalize($all, $request->only([
            'gene',
            'disease',
            'hideSubmitters',
            'sortField',
            'sortDirection',
        ]));
        $groups = $filters->apply($all, $state);
        $filename = 'gencc-conflicts-' . now()->toDateString() . '.' . $format;
        $export = new ConflictSubmissionExport($groups, $format);
        $cache = app(ConflictExportCache::class);
        $identity = $cache->identity($format, $filters->canonicalState($state), $export->cacheRows());
        $cached = $cache->find($identity);
        $headers = $this->headers($format, $filename, $identity['etag'], $cached);

        if ($this->etagMatches($request->header('If-None-Match'), $identity['etag'])
            || (!$request->hasHeader('If-None-Match') && $this->modifiedSinceMatches($request, $cached))) {
            return response('', 304, $headers);
        }

        if ($request->isMethod('HEAD')) {
            return response('', 200, $headers);
        }

        $quota = app(DownloadQuota::class);
        $ip = $request->ip();
        if (!$quota->reserve(DownloadQuota::CONFLICT_VIEWER, $ip)) {
            return $quota->rejectionResponse();
        }

        try {
            $cached = $cached ?: $cache->generate($identity, $export);
            $headers = $this->headers($format, $filename, $identity['etag'], $cached);
            $response = response()->download($cached['path'], $filename, $headers);
            $response->setLastModified($this->dateTime((int) $cached['generated_at']));

            return $response;
        } catch (\Throwable $e) {
            $quota->release(DownloadQuota::CONFLICT_VIEWER, $ip);
            Log::error('Conflict export generation failed: ' . $e->getMessage());

            return response(
                "Conflict export is temporarily unavailable. Please try again later.\n",
                503,
                ['Content-Type' => 'text/plain']
            );
        }
    }

    private function headers(string $format, string $filename, string $etag, ?array $cached): array
    {
        $headers = [
            'Content-Type' => self::MIME_TYPES[$format],
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'public, no-cache',
            'ETag' => $etag,
        ];

        if ($cached) {
            $headers['Last-Modified'] = gmdate('D, d M Y H:i:s', (int) $cached['generated_at']) . ' GMT';
            $headers['Content-Length'] = (string) $cached['size'];
        }

        return $headers;
    }

    private function etagMatches(?string $header, string $etag): bool
    {
        if (!$header) {
            return false;
        }

        $expected = preg_replace('/^W\//', '', $etag);
        foreach (explode(',', $header) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '*' || preg_replace('/^W\//', '', $candidate) === $expected) {
                return true;
            }
        }

        return false;
    }

    private function modifiedSinceMatches(Request $request, ?array $cached): bool
    {
        $header = $request->header('If-Modified-Since');
        if (!$header || !$cached) {
            return false;
        }

        $timestamp = strtotime($header);

        return $timestamp !== false && (int) $cached['generated_at'] <= $timestamp;
    }

    private function dateTime(int $timestamp): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('@' . $timestamp))->setTimezone(new \DateTimeZone('GMT'));
    }
}
