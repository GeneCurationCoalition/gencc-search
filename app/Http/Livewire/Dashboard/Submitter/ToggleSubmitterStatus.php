<?php

namespace App\Http\Livewire\Dashboard\Submitter;

use App\Submitter;
use Livewire\Component;

class ToggleSubmitterStatus extends Component

{

    public $submitter;
    public $status;

    public function mount($submitter)
    {
        $this->submitter = $submitter;
        $this->status = $submitter->status;
    }

    public function updatedStatus($value)
    {
        // Convert radio button value to integer (1 or 0)
        $this->status = $value ? 1 : 0;

        // Save the status to the database
        $submitter = Submitter::uuid($this->submitter->uuid)->first();
        $submitter->status = $this->status;
        $submitter->save();

        // Update the local submitter instance
        $this->submitter = $submitter;
    }

    public function enable($uuid)
    {
        $submitter = Submitter::uuid($uuid)->first();
        $submitter->status = 1;
        //dd($submitter);
        $submitter->save();

        $this->submitter = $submitter;
        //dd($this->submitter);
    }

    public function disable($uuid)
    {
        //dd("disable");
        $submitter = Submitter::uuid($uuid)->first();
        $submitter->status = 0;
        $submitter->save();

        $this->submitter = $submitter;
        //dd($this->submitter);
    }

    public function render()
    {
        return view('livewire.dashboard.submitter.toggle-submitter-status', ['submitter' => $this->submitter]);
    }
}
