<?php

namespace App\Services;

use App\Traits\NormalizesSearchInput;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Shared normalization, filtering, facets, and sorting for conflict groups. */
class ConflictViewerFilters
{
    use NormalizesSearchInput;

    public const DEFAULT_SORT_FIELD = 'total_count';
    public const DEFAULT_SORT_DIRECTION = 'desc';

    public const SORTABLE_FIELDS = [
        'gene_symbol',
        'disease_name',
        'moi',
        'total_count',
    ];

    /** Canonicalize all URL-facing filter values. */
    public function normalize(Collection $all, array $values): array
    {
        $invalid = false;
        $gene = $this->normalizeScalar($values['gene'] ?? '', $invalid);
        $disease = $this->normalizeScalar($values['disease'] ?? '', $invalid);
        [$hideSubmitters, $hidden, $invalidCsv] = $this->normalizeCsv(
            $values['hideSubmitters'] ?? '',
            $this->knownSubmitterSlugs($all)
        );

        $sortField = $values['sortField'] ?? self::DEFAULT_SORT_FIELD;
        if (! is_string($sortField) || ! in_array($sortField, self::SORTABLE_FIELDS, true)) {
            $sortField = self::DEFAULT_SORT_FIELD;
            $invalid = true;
        }

        $sortDirection = $values['sortDirection'] ?? self::DEFAULT_SORT_DIRECTION;
        if (! is_string($sortDirection) || ! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = self::DEFAULT_SORT_DIRECTION;
            $invalid = true;
        }

        return [
            'gene' => $gene,
            'disease' => $disease,
            'hideSubmitters' => $hideSubmitters,
            'hiddenSubmitters' => $hidden,
            'sortField' => $sortField,
            'sortDirection' => $sortDirection,
            'invalid' => $invalid || $invalidCsv,
        ];
    }

