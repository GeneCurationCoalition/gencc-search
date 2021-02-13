<?php

namespace App\Http\Livewire\Dashboard\Submitter;

use App\Classification;
use App\Submission;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Livewire\Component;
use Livewire\WithPagination;

class ManageListingOfSubmissions extends Component
{

    use WithPagination;

    public $count = 0;
    public $submitter_id;
    public $query_disease;
    public $query_gene;
    public $date;
    public $filter;
    public $filter_enabled = false;
    public $filter_set = [];
    protected $records;

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
        $this->filter_set['status'] = array();

        //dd($this->filter_set);
    }

    public function enable($uuid)
    {
        //dd("sdf");
        $item = Submission::where('uuid', '=', $uuid)->first();
        $item->status = 1;
        $item->save();

        $records = Submission::where('submitter_id', '=', $this->submitter_id)->get();

        //dd($this->submitter);
    }

    public function disable($uuid)
    {
        //dd("sdf");
        $item = Submission::where('uuid', '=', $uuid)->first();
        //dd($item);
        $item->status = 0;
        $item->save();

        $records = Submission::where('submitter_id', '=', $this->submitter_id)->get();

        //dd($this->submitter);
    }

    public function archive($uuid)
    {
        //dd("sdf");
        $item = Submission::where('uuid', '=', $uuid)->first();
        $item->status = 2;
        $item->save();

        $records = Submission::where('submitter_id', '=', $this->submitter_id)->get();

        //dd($this->submitter);
    }

    public function filterByClassifications($value)
    {
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

    public function filterByStatus($value)
    {
        $array = [];
        if (isset($this->filter_set['status'])) {
            $array = $this->filter_set['status'];
        }

        if (in_array($value[0], $array)) {
            $result = array_diff($array, $value);
        } else {
            $result = array_merge($array, $value);
        }
        $this->filter_set['status'] = array_unique($result);
    }

    public function filterBySubmitters($value)
    {

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
        $records = Submission::where('submitter_id', '=', $submitter_id)->get();

        $count_submissions = $records->count();

        $this->filter = [];

        foreach ($records as $submission) {
            $count_submissions++;

            $this->filter['status'][0]['title']        = "Disabled";
            $this->filter['status'][0]['ref']          = 0;
            $this->filter['status'][0]['uuid']         = 0;

            $this->filter['status'][1]['title']        = "Enabled";
            $this->filter['status'][1]['ref']          = 1;
            $this->filter['status'][1]['uuid']         = 1;

            $this->filter['status'][2]['title']        = "Archive";
            $this->filter['status'][2]['ref']          = 2;
            $this->filter['status'][2]['uuid']         = 2;

            $this->filter['classifications'][$submission->classification->uuid]['title']        = $submission->classification->title;
            $this->filter['classifications'][$submission->classification->uuid]['ref']          = $submission->classification->id;
            $this->filter['classifications'][$submission->classification->uuid]['uuid']         = $submission->classification->uuid;
            //$this->filter['classifications_id']['ref_' . $submission->classification->id]   = $submission->classification->id;
            $this->filter['genes'][$submission->gene->uuid]['title']                            = $submission->gene->title;
            $this->filter['genes'][$submission->gene->uuid]['ref']                              = $submission->gene->id;
            $this->filter['genes'][$submission->gene->uuid]['uuid']                             = $submission->gene->uuid;
            //$this->filter['genes_id']['ref_' . $submission->gene->id]                       = $submission->gene->id;

            // if ($submission->displayMondoDisease($submission->diseases)->first()) {
            // $this->filter['diseases'][$submission->displayMondoDisease($submission->diseases)->first()->uuid]['title']                      = ucfirst($submission->displayMondoDisease($submission->diseases)->first()->title);
            // $this->filter['diseases'][$submission->displayMondoDisease($submission->diseases)->first()->uuid]['ref']                        = $submission->displayMondoDisease($submission->diseases)->first()->id;
            // $this->filter['diseases'][$submission->displayMondoDisease($submission->diseases)->first()->uuid]['uuid']                       = $submission->displayMondoDisease($submission->diseases)->first()->uuid;
            // }

            if ($submission->disease) {
                $this->filter['diseases'][$submission->disease->uuid]['title']                      = ucfirst($submission->disease->title);
                $this->filter['diseases'][$submission->disease->uuid]['ref']                        = $submission->disease->id;
                $this->filter['diseases'][$submission->disease->uuid]['uuid']                       = $submission->disease->uuid;
            }
            //$this->filter['diseases_id']['ref_' . $submission->disease->id]                 = $submission->disease->id;
            $this->filter['inheritances'][$submission->inheritance->uuid]['title']              = $submission->inheritance->title;
            $this->filter['inheritances'][$submission->inheritance->uuid]['ref']                = $submission->inheritance->id;
            $this->filter['inheritances'][$submission->inheritance->uuid]['uuid']               = $submission->inheritance->uuid;
            //$this->filter['inheritances_id']['ref_' . $submission->inheritance->id]         = $submission->inheritance->id;
            $this->filter['submitters'][$submission->submitter->uuid]['title']                  = $submission->submitter->title;
            $this->filter['submitters'][$submission->submitter->uuid]['ref']                    = $submission->submitter->id;
            $this->filter['submitters'][$submission->submitter->uuid]['uuid']                   = $submission->submitter->uuid;
            //$this->filter['submitters_id']['ref_' . $submission->submitter->id]             = $submission->submitter->id;
        }


        if ($records->count()) {
            $this->filter['genes'] = Arr::sortRecursive($this->filter['genes']);
            //this->filter['diseases'] = Arr::sortRecursive($this->filter['diseases']);
            $this->filter['diseases'] = array_values(Arr::sort($this->filter['diseases'], function ($value) {
                return $value['title'];
            }));
            $this->filter['inheritances'] = Arr::sortRecursive($this->filter['inheritances']);
            $this->filter['submitters'] = Arr::sortRecursive($this->filter['submitters']);
        }
        //dd($this->filter);

        //  dd($records);
        //dd($this->filter);

        $filter        = $this->filter;
        $filter_set        = $this->filter_set;

        //dd($filter);
        if (
            count($filter_set['classifications']) ||
            count($filter_set['genes']) ||
            count($filter_set['diseases']) ||
            count($filter_set['inheritances']) ||
            count($filter_set['submitters']) ||
            count($filter_set['status'])
        ) {
            $this->filter_enabled = true;
        }
        //dd($filter_set);
        //dd($filter);

            foreach($filter_set['status'] as $status) {
                //dd($status);
                $filter_set['set_status'][] = $status;
            }

            if(!isset($filter_set['set_status'])) {
                $filter_set['set_status'] = [1,2,3];
            }

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
                    if (!empty($this->query_disease)) {
                    //dd($disease);
                        $query->where('title', 'like', '%' . $this->query_disease . '%');
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
                    if (!empty($this->query_gene)) {
                    //dd($disease);
                        $query->where('title', 'like', '%' . $this->query_gene . '%');
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
                ->whereIn('status', $filter_set['set_status'])
                ->orderBy('order', 'ASC')
                ->paginate(10);
        // }

        // $posts = App\Models\Post::whereHas('comments', function (Builder $query) {
        //     $query->where('content', 'like', 'foo%');
        // })->get();

        //dd($records);
        return view('livewire.dashboard.submitter.manage-listing-of-submissions', [
            'records' => $records,
            'filter' => $this->filter,
            'count_submissions' => $count_submissions,
            'filter_set' => $filter_set
        ]);
    }
}
