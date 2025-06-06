<?php

namespace App\Http\Livewire\Dashboard\Submitter;

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

    public function toprocess($uuid)
    {
        //dd("sdf");
        $file = SubmissionFile::uuid($uuid)->first();
        $file->status = 1;
        $file->save();

        $this->submitter = Submitter::curie($this->submitter->curie)->first();
        //dd($this->submitter);
    }

    public function notes($uuid)
    {
        dd("sdf");
        $file = SubmissionFile::uuid($uuid)->first();
        $file->private_notes = 1;
        $file->save();

        $this->submitter = Submitter::curie($this->submitter->curie)->first();
        //dd($this->submitter);
    }

    public function processed($uuid)
    {
        //dd("sdf");
        $file = SubmissionFile::uuid($uuid)->first();
        $file->status = 0;
        $file->save();

        $this->submitter = Submitter::curie($this->submitter->curie)->first();
        //dd($this->submitter);
    }

    public function archive($uuid)
    {
        //dd("sdf");
        $file = SubmissionFile::uuid($uuid)->first();
        $file->status = 2;
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
        return view('livewire.dashboard.submitter.submitter-file-list');
    }
}
