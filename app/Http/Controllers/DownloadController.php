<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\SubmissionExport;
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
        //
        //Log::channel('slack')->info('Something happened!');
        //
        //$items = Gene::has('submissions')->orderBy('title')->paginate(10);
        //dd($items);
        //return view('genes.index', ['genes' => $items]);
        $page_meta['seo']['title'] = "Download GenCC Data";
        return view('download.index', ['page_meta' => $page_meta]);
    }

    public function export_XLSX()
    {
        return Excel::download(new SubmissionExport, 'gencc-submissions.xlsx');
    }

    public function export_CSV()
    {
        return Excel::download(new SubmissionExport, 'gencc-submissions.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    public function export_TSV()
    {
        return Excel::download(new SubmissionExport, 'gencc-submissions.tsv', \Maatwebsite\Excel\Excel::TSV);
    }

    public function export_XLS()
    {
        return Excel::download(new SubmissionExport, 'gencc-submissions.xls', \Maatwebsite\Excel\Excel::XLS);
    }
}
