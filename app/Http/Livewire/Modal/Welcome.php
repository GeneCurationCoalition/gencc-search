<?php

namespace App\Http\Livewire\Modal;

use Livewire\Component;


class Welcome extends Component
{

    public $dismiss = false;

    public function dismiss($val = null) {
        $this->dismiss = true;
        session(['modal.welcome.dismiss' => true]);
    }

    public function render()
    {
        return view('livewire.modal.welcome', ['dismiss' => $this->dismiss]);
    }

}
