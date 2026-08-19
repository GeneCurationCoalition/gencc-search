<?php

namespace App\Http\Livewire\Genes;

use Livewire\Component;
use Livewire\WithPagination;
use App\Gene;
use App\Submitter;

class Listing extends Component
{
    use WithPagination;

    /**
     * ?submitters= value meaning "deliberately none selected", as distinct from
     * an absent parameter meaning "no submitter filter at all" (#204).
     */
    const SUBMITTERS_NONE = 'none';

    public $title                           = '';
    public $hasDisease                      = '';
    public $curations_definitive            = '';
    public $curations_strong                = '';
    public $curations_moderate              = '';
    public $curations_limited               = '';
    public $curations_disputed              = '';
    public $curations_refuted               = '';
    public $curations_animal                = '';
    public $curations_noknown               = '';
    public $curations_supportive            = '';
    public $curations_from_submitters       = [];
    public $count_submissions               = '';
    public $count_unique_diseases           = '';
    public $filtering_by_submitter          = false;
    public $sort                            = '';
    public $page                            = 1;
    /**
     * Comma-joined submitter idents, the URL-facing form of
     * curations_from_submitters. Empty means "all submitters" (#204).
     */
    public $submitterFilter                 = '';
    protected $submitters;

    /**
     * The nine classification toggles, in the order they appear in the UI.
     */
    protected $classificationFilters = [
        'curations_definitive',
        'curations_strong',
        'curations_moderate',
        'curations_supportive',
        'curations_limited',
        'curations_disputed',
        'curations_refuted',
        'curations_animal',
        'curations_noknown',
    ];

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
    protected $queryString = [
        'title'                  => ['except' => ''],
        'hasDisease'             => ['except' => ''],
        'curations_definitive'   => ['except' => '1', 'as' => 'definitive'],
        'curations_strong'       => ['except' => '1', 'as' => 'strong'],
        'curations_moderate'     => ['except' => '1', 'as' => 'moderate'],
        'curations_supportive'   => ['except' => '1', 'as' => 'supportive'],
        'curations_limited'      => ['except' => '1', 'as' => 'limited'],
        'curations_disputed'     => ['except' => '1', 'as' => 'disputed'],
        'curations_refuted'      => ['except' => '1', 'as' => 'refuted'],
        'curations_animal'       => ['except' => '1', 'as' => 'animal'],
        'curations_noknown'      => ['except' => '1', 'as' => 'noknown'],
        'submitterFilter'        => ['except' => '', 'as' => 'submitters'],
    ];

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

        // Coalesced to '' rather than left null: null would round-trip into the
        // URL as an empty ?title=, defeating the 'except' rules above (#204).
        $this->title                        = request('title', '') ?? '';
        $this->hasDisease                   = request('hasDisease', '') ?? '';
        $this->count_submissions            = request('count_submissions');
        $this->count_unique_diseases        = request('count_unique_diseases');

        // ?submitters=a,b,c wins if present. Otherwise stay null so render()
        // defaults to every submitter; the legacy ?curations_from_submitters=
        // array form is still honoured.
        $this->submitterFilter = request('submitters', '') ?? '';

