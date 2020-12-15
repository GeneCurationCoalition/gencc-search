<?php

namespace App\Http\Livewire\Dashboard;

use App\Submitter;
use Livewire\Component;

class ToggleSubmitterStatus extends Component

{

    public $submitter;
    public $status;

    public function mount($submitter)
    {
        $submitter = $submitter;
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
        return view('livewire.dashboard.toggle-submitter-status', ['submitter' => $this->submitter]);
    }
}
