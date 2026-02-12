<?php

namespace App\Exports;

use App\Submission;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

/**
 * TSV Export for submissions - uses FromQuery for efficient chunked processing.
 * This significantly reduces memory usage by not loading all 25K+ records at once.
 */
class SubmissionTSVExport implements FromQuery, WithHeadings, WithMapping, WithCustomCsvSettings
{
    use Exportable;

    private bool $useLegacyFormat;

    public function __construct(bool $useLegacyFormat = false)
    {
        $this->useLegacyFormat = $useLegacyFormat;
    }

    /**
     * Build the legacy UUID string for a submission.
     * Format: {SUBMITTER}-{GENE_HGNC}-{DISEASE}-{MOI}-{CLASSIFICATION}
     * Colons are replaced with underscores.
     */
    private function buildLegacyUuid($submission): string
    {
        $diseaseCurie = $submission->disease_original->curie
            ?? $submission->disease->curie
            ?? '';

        return sprintf('%s-%s-%s-%s-%s',
            str_replace(':', '_', $submission->submitter->curie),
            str_replace(':', '_', $submission->gene->hgnc_id),
            str_replace(':', '_', $diseaseCurie),
            str_replace(':', '_', $submission->inheritance->curie),
            str_replace(':', '_', $submission->classification->curie)
        );
    }

    /**
     * Return a query builder for chunked processing.
     * Eager loads relationships to prevent N+1 queries.
     * Only includes submissions from submitters with downloadable=true.
     */
    public function query()
    {
        return Submission::query()
            ->where('is_live', '=', true)
            ->where('status', '=', Submission::STATUS_PUBLISHED)
            ->whereHas('submitter', function ($query) {
                $query->where('downloadable', true);
            })
            ->with(['gene', 'disease', 'disease_original', 'classification', 'inheritance', 'submitter']);
    }

    /**
     * @var submission $submission
     */
    public function map($submission): array
    {
        // Common fields after the ID column(s)
        $commonFields = [
            $submission->gene->curie,
            $submission->gene->title,
            $submission->disease->curie ?? '',
            $submission->disease->title ?? '',
            $submission->disease_original->curie ?? '',
            $submission->disease_original->title ?? '',
            $submission->classification->curie,
            $submission->classification->title,
            $submission->inheritance->curie,
            $submission->inheritance->title,
            $submission->submitter->curie,
            $submission->submitter->title,
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
            $submission->evaluated,
            $submission->submitted_as_public_report_url,
            $submission->submitted_as_notes,
            $submission->submitted_as_pmids,
            $submission->submitted_as_assertion_criteria_url,
            $submission->submitted_as_submission_id,
            $submission->released_at,
        ];

        if ($this->useLegacyFormat) {
            return array_merge([$this->buildLegacyUuid($submission)], $commonFields);
        }

        return array_merge([$submission->sid, $submission->version_number], $commonFields);
    }



    public function headings(): array
    {
        $commonHeadings = [
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

        if ($this->useLegacyFormat) {
            return array_merge(['uuid'], $commonHeadings);
        }

        return array_merge(['sgc_id', 'version_number'], $commonHeadings);
    }


    public function getCsvSettings(): array
{
    return [
        'delimiter' => "\t"
    ];
}

}
