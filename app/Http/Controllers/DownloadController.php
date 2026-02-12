<?php

namespace App\Http\Controllers;

use App\Exports\SubmissionExport;
use App\Exports\SubmissionTSVExport;
use Maatwebsite\Excel\Facades\Excel;

class DownloadController extends Controller
{
    //
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

    public function export_XLSX()
    {
        // Legacy format is default; new format requires ?format=new
        $useLegacy = request()->query('format') !== 'new';
        return Excel::download(new SubmissionExport($useLegacy), 'gencc-submissions.xlsx');
    }

    public function export_CSV()
    {
        // Legacy format is default; new format requires ?format=new
        $useLegacy = request()->query('format') !== 'new';
        return Excel::download(new SubmissionExport($useLegacy), 'gencc-submissions.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    public function export_TSV()
    {
        // Legacy format is default; new format requires ?format=new
        $useLegacy = request()->query('format') !== 'new';
        return Excel::download(new SubmissionTSVExport($useLegacy), 'gencc-submissions.tsv', \Maatwebsite\Excel\Excel::TSV);
    }

    public function export_XLS()
    {
        // Legacy format is default; new format requires ?format=new
        $useLegacy = request()->query('format') !== 'new';
        return Excel::download(new SubmissionExport($useLegacy), 'gencc-submissions.xls', \Maatwebsite\Excel\Excel::XLS);
    }
}
