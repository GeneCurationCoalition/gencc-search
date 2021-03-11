<?php

namespace App\Http\Livewire\Dashboard\Submitter;

use Livewire\Component;
use App\SubmissionFile;

class SubmitterFileManage extends Component
{
    public $file;

    protected $rules = [
        'file.private_notes' => 'string',
    ];

    public function mount($file) {
        $this->file = $file;
        //dd($this->file->file_name);
    }

    public function toprocess($uuid)
    {
        //dd("sdf");
        $this->file->status = 1;
        $this->file->save();
        session()->flash('message', 'Status updated to enabled...');


        //dd($this->submitter);
    }

    public function processed($uuid)
    {
        //dd("sdf");
        $this->file->status = 0;
        $this->file->save();
        session()->flash('message', 'Status updated to disabled...');


        //dd($this->submitter);
    }

    public function archive($uuid)
    {
        //dd("sdf");
        $this->file->status = 2;
        $this->file->save();
        session()->flash('message', 'Status updated to archive...');

        //dd($this->submitter);
    }

    public function save()
    {
        //dd($this->file->private_notes);
        $this->validate();

        $this->file->save();
        session()->flash('message', 'Notes updated...');
    }

    public function render()
    {
        return view('livewire.dashboard.submitter.submitter-file-manage');
    }
}
