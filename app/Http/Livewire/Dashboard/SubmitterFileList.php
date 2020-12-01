<?php

namespace App\Http\Livewire\Dashboard;

use App\SubmissionFile;
use Livewire\Component;
use App\Submitter;

class SubmitterFileList extends Component
{

    public $submitter;
    public $updated = 0;
    public $files;

    protected $listeners = ['submitterFileAdded' => 'update'];

    public function mount($submitter)
    {
        $this->submitter = $submitter;
        //dd($this->submitter);
    }

    public function enable($uuid)
    {
        //dd("sdf");
        $file = SubmissionFile::uuid($uuid)->first();
        $file->status = 0;
        $file->save();

        $this->submitter = Submitter::curie($this->submitter->curie)->first();
        //dd($this->submitter);
    }

    public function disable($uuid)
    {
        //dd("sdf");
        $file = SubmissionFile::uuid($uuid)->first();
        $file->status = 1;
        $file->save();

        $this->submitter = Submitter::curie($this->submitter->curie)->first();
        //dd($this->submitter);
    }

    public function update()
    {
        $this->updated = 1;
        $this->submitter = Submitter::curie($this->submitter->curie)->first();
        //dd($this->submitter);
    }

    public function render()
    {
        return view('livewire.dashboard.submitter-file-list');
    }
}
