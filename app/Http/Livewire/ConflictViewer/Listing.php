<?php

namespace App\Http\Livewire\ConflictViewer;

use App\Services\ConflictFinder;
use App\Traits\NormalizesSearchInput;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Listing extends Component
{
    use WithPagination;
    use NormalizesSearchInput;

    const PER_PAGE = 25;

    public $gene          = '';
    public $disease       = '';
    public $sortField     = 'total_count';
    public $sortDirection = 'desc';

    /** Comma-separated submitter slugs to HIDE. Empty means every submitter is shown. */
    public $hideSubmitters = '';

    /** Whether malformed or unknown URL exclusions were discarded. */
    public $invalidUrlFiltersIgnored = false;

    /**
     * Filter state is shareable. Every entry excepts its default, so a wholly
     * default view has a bare /conflict-viewer URL with no query string at all.
     *
     * Kept as a CSV string rather than an array prop because Livewire compares
     * against 'except' with === (SupportBrowserHistory), which on arrays is
     * key-order sensitive.
     *
     * WithPagination contributes 'page' via queryStringWithPagination().
     *
     * @var array
     */
    protected $queryString = [
        'gene'           => ['except' => ''],
        'disease'        => ['except' => ''],
        'hideSubmitters' => ['except' => ''],
        'sortField'      => ['except' => 'total_count'],
        'sortDirection'  => ['except' => 'desc'],
    ];

    protected $filtersThatResetPage = [
        'gene',
        'disease',
    ];

    /**
     * Columns a user is allowed to sort by. Anything else is ignored.
     *
     * strong_count and other_count were deliberately left out: they sort on
     * something other than what their cells render (submission counts against
     * per-submitter blocks). The facet replaces them.
     *
     * Evidence strength is not sortable either. Within a row it is already the
     * order of the D/S/M and L/P/R/N cells (see ConflictFinder::orderSide()), and
     * across rows it takes too few distinct values to be a useful column sort.
     *
     * @var array
     */
    protected $sortableFields = [
        'gene_symbol',
        'disease_name',
        'moi',
        'total_count',
    ];

    public function mount()
    {
        $this->normalizeExclusionFilters(ConflictFinder::conflicts());
    }

    public function updating($name, $value)
    {
        $property = explode('.', $name)[0];

        if (in_array($property, $this->filtersThatResetPage, true)) {
            $this->resetPage();
        }
    }

    /**
     * Sort by a column, toggling direction when the same column is clicked again.
     *
     * @param  string  $field
     * @return void
     */
    public function sortBy($field)
    {
        if (! in_array($field, $this->sortableFields, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Show or hide a submitter.
     *
     * @param  string  $slug
     * @return void
     */
    public function toggleSubmitter($slug)
    {
        if (! is_string($slug) || ! in_array($slug, $this->knownSubmitterSlugs(ConflictFinder::conflicts()), true)) {
            return;
        }

        $this->hideSubmitters = $this->toggleCsv($this->hideSubmitters, $slug);
        $this->resetPage();
    }

    /**
     * Drop every filter and return to the full conflict set.
     *
     * @return void
     */
    public function clearFilters()
    {
        $this->gene           = '';
        $this->disease        = '';
        $this->hideSubmitters = '';
        $this->invalidUrlFiltersIgnored = false;

        $this->resetPage();
    }

    /**
     * Add or remove one key from a comma-separated exclusion list.
     *
     * array_values() matters: the array_diff idiom preserves keys, and a gappy
     * array makes Livewire serialise the value as a JS object. Sorted so the same
     * selection always produces the same URL.
     *
     * @param  string  $csv
     * @param  string  $key
     * @return string
     */
    protected function toggleCsv($csv, string $key): string
    {
        $keys = $this->csvToArray($csv);

        $keys = in_array($key, $keys, true)
            ? array_values(array_diff($keys, [$key]))
            : array_merge($keys, [$key]);

        sort($keys);

        return implode(',', $keys);
    }

    /**
     * Split a comma-separated exclusion list, dropping empties.
     *
     * @param  string  $csv
     * @return array
     */
    protected function csvToArray($csv): array
    {
        if (! is_string($csv)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $csv)),
            fn ($value) => $value !== ''
        ));
    }

    /**
     * Canonicalize the URL-facing exclusion list and discard invalid entries.
     */
    protected function normalizeExclusionFilters(Collection $all): void
    {
        $this->hideSubmitters = $this->normalizeCsv(
            $this->hideSubmitters,
            $this->knownSubmitterSlugs($all)
        );
    }

    /**
     * Accept only scalar CSV strings and return a sorted, unique known subset.
     */
    protected function normalizeCsv($value, array $known): string
    {
        if (! is_string($value)) {
            $this->invalidUrlFiltersIgnored = true;

            return '';
        }

        $valid = [];

        foreach (explode(',', $value) as $part) {
            $trimmed = trim($part);

            if ($trimmed === '') {
                if ($value !== '') {
                    $this->invalidUrlFiltersIgnored = true;
                }
                continue;
            }

            if ($trimmed !== $part
                || in_array($trimmed, $valid, true)
                || ! in_array($trimmed, $known, true)) {
                $this->invalidUrlFiltersIgnored = true;
            }

            if (in_array($trimmed, $known, true) && ! in_array($trimmed, $valid, true)) {
                $valid[] = $trimmed;
            }
        }

        sort($valid);

        return implode(',', $valid);
    }

    /** Return every filterable submitter slug present in the unfiltered conflict set. */
    protected function knownSubmitterSlugs(Collection $all): array
    {
        $slugs = [];

        foreach ($all as $row) {
            $slugs = array_merge($slugs, array_keys($row['submitter_slugs']));
        }

        $slugs = array_values(array_unique($slugs));
        sort($slugs);

        return $slugs;
    }

    /**
     * Apply the gene and disease substring filters.
     *
     * @param  \Illuminate\Support\Collection  $rows
     * @return \Illuminate\Support\Collection
     */
    protected function applyTextFilters(Collection $rows): Collection
    {
        $gene = $this->normalizeSearchTerm($this->gene);
        $disease = $this->normalizeSearchTerm($this->disease);

        if ($gene !== '') {
            $rows = $rows->filter(fn ($row) => Str::contains(Str::lower((string) $row['gene_symbol']), Str::lower($gene)));
        }

        if ($disease !== '') {
            $rows = $rows->filter(fn ($row) => Str::contains(Str::lower((string) $row['disease_name']), Str::lower($disease)));
        }

        return $rows;
    }

    /**
     * Keep a row when ANY other-side submitter is still visible.
     *
     * OR semantics, so hiding one submitter only removes the rows where that
     * submitter is the only other-side submitter. Rows with another visible
     * submitter are retained, and the hidden submitter's pill still renders —
     * the facet filters rows, not cells.
     *
     * @param  \Illuminate\Support\Collection  $rows
     * @param  array  $hidden
     * @return \Illuminate\Support\Collection
     */
    protected function applySubmitters(Collection $rows, array $hidden): Collection
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

    /**
     * Residual conflict count per filterable submitter, keyed by slug.
     *
     * @param  \Illuminate\Support\Collection  $rows
     * @return array
     */
    protected function countSubmitters(Collection $rows): array
    {
        $counts = [];

        foreach ($rows as $row) {
            foreach (array_keys($row['submitter_slugs']) as $slug) {
                $counts[$slug] = ($counts[$slug] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * The submitter facet options.
     *
     * Option *existence* comes from the unfiltered set so an option never
     * disappears mid-interaction and becomes unrecoverable; only the counts move,
     * and a zero count renders as 0. Sorted by count descending, then by name.
     *
     * @param  \Illuminate\Support\Collection  $all
     * @param  array  $counts
     * @return array
     */
    protected function submitterOptions(Collection $all, array $counts): array
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
                'slug'  => $slug,
                'name'  => $name,
                'count' => (int) ($counts[$slug] ?? 0),
            ];
        }

        usort($options, function ($a, $b) {
            return $b['count'] <=> $a['count'] ?: strcasecmp($a['name'], $b['name']);
        });

        return $options;
    }

    public function render()
    {
        $all  = ConflictFinder::conflicts();
        $this->normalizeExclusionFilters($all);
        $base = $this->applyTextFilters($all);

        $hiddenSubmitters = $this->csvToArray($this->hideSubmitters);

        // The facet's residual counts ignore its own exclusions but respect the
        // active text filters.
        $submitterCounts  = $this->countSubmitters($base);
        $submitterOptions = $this->submitterOptions($all, $submitterCounts);

        $rows = $this->applySubmitters($base, $hiddenSubmitters);

        // A URL can carry a sortField that is no longer sortable; fall back quietly.
        $field = in_array($this->sortField, $this->sortableFields, true) ? $this->sortField : 'total_count';

        $rows = $this->sortDirection === 'asc'
            ? $rows->sortBy($field)
            : $rows->sortByDesc($field);

        $rows = $rows->values();
        $page = $this->resolveValidPage();

        $conflicts = new LengthAwarePaginator(
            $rows->forPage($page, self::PER_PAGE)->values(),
            $rows->count(),
            self::PER_PAGE,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );

        $activeFilters = $this->activeFilterLabels($hiddenSubmitters, $submitterOptions);

        return view('livewire.conflict-viewer.listing', [
            'conflicts'         => $conflicts,
            'total_unfiltered'  => $all->count(),
            'submitter_options' => $submitterOptions,
            'hidden_submitters' => $hiddenSubmitters,
            'active_filters'    => $activeFilters,
        ]);
    }

    /** Resolve one canonical positive integer for slicing and pagination. */
    private function resolveValidPage(): int
    {
        $value = $this->page;
        $page = false;

        if (is_int($value) && $value > 0) {
            $page = $value;
        } elseif (is_string($value) && preg_match('/^[1-9][0-9]*$/D', $value)) {
            $page = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        }

        if ($page === false) {
            $page = 1;
        }

        $this->page = $page;
        $this->paginators['page'] = $page;

        return $page;
    }

    /**
     * Human-readable descriptions of the active filters, for the summary line.
     *
     * @param  array  $hiddenSubmitters
     * @param  array  $submitterOptions
     * @return array
     */
    protected function activeFilterLabels(array $hiddenSubmitters, array $submitterOptions): array
    {
        $labels = [];

        if (is_string($this->gene) && $this->normalizeSearchTerm($this->gene) !== '') {
            $labels[] = 'gene matches “' . $this->gene . '”';
        }

        if (is_string($this->disease) && $this->normalizeSearchTerm($this->disease) !== '') {
            $labels[] = 'disease matches “' . $this->disease . '”';
        }

        if (! empty($hiddenSubmitters)) {
            $labels[] = count($hiddenSubmitters) . ' of ' . count($submitterOptions) . ' submitters hidden';
        }

        return $labels;
    }
}
