<?php

namespace App\Http\Livewire\Genes;

use Livewire\Component;
use Livewire\WithPagination;
use App\Gene;
use App\Query\Query;
use App\Submitter;
use Illuminate\Http\Request;

class Listing extends Component
{
    use WithPagination;

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
    protected $items;
    protected $submitters;
    // protected $queryString = [
    //     'title'                             => ['except' => ''],
    //     'hasDisease'                        => ['except' => ''],
    //     'curations_definitive'              => ['except' => ''],
    //     'curations_strong'                  => ['except' => ''],
    //     'curations_moderate'                => ['except' => ''],
    //     'curations_limited'                 => ['except' => ''],
    //     'curations_disputed'                => ['except' => ''],
    //     'curations_refuted'                 => ['except' => ''],
    //     'curations_animal'                  => ['except' => ''],
    //     'curations_noknown'                 => ['except' => ''],
    //     'curations_supportive'              => ['except' => ''],
    //     'count_submissions'                 => ['except' => ''],
    //     'count_unique_diseases'             => ['except' => ''],
    //     'sort'                              => ['except' => ''],
    //     'page'                              => ['except' => 1],
    // ];

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

        $this->submitters     = Submitter::has('submissions')->orderBy('title')->get();

        //$curations_from_submitters = $this->submitters->toArray();
        $curations_from_submitters = $this->submitters->pluck(['uuid']);
        //$curations_from_submitters->all();
        //$curations_from_submitters->toArray();

        // $result = Gene::orwhere('curations_definitive', '>', '0')->orwhere('curations_strong', '>', '0')->get();
        // dd($result);'
        //$value = ["GENCC_000104", "GENCC_000105","GENCC_000108","GENCC_000111","GENCC_000102"];
        //$value = ["GENCC_000111", "GENCC_000105"];
        //$result = Gene::whereHas('submissions.submitter', function ($query) use ($value) {
            //return $query->whereIn('uuid', $value);
        //})->get();

        // $result = Submitter::has('submissions')->with('submissions.gene')->orderBy('title')->get();
        //dd($result);


        $this->title                        = request('title');
        $this->hasDisease                   = request('hasDisease');
        // $this->curations_definitive         = (int)request('curations_definitive') ?? 1;
        // $this->curations_strong             = (int)request('curations_strong') ?? 1;
        // $this->curations_moderate           = (int)request('curations_moderate') ?? 1;
        // $this->curations_limited            = (int)request('curations_limited') ?? 1;
        // $this->curations_disputed           = (int)request('curations_disputed') ?? 1;
        // $this->curations_refuted            = (int)request('curations_refuted') ?? 1;
        // $this->curations_animal             = (int)request('curations_animal') ?? 1;
        // $this->curations_noknown            = (int)request('curations_noknown') ?? 1;
        // $this->curations_supportive         = (int)request('curations_supportive') ?? 1;
        $this->curations_from_submitters    = request('curations_from_submitters');
        $this->count_submissions            = request('count_submissions');
        $this->count_unique_diseases        = request('count_unique_diseases');

