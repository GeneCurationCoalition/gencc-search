<?php

namespace App\Http\Livewire\Genes;

use Livewire\Component;
use Livewire\WithPagination;
use App\Classification;
use App\Gene;
use App\Submitter;
use App\Traits\NormalizesSearchInput;

class Listing extends Component
{
    use WithPagination;
    use NormalizesSearchInput;

    /**
     * ?submitters= value meaning "deliberately none selected", as distinct from
     * an absent parameter meaning "no submitter filter at all" (#204).
     */
    const SUBMITTERS_NONE = 'none';

    public $title                           = '';
    public $hasDisease                      = '';
    public $curations_definitive            = '1';
    public $curations_strong                = '1';
    public $curations_moderate              = '1';
    public $curations_limited               = '1';
    public $curations_disputed              = '1';
    public $curations_refuted               = '1';
    public $curations_animal                = '1';
    public $curations_noknown               = '1';
    public $curations_supportive            = '1';
    public $curations_from_submitters       = [];
    public $count_submissions               = '';
    public $count_unique_diseases           = '';
    public $filtering_by_submitter          = false;
    public $sort                            = '';
    public $page                            = 1;
    /** Comma-joined submitter CURIEs. Empty means "all submitters" (#204). */
    public $submitterFilter                 = '';
    public $invalidUrlFiltersIgnored        = false;
    protected $submitters;

    protected $filtersThatResetPage = [
        'title',
        'hasDisease',
        'curations_definitive',
        'curations_strong',
        'curations_moderate',
        'curations_limited',
        'curations_disputed',
        'curations_refuted',
        'curations_animal',
        'curations_noknown',
        'curations_supportive',
        'curations_from_submitters',
        'count_submissions',
        'count_unique_diseases',
    ];

    /**
     * Reflect the filters in the URL so a filtered view can be bookmarked and
     * shared (#204).
     *
     * Every entry carries an 'except' matching its unfiltered value, so a clean
     * /genes URL stays clean and only the filters the user actually changed show
     * up. The classification defaults are '1' because render() turns all nine on
     * for a fresh load.
     *
     * Submitters are exposed as a comma-joined ?submitters= list rather than
     * binding curations_from_submitters directly: that array holds every
     * submitter when unfiltered, which would put twenty-odd idents into the URL
     * of an unfiltered page. See syncSubmitterFilter().
     */
    protected function queryString(): array
    {
        return array_merge([
            'title' => ['except' => ''],
            'hasDisease' => ['except' => ''],
        ], Classification::queryStringBindings(), [
            'submitterFilter' => ['except' => '', 'as' => 'submitters'],
        ]);
    }

    protected $rules = [
        'curations_definitive' => 'numeric',
        'curations_strong' => 'numeric',
        'curations_moderate' => 'numeric',
        'curations_limited' => 'numeric',
        'curations_disputed' => 'numeric',
        'curations_refuted' => 'numeric',
        'curations_animal' => 'numeric',
        'curations_noknown' => 'numeric',
        'curations_supportive' => 'numeric',
        'count_submissions' => 'numeric',
        'page' => 'numeric',
        'title' => 'string',
        //'curations_from_submitters' => 'string',
    ];

    public function mount()
    {
        $this->submitters = $this->submittersWithSubmissions();

        $this->title = $this->normalizeTextFilter(request('title', ''));
        $this->hasDisease = $this->normalizeTextFilter(request('hasDisease', ''));
        $this->count_submissions            = request('count_submissions');
        $this->count_unique_diseases        = request('count_unique_diseases');
        $this->normalizeClassificationFilters();

        // The CURIE form wins when both new and legacy parameters are present.
        if (request()->query->has('submitters')) {
            $this->curations_from_submitters = $this->expandSubmitterFilter(request()->query('submitters'));
        } else {
            $this->curations_from_submitters = $this->normalizeLegacySubmitterFilter(
                request()->query('curations_from_submitters')
            );
        }

        // Legacy links remain accepted inbound, but generated URLs use only the
        // stable CURIE-based submitters parameter.
        request()->query->remove('curations_from_submitters');
    }

    private function normalizeTextFilter($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        $this->invalidUrlFiltersIgnored = true;

        return '';
    }

    private function normalizeClassificationFilters(): void
    {
        foreach (Classification::VOCABULARY as $metadata) {
            $property = $metadata['property'];
            $value = $this->$property;

            if ((is_int($value) || is_string($value)) && ($value === 0 || $value === 1 || $value === '0' || $value === '1')) {
                $this->$property = (string) $value;
                continue;
            }

            $this->$property = '1';
            $this->invalidUrlFiltersIgnored = true;
        }
    }