        if ($this->submitterFilter !== '') {
            $this->curations_from_submitters = $this->expandSubmitterFilter($this->submitterFilter);
        } else {
            $this->curations_from_submitters = request('curations_from_submitters');
        }
    }

    /**
     * Turn ?submitters=a,b,c into the array the query builder wants, keeping only
     * idents that exist so a stale or hand-edited URL cannot empty the listing.
     */
    private function expandSubmitterFilter($value)
    {
        // An empty selection cannot be spelled as an empty string, since that is
        // also how "no filter" is spelled. Hence the explicit sentinel.
        if ($value === self::SUBMITTERS_NONE) {
            return [];
        }

        $requested = array_filter(array_map('trim', explode(',', $value)), 'strlen');

        if (empty($requested)) {
            return null;
        }

        $known = $this->submittersWithSubmissions()->pluck('uuid')->toArray();
        $valid = array_values(array_intersect($requested, $known));

        return empty($valid) ? null : $valid;
    }

    /**
     * Keep the URL-facing ?submitters= list in step with the selection. Cleared
     * when every submitter is selected, so an unfiltered page has a clean URL.
     */
    private function syncSubmitterFilter()
    {
        $selected = $this->curations_from_submitters ?? [];
        $total = $this->submittersWithSubmissions()->count();

        if (count($selected) === $total) {
            $this->submitterFilter = '';
        } elseif (empty($selected)) {
            $this->submitterFilter = self::SUBMITTERS_NONE;
        } else {
            $this->submitterFilter = implode(',', $selected);
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

        foreach ($this->classificationFilters as $filter) {
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
        $this->curations_from_submitters = array_unique($result);
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

        foreach ($this->classificationFilters as $filter) {
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
        // Queried rather than read from $this->submitters: that property is
        // protected, so Livewire does not carry it across requests and it is null
        // by the time an action runs.
        $this->curations_from_submitters = $this->submittersWithSubmissions()->pluck('uuid')->toArray();
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
        return Submitter::has('submissions')->orderBy('name')->get();
    }




    public function render()
    {

        $this->submitters     = $this->submittersWithSubmissions();
        // Default to every submitter on a fresh load, but leave an explicitly
        // emptied selection alone — is_null, not empty(), so that "none" sticks
        // instead of snapping back to all (#203).
        if(is_null($this->curations_from_submitters)){
            $curations_from_submitters = $this->submitters->pluck(['uuid']);
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
        foreach ($this->classificationFilters as $filter) {
            if ($this->$filter === '' || is_null($this->$filter)) {
                $this->$filter = 1;
            }
        }

        $query = [
            //'title'                         => $this->title,
            //'hasDisease'                    => $this->hasDisease,
            'num_curations_definitive'          => (int)$this->curations_definitive,
            'action_curations_definitive'       => ($this->curations_definitive == 0 ? "=" : ">="),
            'num_curations_strong'              => (int)$this->curations_strong,
            'action_curations_strong'           => ($this->curations_strong == 0 ? "=" : ">="),
            'num_curations_moderate'            => (int)$this->curations_moderate,
            'action_curations_moderate'         => ($this->curations_moderate == 0 ? "=" : ">="),
            'num_curations_supportive'          => (int)$this->curations_supportive,
            'action_curations_supportive'       => ($this->curations_supportive == 0 ? "=" : ">="),
            'num_curations_limited'             => (int)$this->curations_limited,
            'action_curations_limited'          => ($this->curations_limited == 0 ? "=" : ">="),
            'num_curations_disputed'            => (int)$this->curations_disputed,
            'action_curations_disputed'         => ($this->curations_disputed == 0 ? "=" : ">="),
            'num_curations_refuted'             => (int)$this->curations_refuted,
            'action_curations_refuted'          => ($this->curations_refuted == 0 ? "=" : ">="),
            'num_curations_animal'              => (int)$this->curations_animal,
            'action_curations_animal'           => ($this->curations_animal == 0 ? "=" : ">="),
            'num_curations_noknown'             => (int)$this->curations_noknown,
            'action_curations_noknown'          => ($this->curations_noknown == 0 ? "=" : ">="),
            //'or_curations_from_submitters'  => $this->curations_from_submitters ?? $curations_from_submitters,
            //'count_submissions'             => $this->count_submissions,
            //'count_unique_diseases'         => $this->count_unique_diseases,
        ];
        //dd($query);
        $totalGenesCount = Gene::has('submissions')->count();
        $submitterIds = $this->curations_from_submitters
            ? Submitter::whereIn('ident', $this->curations_from_submitters)->pluck('id')->toArray()
            : [];

        // Build array of enabled classification IDs based on filter toggles
        $enabledClassifications = [];
        if ($query['num_curations_definitive'] > 0) $enabledClassifications[] = 1;
        if ($query['num_curations_strong'] > 0) $enabledClassifications[] = 2;
        if ($query['num_curations_moderate'] > 0) $enabledClassifications[] = 3;
        if ($query['num_curations_supportive'] > 0) $enabledClassifications[] = 4;
        if ($query['num_curations_limited'] > 0) $enabledClassifications[] = 5;
        if ($query['num_curations_disputed'] > 0) $enabledClassifications[] = 6;
        if ($query['num_curations_refuted'] > 0) $enabledClassifications[] = 7;
        if ($query['num_curations_animal'] > 0) $enabledClassifications[] = 8;
        if ($query['num_curations_noknown'] > 0) $enabledClassifications[] = 9;

        $query_disease = $this->hasDisease;

        $genes = Gene::where('symbol', 'LIKE', '%' . $this->title . '%')
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