        //dd($this);
    }

    // public function toggle_curations_definitive()
    // {
    //     $this->curations_definitive = ($this->curations_definitive == 0) ? 1 : '';
    // }
    // public function toggle_curations_strong()
    // {
    //     $this->curations_strong = ($this->curations_strong == 0) ? 1 : '';
    // }
    // public function toggle_curations_moderate()
    // {
    //     $this->curations_moderate = ($this->curations_moderate == 0) ? 1 : '';
    // }
    // public function toggle_curations_limited()
    // {
    //     $this->curations_limited = ($this->curations_limited == 0) ? 1 : '';
    // }
    // public function toggle_curations_disputed()
    // {
    //     $this->curations_disputed = ($this->curations_disputed == 0) ? 1 : '';
    // }
    // public function toggle_curations_refuted()
    // {
    //     $this->curations_refuted = ($this->curations_refuted == 0) ? 1 : '';
    // }
    // public function toggle_curations_animal()
    // {
    //     $this->curations_animal = ($this->curations_animal == 0) ? 1 : '';
    // }
    // public function toggle_curations_noknown()
    // {
    //     $this->curations_noknown = ($this->curations_noknown == 0) ? 1 : '';
    // }
    // public function toggle_curations_supportive()
    // {
    //     $this->curations_supportive = ($this->curations_supportive == 0) ? 1 : '';
    // }
    // public function toggle_count_submissions()
    // {
    //     $this->count_submissions = ($this->count_submissions == 0) ? 1 : '';
    // }
    // public function toggle_count_unique_diseases()
    // {
    //     $this->count_unique_diseases = ($this->count_unique_diseases == 0) ? 1 : '';
    // }

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

        $this->submitters     = Submitter::has('submissions')->orderBy('title')->get();
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
        $submissions    = Gene::has('submissions')->get();
        //$this->return = Gene::orderBy('title')->paginate(5000);
        //$this->return = Query::apply($query)->orderBy('title')->paginate(150);

        //$value = ["GENCC_000104", "GENCC_000105", "GENCC_000108", "GENCC_000111", "GENCC_000102"];
        //dd($this->curations_from_submitters);
        $value = $this->curations_from_submitters;
        $filter = $query;
        $query_disease = $this->hasDisease;
        $query_title = strtoupper($this->title);
        // DB::table('users')
        //     ->where('name', '=', 'John')
        //     ->where(function ($query) {
        //         $query->where('votes', '>', 100)
        //               ->orWhere('title', '=', 'Admin');
        //     })
        //     ->get();
        //$this->return = Gene::where('title', 'LIKE', '%' . $this->title . '%')

            //dd($query_title);
        $this->return = Gene::where('title', 'LIKE', '%' . $this->title . '%')
            ->whereHas('submissions', function ($query) use($query_disease) {
                if (!empty($query_disease)) {
                //dd($disease);
                    $query->where('submitted_as_disease_name', 'like', '%' . $query_disease . '%');
                }
            })->where(function($query) use ($filter, $query_title) {
                if($filter['num_curations_definitive'] > 0) {
                    $query->orwhere('curations_definitive', ">=", $filter['num_curations_definitive']);
                }
                if ($filter['num_curations_strong'] > 0) {
                    $query->orwhere('curations_strong', ">=", $filter['num_curations_strong']);
                }
                if ($filter['num_curations_moderate'] > 0) {
                    $query->orwhere('curations_moderate', ">=", $filter['num_curations_moderate']);
                }
                if ($filter['num_curations_supportive'] > 0) {
                    $query->orwhere('curations_supportive', ">=", $filter['num_curations_supportive']);
                }
                if ($filter['num_curations_limited'] > 0) {
                    $query->orwhere('curations_limited', ">=", $filter['num_curations_limited']);
                }
                if ($filter['num_curations_disputed'] > 0) {
                    $query->orwhere('curations_disputed', ">=", $filter['num_curations_disputed']);
                }
                if ($filter['num_curations_refuted'] > 0) {
                    $query->orwhere('curations_refuted', ">=", $filter['num_curations_refuted']);
                }
                if ($filter['num_curations_animal'] > 0) {
                    $query->orwhere('curations_animal', ">=", $filter['num_curations_animal']);
                }
                if ($filter['num_curations_noknown'] > 0) {
                    $query->orwhere('curations_noknown', ">=", $filter['num_curations_noknown']);
                }
            })->whereHas('submissions.submitter', function ($query) use ($value) {
                $query->whereIn('uuid', $value);
            })

            // ->where('curations_definitive', '>=', $query['or_curations_definitive'])
            // ->orwhere('curations_strong', '>=', $query['or_curations_strong'])
            // ->orwhere('curations_moderate', '>=', $query['or_curations_moderate'])
            // ->orwhere('curations_supportive', '>=', $query['or_curations_supportive'])
            // ->orwhere('curations_limited', '>=', $query['or_curations_limited'])
            // ->orwhere('curations_disputed', '>=', $query['or_curations_disputed'])
            // ->orwhere('curations_refuted', '>=', $query['or_curations_refuted'])
            // ->orwhere('curations_animal', '>=', $query['or_curations_animal'])
            // ->orwhere('curations_noknown', '>=', $query['or_curations_noknown'])

            ->whereHas('submissions')->orderBy('title')->paginate(100);

        //dd(count($this->return));

        if(count($this->return) != count($submissions)) {
            $tableHeading = " Genes with classifications based on your filters";
        } else {
            $tableHeading = " Genes with classifications";
        }

        //dd($this->return);
        //dd($this->submitters);
        //dd($this->curations_from_submitters);

        return view('livewire.genes.listing', [
            'genes' => $this->return,
            'submitters' => $this->submitters,
            'curations_from_submitters' => $this->curations_from_submitters,
            'tableHeading' => $tableHeading,
        ]);
    }
}