    /**
     * Turn a CURIE CSV into the legacy ident array the query builder wants.
     */
    private function expandSubmitterFilter($value)
    {
        if (!is_string($value)) {
            $this->invalidUrlFiltersIgnored = true;
            return null;
        }

        // An empty selection cannot be spelled as an empty string, since that is
        // also how "no filter" is spelled. Hence the explicit sentinel.
        if ($value === self::SUBMITTERS_NONE) {
            return [];
        }

        $parts = array_map('trim', explode(',', $value));
        $requested = array_values(array_filter($parts, 'strlen'));

        if (empty($requested)) {
            $this->invalidUrlFiltersIgnored = true;
            return null;
        }

        $unique = array_values(array_unique($requested));
        $known = $this->submittersWithSubmissions()->pluck('ident', 'curie');
        $valid = collect($unique)
            ->filter(fn ($curie) => $known->has($curie))
            ->map(fn ($curie) => $known->get($curie))
            ->values()
            ->all();

        if (count($parts) !== count($requested) || count($requested) !== count($unique) || count($unique) !== count($valid)) {
            $this->invalidUrlFiltersIgnored = true;
        }

        return empty($valid) ? null : $valid;
    }

    /**
     * Normalize the bracketed legacy ident list and canonicalize it on output.
     */
    private function normalizeLegacySubmitterFilter($value)
    {
        if (is_null($value)) {
            return null;
        }

        if (!is_array($value)) {
            $this->invalidUrlFiltersIgnored = true;
            return null;
        }

        $requested = [];

        foreach ($value as $ident) {
            if (!is_scalar($ident)) {
                $this->invalidUrlFiltersIgnored = true;
                continue;
            }

            $trimmed = trim((string) $ident);

            if ($trimmed === '') {
                $this->invalidUrlFiltersIgnored = true;
                continue;
            }

            if ($trimmed !== (string) $ident || in_array($trimmed, $requested, true)) {
                $this->invalidUrlFiltersIgnored = true;
            }

            $requested[] = $trimmed;
        }

        $requested = array_values(array_unique($requested));
        $known = $this->submittersWithSubmissions()->pluck('ident')->all();
        $valid = array_values(array_intersect($requested, $known));

        if (count($valid) !== count($requested)) {
            $this->invalidUrlFiltersIgnored = true;
        }

        return empty($valid) ? null : $valid;
    }

    /**
     * Keep the URL-facing ?submitters= list in step with the selection. Cleared
     * when every submitter is selected, so an unfiltered page has a clean URL.
     */
    private function syncSubmitterFilter()
    {
        $selected = $this->curations_from_submitters ?? [];
        $submitters = $this->submittersWithSubmissions();
        $total = $submitters->count();

        if (count($selected) === $total) {
            $this->submitterFilter = '';
        } elseif (empty($selected)) {
            $this->submitterFilter = self::SUBMITTERS_NONE;
        } else {
            $this->submitterFilter = $submitters
                ->filter(fn ($submitter) => in_array($submitter->ident, $selected, true))
                ->pluck('curie')
                ->implode(',');
        }
    }

