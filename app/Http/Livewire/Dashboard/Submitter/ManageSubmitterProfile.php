<?php

namespace App\Http\Livewire\Dashboard\Submitter;

use App\Submitter;
use Livewire\Component;

class ManageSubmitterProfile extends Component
{
    public $submitter;
    public $newUuid;
    public $newCurie;
    public $status;
    public $title;
    public $curie;
    public $uuid;
    public $website;
    public $text_descriptions;
    public $text_contact;
    public $text_assertions;
    public $text_disclaimer;
    public $downloadable;

    public function mount($submitter)
    {
        $submitter = $submitter;
        //dd($submitter);
        if($submitter != null) {
            $this->title               = $submitter->title;
            $this->curie               = $submitter->curie;
            $this->uuid                = $submitter->uuid;
            $this->website             = $submitter->website;
            $this->text_descriptions   = $submitter->text_descriptions;
            $this->text_contact        = $submitter->text_contact;
            $this->text_assertions     = $submitter->text_assertions;
            $this->text_disclaimer     = $submitter->text_disclaimer;
            $this->downloadable        = $submitter->downloadable;
        } else {

            $submitter = Submitter::latest('id')->first();
            $lastNumber = str_replace('GENCC:', '', $submitter->curie);
            //$lastUuid =
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
            $newUuid    = "GENCC_" . $newNumber;
            $newCurie   = "GENCC:" . $newNumber;

            $this->title               = "";
            $this->curie               = $newCurie;
            $this->uuid                = $newUuid;
            $this->website             = "";
            $this->text_descriptions   = "";
            $this->text_contact        = "";
            $this->text_assertions     = "";
            $this->text_disclaimer     = "";
            $this->downloadable        = "1";

            $submitter->curie               = $newCurie;
            $submitter->uuid                = $newUuid;
        }
    }

    protected $rules = [
        'title' => 'required|min:2',
        'curie' => 'required',
        'website' => 'required',
        'text_contact' => 'required',
        'text_assertions' => 'required',
        'downloadable' => 'required|integer',
    ];

    public function create()
    {
        $this->validate();
        //dd("dfsdfdf");
        $submitter = Submitter::updateOrCreate(
            ['uuid'                     =>  $this->uuid],
            [
                'title'                 => $this->title,
                'curie'                 => $this->curie,
                'uuid '                 => $this->uuid,
                'website'               => $this->website,
                'text_descriptions'     => $this->text_descriptions,
                'text_contact'          => $this->text_contact,
                'text_assertions'       => $this->text_assertions,
                'text_disclaimer'       => $this->text_disclaimer,
                'downloadable'          => $this->downloadable,
                'status'                => 0,
            ]
        );
        return redirect()->to('/dashboard/admin/'.$this->curie.'/profile');
        //return redirect()->route('manage-submitter-show-profile', [$this->uuid]);
    }

    public function submit()
    {
        $this->validate();

        //dd(request());
        $submitter = Submitter::updateOrCreate(
            ['uuid'                     =>  $this->uuid],
            [
                'title'                 => $this->title,
                'curie'                 => $this->curie,
                'uuid '                 => $this->uuid,
                'website'               => $this->website,
                'text_descriptions'     => $this->text_descriptions,
                'text_contact'          => $this->text_contact,
                'text_assertions'       => $this->text_assertions,
                'text_disclaimer'       => $this->text_disclaimer,
                'downloadable'          => $this->downloadable,
            ]
        );
        session()->flash('message', 'Record Saved...');
    }

    public function render()
    {
        return view('livewire.dashboard.submitter.manage-submitter-profile');
    }
}
