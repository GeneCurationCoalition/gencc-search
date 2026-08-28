<?php

namespace App\Http\Livewire\ConflictViewer;

use App\Services\ConflictFinder;
use App\Services\ConflictViewerFilters;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\Component;
use Livewire\WithPagination;

class Listing extends Component
{
    use WithPagination;

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

    public function mount()
    {
        $this->normalizeState(ConflictFinder::conflicts());
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
        if (! in_array($field, ConflictViewerFilters::SORTABLE_FIELDS, true)) {
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
        $filters = new ConflictViewerFilters();

        if (! is_string($slug) || ! in_array($slug, $filters->knownSubmitterSlugs(ConflictFinder::conflicts()), true)) {
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
        $keys = (new ConflictViewerFilters())->csvToArray($csv);

        $keys = in_array($key, $keys, true)
            ? array_values(array_diff($keys, [$key]))
            : array_merge($keys, [$key]);

        sort($keys);

        return implode(',', $keys);
    }

    public function render()
    {
        $filters = new ConflictViewerFilters();
        $all  = ConflictFinder::conflicts();
        $state = $this->normalizeState($all);
        $base = $filters->applyTextFilters($all, $state['gene'], $state['disease']);
        $hiddenSubmitters = $state['hiddenSubmitters'];

        // The facet's residual counts ignore its own exclusions but respect the
        // active text filters.
        $submitterCounts  = $filters->countSubmitters($base);
        $submitterOptions = $filters->submitterOptions($all, $submitterCounts);
        $rows = $filters->apply($all, $state);
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
            'download_query'    => $filters->downloadQuery($state),
        ]);
    }

    /** Normalize component state with the same rules used by downloads. */
    protected function normalizeState($all): array
    {
        $state = (new ConflictViewerFilters())->normalize($all, [
            'gene' => $this->gene,
            'disease' => $this->disease,
            'hideSubmitters' => $this->hideSubmitters,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);

        $this->gene = $state['gene'];
        $this->disease = $state['disease'];
        $this->hideSubmitters = $state['hideSubmitters'];
        $this->sortField = $state['sortField'];
        $this->sortDirection = $state['sortDirection'];
        $this->invalidUrlFiltersIgnored = $this->invalidUrlFiltersIgnored || $state['invalid'];

        return $state;
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
        $filters = new ConflictViewerFilters();
        $labels = [];

        if (is_string($this->gene) && $filters->normalizedSearchTerm($this->gene) !== '') {
            $labels[] = 'gene matches “' . $this->gene . '”';
        }

        if (is_string($this->disease) && $filters->normalizedSearchTerm($this->disease) !== '') {
            $labels[] = 'disease matches “' . $this->disease . '”';
        }

        if (! empty($hiddenSubmitters)) {
            $labels[] = count($hiddenSubmitters) . ' of ' . count($submitterOptions) . ' submitters hidden';
        }

        return $labels;
    }
}
