<?php

namespace App\Http\Livewire\Dashboard;

use App\SubmissionFile;
use App\Submitter;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class SubmissionUpload extends Component
{
    use WithFileUploads;

    public $file;
    public $upload;
    public $size;
    public $submitter;

    public function mount($submitter)
    {
        $this->submitter = $submitter->uuid;
        //dd($this->submitter);
    }

    public function clear()
    {
        $this->size = null;
        //dd("asasd");
    }

    public function save()
    {
        $this->validate([
            'file' => 'required|mimes:xls,xlsx|max:1024', // 1MB Max
        ]);

        if(isset($this->file)){
            //dd();
            $this->upload = $this->file->storeAs('file', $this->submitter."-". $this->file->getClientOriginalName());
            $resource       = explode("/", $this->upload);
            $file_name      = end($resource);
            $resource       = explode(".", $this->upload);
            $file_type      = end($resource);
            $this->size     = Storage::size($this->upload);
            $file_size      = $this->size;
        }

        $file = SubmissionFile::create(
            [
                'path'          => $this->upload,
                'file_name'     => $file_name,
                'file_type'     => $file_type,
                'file_size'     => $file_size,
                'status'        => 1
            ]
        );

        //dd($this->submitter);
        $submitter = Submitter::uuid($this->submitter)->first();
        //dd($submitter);
        $file->submitter()->associate($submitter);
        $file->user()->associate(Auth::user());
        $file->created_by()->associate(Auth::user());
        $file->save();
        //dd($file);

        // Tell the list to update
        $this->emit('submitterFileAdded');


    }

    public function render()
    {
        return view('livewire.dashboard.submission-upload');
    }
}