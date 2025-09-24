<?php

namespace App\Exports;

use App\Submission;

class SubmissionWithRowIDExport extends SubmissionExport
{

    /**
     * @var submission $submission
     */
    public function map($submission): array
    {
        $parentMap = parent::map($submission);
        $parentMap[] = $submission->id;
        return $parentMap;
    }

    public function headings(): array
    {
        $parentHeadings = parent::headings();
        $parentHeadings[] = 'rowid';
        return $parentHeadings;
    }
}