    /** Apply the normalized state to every group, without pagination. */
    public function apply(Collection $rows, array $state): Collection
    {
        $rows = $this->applyTextFilters($rows, $state['gene'], $state['disease']);
        $rows = $this->applySubmitters($rows, $state['hiddenSubmitters']);

        $rows = $rows->sort(function ($left, $right) use ($state) {
            $primary = $this->compareField($left, $right, $state['sortField']);
            if ($primary !== 0) {
                return $state['sortDirection'] === 'asc' ? $primary : -$primary;
            }

            foreach ([
                ['gene_symbol', 'gene_curie'],
                ['disease_name', 'disease_curie'],
                ['moi', 'moi_curie'],
            ] as [$label, $curie]) {
                $comparison = $this->compareText($left[$label] ?? '', $right[$label] ?? '')
                    ?: $this->compareText($left[$curie] ?? '', $right[$curie] ?? '');

                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return strcmp((string) ($left['key'] ?? ''), (string) ($right['key'] ?? ''));
        });

        return $rows->values();
    }

    /** Stable cache-facing state; invalid markers and parsed helper values are omitted. */
    public function canonicalState(array $state): array
    {
        return [
            'gene' => $this->normalizedSearchTerm($state['gene'] ?? ''),
            'disease' => $this->normalizedSearchTerm($state['disease'] ?? ''),
            'hideSubmitters' => (string) ($state['hideSubmitters'] ?? ''),
            'sortField' => (string) ($state['sortField'] ?? self::DEFAULT_SORT_FIELD),
            'sortDirection' => (string) ($state['sortDirection'] ?? self::DEFAULT_SORT_DIRECTION),
        ];
    }

    /** Apply only text filters, used for residual facet counts. */
    public function applyTextFilters(Collection $rows, $gene, $disease): Collection
    {
        $gene = $this->normalizedSearchTerm($gene);
        $disease = $this->normalizedSearchTerm($disease);

        if ($gene !== '') {
            $rows = $rows->filter(fn ($row) => Str::contains(
                Str::lower((string) $row['gene_symbol']),
                Str::lower($gene)
            ));
        }

        if ($disease !== '') {
            $rows = $rows->filter(fn ($row) => Str::contains(
                Str::lower((string) $row['disease_name']),
                Str::lower($disease)
            ));
        }

        return $rows;
    }

    /** Keep a group when any other-side submitter remains visible. */
    public function applySubmitters(Collection $rows, array $hidden): Collection
    {
        if (empty($hidden)) {
            return $rows;
        }

        return $rows->filter(function ($row) use ($hidden) {
            foreach (array_keys($row['submitter_slugs']) as $slug) {
                if (! in_array($slug, $hidden, true)) {
                    return true;
                }
            }

            return false;
        });
    }

    public function knownSubmitterSlugs(Collection $all): array
    {
        $slugs = [];

        foreach ($all as $row) {
            $slugs = array_merge($slugs, array_keys($row['submitter_slugs']));
        }

        $slugs = array_values(array_unique($slugs));
        sort($slugs);

        return $slugs;
    }

    public function csvToArray($csv): array
    {
        if (! is_string($csv)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $csv)),
            fn ($value) => $value !== ''
        ));
    }

    public function countSubmitters(Collection $rows): array
    {
        $counts = [];

        foreach ($rows as $row) {
            foreach (array_keys($row['submitter_slugs']) as $slug) {
                $counts[$slug] = ($counts[$slug] ?? 0) + 1;
            }
        }

        return $counts;
    }

    public function submitterOptions(Collection $all, array $counts): array
    {
        $names = [];

        foreach ($all as $row) {
            foreach ($row['submitter_slugs'] as $slug => $name) {
                $names[$slug] = $name;
            }
        }

        $options = [];
        foreach ($names as $slug => $name) {
            $options[] = [
                'slug' => $slug,
                'name' => $name,
                'count' => (int) ($counts[$slug] ?? 0),
            ];
        }

        usort($options, fn ($a, $b) => $b['count'] <=> $a['count'] ?: strcasecmp($a['name'], $b['name']));

        return $options;
    }

    /** Query parameters for download links; pagination is intentionally absent. */
    public function downloadQuery(array $state): array
    {
        $query = [];

        foreach (['gene', 'disease', 'hideSubmitters'] as $field) {
            if ($state[$field] !== '') {
                $query[$field] = $state[$field];
            }
        }

        if ($state['sortField'] !== self::DEFAULT_SORT_FIELD) {
            $query['sortField'] = $state['sortField'];
        }
        if ($state['sortDirection'] !== self::DEFAULT_SORT_DIRECTION) {
            $query['sortDirection'] = $state['sortDirection'];
        }

        return $query;
    }

    public function normalizedSearchTerm($term): string
    {
        return $this->normalizeSearchTerm($term);
    }

    protected function normalizeScalar($value, bool &$invalid): string
    {
        if (! is_string($value)) {
            $invalid = true;

            return '';
        }

        return $value;
    }

    /** Return canonical CSV, parsed values, and whether anything was discarded. */
    protected function normalizeCsv($value, array $known): array
    {
        if (! is_string($value)) {
            return ['', [], true];
        }

        $valid = [];
        $invalid = false;

        foreach (explode(',', $value) as $part) {
            $trimmed = trim($part);

            if ($trimmed === '') {
                $invalid = $invalid || $value !== '';
                continue;
            }

            if ($trimmed !== $part
                || in_array($trimmed, $valid, true)
                || ! in_array($trimmed, $known, true)) {
                $invalid = true;
            }

            if (in_array($trimmed, $known, true) && ! in_array($trimmed, $valid, true)) {
                $valid[] = $trimmed;
            }
        }

        sort($valid);

        return [implode(',', $valid), $valid, $invalid];
    }

    private function compareField(array $left, array $right, string $field): int
    {
        if ($field === 'total_count') {
            return (int) ($left[$field] ?? 0) <=> (int) ($right[$field] ?? 0);
        }

        return $this->compareText($left[$field] ?? '', $right[$field] ?? '');
    }

    private function compareText($left, $right): int
    {
        return strcasecmp((string) $left, (string) $right)
            ?: strcmp((string) $left, (string) $right);
    }
}
