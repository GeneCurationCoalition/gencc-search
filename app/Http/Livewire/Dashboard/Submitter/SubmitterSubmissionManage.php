<?php

namespace App\Http\Livewire\Dashboard\Submitter;

use Livewire\Component;
use App\SubmissionFile;

class SubmitterSubmissionManage extends Component
{
    public $submission;

    protected $rules = [
        'submission.private_notes' => 'string',
    ];


    public function mount($submission) {
        $this->submission = $submission;
        //dd($this->file->file_name);
    }

    public function enable($uuid)
    {
        //dd("sdf");
        $this->submission->status = 1;
        $this->submission->save();
        session()->flash('message', 'Status updated to enabled...');


        //dd($this->submitter);
    }

    public function disable($uuid)
    {
        //dd("sdf");
        $this->submission->status = 0;
        $this->submission->save();
        session()->flash('message', 'Status updated to disabled...');


        //dd($this->submitter);
    }

    public function archive($uuid)
    {
        //dd("sdf");
        $this->submission->status = 2;
        $this->submission->save();
        session()->flash('message', 'Status updated to archive...');

        //dd($this->submitter);
    }

    public function save()
    {
        //dd($this->file->private_notes);
        $this->validate();

        $this->submission->save();
        session()->flash('message', 'Notes updated...');
        session()->flash('note_button', ' [Saved]');
    }

    public function render()
    {
        return view('livewire.dashboard.submitter.submitter-submission-manage');
    }
}
