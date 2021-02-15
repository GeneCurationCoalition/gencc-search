<?php

namespace App\Http\Livewire\Dashboard\Submitter;

use Livewire\Component;

class ManageSubmitterProfile extends Component
{
    public $submitter;
    public $status;

    public function mount($submitter)
    {
        $submitter = $submitter;
    }

    public function render()
    {
        return view('livewire.dashboard.submitter.manage-submitter-profile');
    }
}
