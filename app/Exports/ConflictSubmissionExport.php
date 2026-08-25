<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** One export implementation for conflict CSV, tab-delimited CSV, and XLSX. */
class ConflictSubmissionExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithCustomCsvSettings,
    WithCustomValueBinder,
    WithStyles,
    ShouldAutoSize
{
    public const HEADINGS = [
        'sgc_id',
        'version_number',
        'gene_curie',
        'gene_symbol',
        'disease_curie',
        'disease_title',
        'disease_original_curie',
        'disease_original_title',
        'moi_curie',
        'moi_title',
        'classification_group',
        'classification_curie',
        'classification_title',
        'submitter_curie',
        'submitter_title',
        'submitted_as_date',
    ];

    protected Collection $rows;
    protected string $format;

    public function __construct(Collection $groups, string $format)
    {
        $this->format = $format;
        $this->rows = $groups->flatMap(function ($group) {
            $submissions = array_values($group['submissions']);
            usort($submissions, function ($left, $right) {
                $leftSide = $left['conflict_side'] === 'strong' ? 0 : 1;
                $rightSide = $right['conflict_side'] === 'strong' ? 0 : 1;

                return $leftSide <=> $rightSide
                    ?: (int) $left['classification_priority'] <=> (int) $right['classification_priority']
                    ?: self::compareText($left['classification_title'] ?? '', $right['classification_title'] ?? '')
                    ?: self::compareText($left['classification_curie'] ?? '', $right['classification_curie'] ?? '')
                    ?: self::compareText($left['submitter_title'] ?? '', $right['submitter_title'] ?? '')
                    ?: self::compareText($left['submitter_curie'] ?? '', $right['submitter_curie'] ?? '')
                    ?: self::compareText($left['submitted_as_date'] ?? '', $right['submitted_as_date'] ?? '')
                    ?: self::compareText($left['sgc_id'] ?? '', $right['sgc_id'] ?? '')
                    ?: (int) ($left['version_number'] ?? 0) <=> (int) ($right['version_number'] ?? 0)
                    ?: self::compareText($left['disease_original_title'] ?? '', $right['disease_original_title'] ?? '')
                    ?: self::compareText($left['disease_original_curie'] ?? '', $right['disease_original_curie'] ?? '');
            });

            return $submissions;
        })->values();
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    /** Ordered, visible row data used to identify a cached representation. */
    public function cacheRows(): array
    {
        return $this->rows->map(function ($submission) {
            $row = [];

            foreach (self::HEADINGS as $heading) {
                $row[$heading] = $submission[$heading] ?? '';
            }

            return $row;
        })->all();
    }

    public function headings(): array
    {
        return self::HEADINGS;
    }

    public function map($submission): array
    {
        return array_map(
            fn ($heading) => $this->literal($submission[$heading] ?? ''),
            self::HEADINGS
        );
    }

    public function getCsvSettings(): array
    {
        return [
            // TSV intentionally uses the CSV writer with a tab delimiter.
            'delimiter' => $this->format === 'tsv' ? "\t" : ',',
        ];
    }

    /** Bind every value explicitly as text so XLSX never creates formulas. */
    public function bindValue(Cell $cell, $value)
    {
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

        return true;
    }

    /** XLSX presentation: frozen bold header and filters over every column. */
    public function styles(Worksheet $sheet)
    {
        if ($this->format !== 'xlsx') {
            return [];
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:P' . max(1, $sheet->getHighestRow()));

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Keep spreadsheet programs from interpreting user-controlled CSV cells as
     * formulas. In Excel the quote prefix is hidden and the original text is
     * displayed literally; XLSX cells are also explicitly typed as strings.
     */
    protected function literal($value): string
    {
        $value = $value === null ? '' : (string) $value;

        if ($value !== '' && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }

    private static function compareText($left, $right): int
    {
        return strcasecmp((string) $left, (string) $right)
            ?: strcmp((string) $left, (string) $right);
    }
}
