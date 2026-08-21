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

    /**
     * Comma-separated severity tier keys to HIDE. Empty means every tier is shown.
     *
     * Exclusions rather than inclusions so the all-on default serialises to '',
     * which $queryString then omits from the URL entirely. It also mirrors the
     * $filter_set idiom in App\Http\Livewire\Gene\ListingByClassification.
     *
     * @var string
     */
    public $hideTiers = '';

    /** Comma-separated dissenting-submitter slugs to HIDE. Empty means every submitter is shown. */
    public $hideDissenters = '';

    /**
     * Filter state is shareable. Every entry excepts its default, so a wholly
     * default view has a bare /conflict-viewer URL with no query string at all.
     *
     * Kept as CSV strings rather than array props because Livewire compares
     * against 'except' with === (SupportBrowserHistory), which on arrays is
     * key-order sensitive, and array props serialise as hideTiers[0]=supportive.
     *
     * WithPagination contributes 'page' via queryStringWithPagination().
     *
     * @var array
     */
    protected $queryString = [
        'gene'           => ['except' => ''],
        'disease'        => ['except' => ''],
        'hideTiers'      => ['except' => ''],
        'hideDissenters' => ['except' => ''],
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
     * per-submitter blocks). The facets replace them.
     *
     * Evidence strength is not sortable either. Within a row it is already the
     * order of the Strong and Other cells (see ConflictFinder::orderSide()), and
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
     * Show or hide a severity tier.
     *
     * resetPage() is called here rather than left to updating(), which only fires
     * for wire:model writes and not for wire:click method calls.
     *
     * @param  string  $tier
     * @return void
     */
    public function toggleTier($tier)
    {
        $this->hideTiers = $this->toggleCsv($this->hideTiers, (string) $tier);
        $this->resetPage();
    }

    /**
     * Show or hide a dissenting submitter.
     *
     * @param  string  $slug
     * @return void
     */
    public function toggleDissenter($slug)
    {
        $this->hideDissenters = $this->toggleCsv($this->hideDissenters, (string) $slug);
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
        $this->hideTiers      = '';
        $this->hideDissenters = '';

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
        return array_values(array_filter(
            array_map('trim', explode(',', (string) $csv)),
            fn ($value) => $value !== ''
        ));
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
     * Drop rows whose severity tier is hidden.
     *
     * @param  \Illuminate\Support\Collection  $rows
     * @param  array  $hidden
     * @return \Illuminate\Support\Collection
     */
    protected function applyTiers(Collection $rows, array $hidden): Collection
    {
        if (empty($hidden)) {
            return $rows;
        }

        return $rows->reject(fn ($row) => in_array($row['severity_tier'], $hidden, true));
    }

    /**
     * Keep a row when ANY still-visible submitter dissents on it.
     *
     * OR semantics, so hiding one submitter only removes the rows where that
     * submitter is the *sole* dissenter. Rows where it dissents alongside a
     * visible submitter are retained, and its pill still renders in the cell —
     * the facet filters rows, not cells.
     *
     * @param  \Illuminate\Support\Collection  $rows
     * @param  array  $hidden
     * @return \Illuminate\Support\Collection
     */
    protected function applyDissenters(Collection $rows, array $hidden): Collection
    {
        if (empty($hidden)) {
            return $rows;
        }

        return $rows->filter(function ($row) use ($hidden) {
            foreach (array_keys($row['other_slugs']) as $slug) {
                if (! in_array($slug, $hidden, true)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Residual conflict count per severity tier, always keyed in TIER_LABELS order.
     *
     * @param  \Illuminate\Support\Collection  $rows
     * @return array
     */
    protected function countTiers(Collection $rows): array
    {
        $counted = $rows->countBy('severity_tier');

        $counts = [];

        foreach (array_keys(ConflictFinder::TIER_LABELS) as $tier) {
            $counts[$tier] = (int) $counted->get($tier, 0);
        }

        return $counts;
    }

    /**
     * Residual conflict count per dissenting submitter, keyed by slug.
     *
     * @param  \Illuminate\Support\Collection  $rows
     * @return array
     */
    protected function countDissenters(Collection $rows): array
    {
        $counts = [];

        foreach ($rows as $row) {
            foreach (array_keys($row['other_slugs']) as $slug) {
                $counts[$slug] = ($counts[$slug] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * The dissenting-submitter facet options.
     *
     * Option *existence* comes from the unfiltered set so an option never
     * disappears mid-interaction and become unrecoverable; only the counts move,
     * and a zero count renders as 0. Sorted by count descending, then by name.
     *
     * @param  \Illuminate\Support\Collection  $all
     * @param  array  $counts
     * @return array
     */
    protected function dissenterOptions(Collection $all, array $counts): array
    {
        $names = [];

        foreach ($all as $row) {
            foreach ($row['other_slugs'] as $slug => $name) {
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
        $base = $this->applyTextFilters($all);

        $hiddenTiers      = $this->csvToArray($this->hideTiers);
        $hiddenDissenters = $this->csvToArray($this->hideDissenters);

        // Residual counts: each facet's counts ignore its OWN exclusions but
        // respect every other active filter.
        $tierCounts       = $this->countTiers($this->applyDissenters($base, $hiddenDissenters));
        $dissenterCounts  = $this->countDissenters($this->applyTiers($base, $hiddenTiers));
        $dissenterOptions = $this->dissenterOptions($all, $dissenterCounts);

        $rows = $this->applyDissenters($this->applyTiers($base, $hiddenTiers), $hiddenDissenters);

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

        $activeFilters = $this->activeFilterLabels($hiddenTiers, $hiddenDissenters, $dissenterOptions);

        return view('livewire.conflict-viewer.listing', [
            'conflicts'         => $conflicts,
            'total_unfiltered'  => $all->count(),
            'tier_labels'       => ConflictFinder::TIER_LABELS,
            'tier_counts'       => $tierCounts,
            'hidden_tiers'      => $hiddenTiers,
            'dissenter_options' => $dissenterOptions,
            'hidden_dissenters' => $hiddenDissenters,
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
     * @param  array  $hiddenTiers
     * @param  array  $hiddenDissenters
     * @param  array  $dissenterOptions
     * @return array
     */
    protected function activeFilterLabels(array $hiddenTiers, array $hiddenDissenters, array $dissenterOptions): array
    {
        $labels = [];

        if (is_string($this->gene) && $this->normalizeSearchTerm($this->gene) !== '') {
            $labels[] = 'gene matches “' . $this->gene . '”';
        }

        if (is_string($this->disease) && $this->normalizeSearchTerm($this->disease) !== '') {
            $labels[] = 'disease matches “' . $this->disease . '”';
        }

        if (! empty($hiddenTiers)) {
            $labels[] = count($hiddenTiers) . ' of ' . count(ConflictFinder::TIER_LABELS) . ' severity tiers hidden';
        }

        if (! empty($hiddenDissenters)) {
            $labels[] = count($hiddenDissenters) . ' of ' . count($dissenterOptions) . ' submitters hidden';
        }

        return $labels;
    }
}
