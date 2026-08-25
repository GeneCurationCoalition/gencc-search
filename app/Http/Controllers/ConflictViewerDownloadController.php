<?php

namespace App\Http\Controllers;

use App\Exports\ConflictSubmissionExport;
use App\Services\ConflictFinder;
use App\Services\ConflictViewerFilters;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConflictViewerDownloadController extends Controller
{
    protected const MIME_TYPES = [
        'csv' => 'text/csv',
        'tsv' => 'text/tab-separated-values',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function __invoke(Request $request, string $format): BinaryFileResponse
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
        $writer = $format === 'xlsx' ? Excel::XLSX : Excel::CSV;

        return ExcelFacade::download(
            new ConflictSubmissionExport($groups, $format),
            $filename,
            $writer,
            ['Content-Type' => self::MIME_TYPES[$format]]
        );
    }
}
