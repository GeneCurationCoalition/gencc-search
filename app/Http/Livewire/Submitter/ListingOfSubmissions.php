<?php

namespace App\Http\Livewire\Submitter;

use App\Classification;
use App\Submission;
use App\Traits\NormalizesSearchInput;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Livewire\Component;
use Livewire\WithPagination;

class ListingOfSubmissions extends Component
{

    use WithPagination;
    use NormalizesSearchInput;

    public $count = 0;
    public $submitter_id;
    public $query_disease;
    public $query_gene;
    public $date;
    public $filter;
    public $filter_enabled = false;
    public $filter_set = [];
    protected $records;
    protected $filtersThatResetPage = [
        'query_disease',
        'query_gene',
        'filter_set',
    ];

    public function mount($submitter)
    {
        //dd($gene);
        $this->submitter_id = $submitter->id;

        $this->date = new DateTime();
        $this->date =  $this->date->getTimestamp();
        $this->filter_set['classifications'] = array();
        $this->filter_set['diseases'] = array();
        $this->filter_set['genes'] = array();
        $this->filter_set['inheritances'] = array();
        $this->filter_set['submitters'] = array();

        //dd($this->filter_set);
    }

    public function increment()
    {
        $this->count++;
    }

    public function updating($name, $value)
    {
        $property = explode('.', $name)[0];

        if (in_array($property, $this->filtersThatResetPage, true)) {
            $this->resetPage();
        }
    }

    public function filterByClassifications($value)
    {
        $this->resetPage();
        //dd($value);
        //dd($this->filter_set);
        //$array = [];
        //if(isset($this->filter_set['classifications'])) {
        $array = $this->filter_set['classifications'];
        //}
        if (in_array($value[0], $array)) {
            $result = array_diff($array, $value);
        } else {
            $result = array_merge($array, $value);
        }

        $this->filter_set['classifications'] = array_unique($result);
        //dd($this->filter_set);
        //$this->filter['classifications_id'][$submission->classification->uuid]          = $submission->classification->id;

    }

    public function filterByGenes($value)
    {
        $this->resetPage();
        $array = [];
        if (isset($this->filter_set['genes'])) {
            $array = $this->filter_set['genes'];
        }

        if (in_array($value[0], $array)) {
            $result = array_diff($array, $value);
        } else {
            $result = array_merge($array, $value);
        }
        $this->filter_set['genes'] = array_unique($result);
    }

    public function filterByDiseases($value)
    {
        $this->resetPage();
        $array = [];
        if (isset($this->filter_set['diseases'])) {
            $array = $this->filter_set['diseases'];
        }

        if (in_array($value[0], $array)) {
            $result = array_diff($array, $value);
        } else {
            $result = array_merge($array, $value);
        }
        $this->filter_set['diseases'] = array_unique($result);
    }

    public function filterByInheritances($value)
    {
        $this->resetPage();

        $array = [];
        if (isset($this->filter_set['inheritances'])) {
            $array = $this->filter_set['inheritances'];
        }

        if (in_array($value[0], $array)) {
            $result = array_diff($array, $value);
        } else {
            $result = array_merge($array, $value);
        }
        $this->filter_set['inheritances'] = array_unique($result);
    }

    public function filterBySubmitters($value)
    {
        $this->resetPage();

        $array = [];
        if (isset($this->filter_set['submitters'])) {
            $array = $this->filter_set['submitters'];
        }

        $array = $this->filter_set['submitters'];
        if (in_array($value[0], $array)) {
            $result = array_diff($array, $value);
        } else {
            $result = array_merge($array, $value);
        }
        $this->filter_set['submitters'] = array_unique($result);
    }