    /**
     * True when the listing is showing anything other than the full data set,
     * so the view can say so rather than leaving a stale filter invisible (#204).
     */
    public function getHasActiveFiltersProperty()
    {
        if ($this->title !== '' && !is_null($this->title)) {
            return true;
        }

        if ($this->hasDisease !== '' && !is_null($this->hasDisease)) {
            return true;
        }

        if ($this->submitterFilter !== '') {
            return true;
        }

        foreach (Classification::filterProperties() as $filter) {
            if ($this->$filter !== '' && !is_null($this->$filter) && (int) $this->$filter !== 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reset every filter to its unfiltered default, which also empties the query
     * string thanks to the 'except' rules.
     */
    public function clearAllFilters()
    {
        $this->resetPage();
        $this->title = '';
        $this->hasDisease = '';
        $this->selectAllClassifications();
        $this->selectAllSubmitters();
    }

    public function updating($name, $value)
    {
        $property = explode('.', $name)[0];

        if (in_array($property, $this->filtersThatResetPage, true)) {
            $this->resetPage();
        }
    }

    public function curationsFromSubmitters($value)
    {
        // This sets a flag to show a message on the front end that the curations counts in the pulls don't tally correctly yet
        $this->filtering_by_submitter = true;
        $this->resetPage();
        $array = $this->curations_from_submitters;
        if (in_array($value[0], $array)) {
            $result = array_diff($array, $value);
        } else {
            $result = array_merge($array, $value);
        }
        $this->curations_from_submitters = array_values(array_unique($result));
    }

    /**
     * Toggle every classification at once (#203).
     *
     * '0' rather than '' matters: '' means "never set", which render() treats as
     * a fresh page load and initializes to all-on. Writing '0' records a
     * deliberate choice, so an empty selection survives the next render and the
     * listing shows nothing until the user picks something.
     */
    public function selectAllClassifications()
    {
        $this->setAllClassifications('1');
    }

    public function selectNoClassifications()
    {
        $this->setAllClassifications('0');
    }

    private function setAllClassifications($value)
    {
        $this->resetPage();

        foreach (Classification::filterProperties() as $filter) {
            $this->$filter = $value;
        }
    }

    /**
     * Toggle every submitter at once (#203). Same empty-selection reasoning as
     * the classifications above, except the "never set" marker is null.
     */
    public function selectAllSubmitters()
    {
        $this->resetPage();
        $this->curations_from_submitters = $this->submittersWithSubmissions()->pluck('ident')->toArray();
        $this->filtering_by_submitter = false;
    }

    public function selectNoSubmitters()
    {
        $this->resetPage();
        $this->curations_from_submitters = [];
        $this->filtering_by_submitter = true;
    }

    private function submittersWithSubmissions()
    {
        if (is_null($this->submitters)) {
            $this->submitters = Submitter::has('submissions')->orderBy('name')->get();
        }

        return $this->submitters;
    }




    public function render()
    {

        $this->submitters     = $this->submittersWithSubmissions();
        // Default to every submitter on a fresh load, but leave an explicitly
        // emptied selection alone — is_null, not empty(), so that "none" sticks
        // instead of snapping back to all (#203).
        if(is_null($this->curations_from_submitters)){
            $curations_from_submitters = $this->submitters->pluck('ident');
            $this->curations_from_submitters = $curations_from_submitters->toArray();
        }
        if(count($this->submitters) == count($this->curations_from_submitters)) {
            $this->filtering_by_submitter = false;
        }

        // Done here rather than in each action so every path that changes the
        // selection — the per-submitter toggle, select all, select none, and the
        // fresh-load default above — lands in the URL (#204).
        $this->syncSubmitterFilter();

        // Default each unset classification to on, one at a time.
        //
        // This used to reset all nine whenever all nine were off, which made an
        // all-off selection impossible to hold — the loose == 0 comparison could
        // not tell '' ("never set") from '0' ("turned off") (#203). Defaulting
        // per toggle rather than all-or-nothing also matters for URLs that name
        // only some of them: ?definitive=0 must leave the other eight on rather
        // than stranding them at '' and silently disabling everything (#204).
        $this->normalizeClassificationFilters();

        $totalGenesCount = Gene::has('submissions')->count();
        $submitterIds = $this->submitters
            ->filter(fn ($submitter) => in_array($submitter->ident, $this->curations_from_submitters, true))
            ->pluck('id')
            ->all();

        $enabledCuries = collect(Classification::VOCABULARY)
            ->filter(fn ($metadata) => $this->{$metadata['property']} === '1')
            ->keys()
            ->all();

        // IDs are still the submission foreign keys, but their meaning is
        // resolved from stable CURIEs for this database at query time.
        $enabledClassifications = Classification::whereIn('curie', $enabledCuries)
            ->pluck('id')
            ->all();

        // Normalize here rather than in mount() or updating(), so $this->title
        // keeps whatever the user actually typed or pasted and the filter box
        // still shows it back to them. That also means every path into this
        // component is covered at once — the Livewire-bound filter box and the
        // ?title= parameter mount() reads. The latter matters: the gene search
        // box in shared/gene-headline.blade.php trims client-side before
        // building the URL, but a bookmarked or hand-edited ?title= never runs
        // that JavaScript, so this is the only normalization it gets.
        $query_disease = $this->normalizeSearchTerm($this->hasDisease);
        $query_symbol  = $this->normalizeSearchTerm($this->title);

        $genes = Gene::where('symbol', 'LIKE', '%' . $query_symbol . '%')
            ->whereHas('submissions', function ($q) use ($query_disease, $enabledClassifications, $submitterIds) {
                if (!empty($query_disease)) {
                    $q->whereHas('disease', function ($diseaseQuery) use ($query_disease) {
                        $diseaseQuery->where('name', 'like', '%' . $query_disease . '%');
                    });
                }
                // Applied unconditionally: an empty list means the user turned
                // everything off, which has to match nothing. Skipping the
                // clause would silently widen that to "match everything" (#203).
                $q->whereIn('classification_id', $enabledClassifications);
                $q->whereIn('submitter_id', $submitterIds);
            })
            ->with('submissions')
            ->orderByRaw("
                REGEXP_SUBSTR(symbol, '^[^0-9]+') ASC,
                CAST(REGEXP_SUBSTR(symbol, '[0-9]+', 1, 1) AS UNSIGNED) ASC,
                REGEXP_SUBSTR(symbol, '[^0-9]+', 1, 2) ASC,
                CAST(REGEXP_SUBSTR(symbol, '[0-9]+', 1, 2) AS UNSIGNED) ASC,
                REGEXP_SUBSTR(symbol, '[^0-9]+', 1, 3) ASC,
                CAST(REGEXP_SUBSTR(symbol, '[0-9]+', 1, 3) AS UNSIGNED) ASC,
                REGEXP_SUBSTR(symbol, '[^0-9]+', 1, 4) ASC")
            ->paginate(25);

        $tableHeading = count($genes) != $totalGenesCount
            ? " Genes with classifications based on your filters"
            : " Genes with classifications";

        return view('livewire.genes.listing', [
            'genes' => $genes,
            'submitters' => $this->submitters,
            'curations_from_submitters' => $this->curations_from_submitters,
            'tableHeading' => $tableHeading,
        ]);
    }
}
