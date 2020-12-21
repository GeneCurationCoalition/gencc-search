<?php

namespace App\Exports;

use App\Submission;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;


// class SubmissionExport implements FromCollection
// {
//     /**
//     * @return \Illuminate\Support\Collection
//     */
//     public function collection()
//     {
//         return Submission::all();
//     }
// }
//, WithMapping
class SubmissionExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    public function collection()
    {
        return Submission::where('status', '=', 1)->get();
    }

    /**
     * @var submission $submission
     */
    public function map($submission): array
    {
        return [
            $submission->uuid,
            $submission->gene_curie                 = $submission->gene->curie,
            $submission->gene_symbol                = $submission->gene->title,
            $submission->disease_curie              = $submission->disease->curie,
            $submission->disease_title              = $submission->disease->title,
            $submission->disease_original_curie     = $submission->disease_original->curie,
            $submission->disease_original_title     = $submission->disease_original->title,
            $submission->classification_curie       = $submission->classification->curie,
            $submission->classification_title       = $submission->classification->title,
            $submission->moi_curie                  = $submission->inheritance->curie,
            $submission->moi_title                  = $submission->inheritance->title,
            $submission->submitter_curie            = $submission->submitter->curie,
            $submission->submitter_title            = $submission->submitter->title,
            $submission->submitted_as_hgnc_id,
            $submission->submitted_as_hgnc_symbol,
            $submission->submitted_as_disease_id,
            $submission->submitted_as_disease_name,
            $submission->submitted_as_moi_id,
            $submission->submitted_as_moi_name,
            $submission->submitted_as_submitter_id,
            $submission->submitted_as_submitter_name,
            $submission->submitted_as_classification_id,
            $submission->submitted_as_classification_name,
            $submission->submitted_as_date,
            $submission->submitted_as_public_report_url,
            $submission->submitted_as_notes,
            $submission->submitted_as_pmids,
            $submission->submitted_as_assertion_criteria_url,
            $submission->submitted_as_submission_id,
            $submission->submitted_run_date,
        ];
    }



    public function headings(): array
    {
        return [
            'uuid',
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
            'submitted_as_hgnc_id',
            'submitted_as_hgnc_symbol',
            'submitted_as_disease_id',
            'submitted_as_disease_name',
            'submitted_as_moi_id',
            'submitted_as_moi_name',
            'submitted_as_submitter_id',
            'submitted_as_submitter_name',
            'submitted_as_classification_id',
            'submitted_as_classification_name',
            'submitted_as_date',
            'submitted_as_public_report_url',
            'submitted_as_notes',
            'submitted_as_pmids',
            'submitted_as_assertion_criteria_url',
            'submitted_as_submission_id',
            'submitted_run_date',
        ];
    }

}