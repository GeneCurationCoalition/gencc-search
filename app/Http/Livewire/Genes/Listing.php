<?php

namespace App\Http\Livewire\Genes;

use Livewire\Component;
use Livewire\WithPagination;
use App\Gene;
use App\Submitter;

class Listing extends Component
{
    use WithPagination;

    protected $paginationTheme = 'default';

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
    protected $submitters;

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
        $this->submitters = Submitter::has('submissions')->orderBy('name')->get();

        $this->title                        = request('title');
        $this->hasDisease                   = request('hasDisease');
        $this->curations_from_submitters    = request('curations_from_submitters');
        $this->count_submissions            = request('count_submissions');
        $this->count_unique_diseases        = request('count_unique_diseases');
    }

    public function curationsFromSubmitters($value)
    {
        // This sets a flag to show a message on the front end that the curations counts in the pulls don't tally correctly yet
        $this->filtering_by_submitter = true;
        $array = $this->curations_from_submitters;
        if (in_array($value[0], $array)) {
            $result = array_diff($array, $value);
        } else {
            $result = array_merge($array, $value);
        }
        $this->curations_from_submitters = array_unique($result);
    }



    public function render()
    {

        $this->submitters     = Submitter::has('submissions')->orderBy('name')->get();
        if(empty($this->curations_from_submitters)){
            $curations_from_submitters = $this->submitters->pluck(['uuid']);
            $this->curations_from_submitters = $curations_from_submitters->toArray();
        }
        if(count($this->submitters) == count($this->curations_from_submitters)) {
            $this->filtering_by_submitter = false;
        }

        // TODO
        // Check if all of the filters are off, if so, switch all back on
        if(
            ($this->curations_definitive == 0) &&
            ($this->curations_strong == 0) &&
            ($this->curations_moderate == 0) &&
            ($this->curations_supportive == 0) &&
            ($this->curations_limited == 0) &&
            ($this->curations_disputed == 0) &&
            ($this->curations_refuted == 0) &&
            ($this->curations_animal == 0) &&
            ($this->curations_noknown == 0)
        ) {
            $this->curations_definitive            = 1;
            $this->curations_strong                = 1;
            $this->curations_moderate              = 1;
            $this->curations_limited               = 1;
            $this->curations_disputed              = 1;
            $this->curations_refuted               = 1;
            $this->curations_animal                = 1;
            $this->curations_noknown               = 1;
            $this->curations_supportive            = 1;
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
                if (!empty($enabledClassifications)) {
                    $q->whereIn('classification_id', $enabledClassifications);
                }
                if (!empty($submitterIds)) {
                    $q->whereIn('submitter_id', $submitterIds);
                }
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