    public function render()
    {

        $submitter_id = $this->submitter_id;

        // Build base query for submissions
        $baseQuery = Submission::where('submitter_id', '=', $submitter_id)
            ->where('is_live', '=', true)
            ->where('status', '=', Submission::STATUS_PUBLISHED);

        // Get count without loading all records
        $count_submissions = $baseQuery->count();

        $this->filter = [
            'classifications' => [],
            'genes' => [],
            'diseases' => [],
            'inheritances' => [],
            'submitters' => [],
        ];

        // Build filter options using efficient database queries with minimal columns
        // This avoids loading full Eloquent models for thousands of records

        // Get distinct classifications (only ~9 possible, safe to load)
        $classificationIds = (clone $baseQuery)->distinct()->pluck('classification_id');
        $classifications = Classification::orderCollection(
            Classification::whereIn('id', $classificationIds)
                ->select('id', 'ident', 'curie', 'name')
                ->get()
        );
        foreach ($classifications as $classification) {
            $this->filter['classifications'][$classification->ident] = [
                'title' => $classification->name,
                'ref' => $classification->id,
                'uuid' => $classification->ident,
            ];
        }

        // Get distinct genes - use cursor for memory efficiency with large sets
        $geneIds = (clone $baseQuery)->distinct()->pluck('gene_id');
        $genes = \Illuminate\Support\Facades\DB::table('genes')
            ->whereIn('id', $geneIds)
            ->select('id', 'ident', 'symbol')
            ->orderBy('symbol')
            ->cursor();
        foreach ($genes as $gene) {
            $this->filter['genes'][$gene->ident] = [
                'title' => $gene->symbol,
                'ref' => $gene->id,
                'uuid' => $gene->ident,
            ];
        }

        // Get distinct diseases - use cursor for memory efficiency with large sets
        $diseaseIds = (clone $baseQuery)->distinct()->pluck('disease_id');
        $diseases = \Illuminate\Support\Facades\DB::table('diseases')
            ->whereIn('id', $diseaseIds)
            ->select('id', 'ident', 'name')
            ->orderBy('name')
            ->cursor();
        foreach ($diseases as $disease) {
            $this->filter['diseases'][$disease->ident] = [
                'title' => ucfirst($disease->name),
                'ref' => $disease->id,
                'uuid' => $disease->ident,
            ];
        }

        // Get distinct inheritances (limited set, safe to load)
        $inheritanceIds = (clone $baseQuery)->distinct()->whereNotNull('inheritance_id')->pluck('inheritance_id');
        $inheritances = \Illuminate\Support\Facades\DB::table('inheritances')
            ->whereIn('id', $inheritanceIds)
            ->select('id', 'ident', 'name')
            ->get();
        foreach ($inheritances as $inheritance) {
            $this->filter['inheritances'][$inheritance->ident] = [
                'title' => $inheritance->name,
                'ref' => $inheritance->id,
                'uuid' => $inheritance->ident,
            ];
        }

        // Get the submitter for this page (just one)
        $submitter = \Illuminate\Support\Facades\DB::table('submitters')
            ->where('id', $submitter_id)
            ->select('id', 'ident', 'name')
            ->first();
        if ($submitter) {
            $this->filter['submitters'][$submitter->ident] = [
                'title' => $submitter->name,
                'ref' => $submitter->id,
                'uuid' => $submitter->ident,
            ];
        }

        // Genes and diseases are already sorted by DB query
        // Only need to sort inheritances and submitters if needed
        if($count_submissions > 0) {
            $this->filter['inheritances'] = Arr::sortRecursive($this->filter['inheritances']);
            $this->filter['submitters'] = Arr::sortRecursive($this->filter['submitters']);
        }

        $filter        = $this->filter;
        $filter_set        = $this->filter_set;

        //dd($filter);
        if (
            count($filter_set['classifications']) ||
            count($filter_set['genes']) ||
            count($filter_set['diseases']) ||
            count($filter_set['inheritances']) ||
            count($filter_set['submitters'])
        ) {
            $this->filter_enabled = true;
        }
            //dd($filter_set);
            //dd($filter);
            $has_records = Submission::where('submitter_id', '=', $submitter_id)->where('is_live', '=', true)->where('status', '=', Submission::STATUS_PUBLISHED)->count();
            //dd($has_records);
            $idsByCurie = Classification::whereIn('curie', array_keys(Classification::VOCABULARY))
                ->pluck('id', 'curie');
            $whens = '';

            foreach (Classification::VOCABULARY as $curie => $metadata) {
                if ($idsByCurie->has($curie)) {
                    $whens .= ' WHEN ' . (int) $idsByCurie->get($curie)
                        . ' THEN ' . (int) $metadata['priority'];
                }
            }

            // A CASE with no WHEN clauses is a SQL syntax error. With nothing
            // ranked every row ties, leaving report_date to order the page.
            $classificationOrder = $whens === ''
                ? '99'
                : 'CASE classification_id' . $whens . ' ELSE 99 END';

            $records = Submission::where('submitter_id', '=', $submitter_id)
                ->whereHas('classification', function (Builder $query) use ($filter, $filter_set) {
                    //foreach ($filter['classifications'] as $key => $item) {
                        //dd($filter_set['classifications']);
                        $query->whereNotIn('id', $filter_set['classifications']);
                    //}

                })->whereHas('disease', function (Builder $query) use ($filter, $filter_set) {
                    //foreach ($filter['diseases'] as $key => $item) {
                        //dd($filter_set['classifications']);
                        //$query->whereNotIn('id', $filter_set['diseases']);
                    //}
                    $query_disease = $this->normalizeSearchTerm($this->query_disease);
                    if (!empty($query_disease)) {
                        $query->where('name', 'like', '%' . $query_disease . '%');
                    }
                })->whereHas('inheritance', function (Builder $query) use ($filter, $filter_set) {
                    //foreach ($filter['inheritances'] as $key => $item) {
                        //dd($filter_set['classifications']);
                        $query->whereNotIn('id', $filter_set['inheritances']);
                    //}
                })->whereHas('gene', function (Builder $query) use ($filter, $filter_set) {
                    //foreach ($filter['genes'] as $key => $item) {
                        //dd($filter_set['classifications']);
                        //$query->whereNotIn('id', $filter_set['genes']);
                    //}
                    $query_gene = $this->normalizeSearchTerm($this->query_gene);
                    if (!empty($query_gene)) {
                        $query->where('symbol', 'like', '%' . $query_gene . '%');
                    }
                })->whereHas('submitter', function (Builder $query) use ($filter, $filter_set) {
                    //foreach ($filter['submitters'] as $key => $item) {
                        //dd($filter_set['classifications']);
                        $query->whereNotIn('id', $filter_set['submitters']);
                    //}
                 })
                // ->with(['classification' => function ($q) {
                //         $q->orderBy('title', 'DESC');
                //     }])
                ->where('is_live', '=', true)
                ->where('status', '=', Submission::STATUS_PUBLISHED)
                ->orderByRaw($classificationOrder)
                ->orderBy('report_date', 'DESC')
                ->paginate(20);
        // }

        // $posts = App\Models\Post::whereHas('comments', function (Builder $query) {
        //     $query->where('content', 'like', 'foo%');
        // })->get();

        //dd($records);
        return view('livewire.submitter.listing-of-submissions', [
            'records' => $records,
            'has_records' => $has_records,
            'filter' => $this->filter,
            'count_submissions' => $count_submissions,
            'filter_set' => $filter_set
        ]);
    }
}
