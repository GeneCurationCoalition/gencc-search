<?php

namespace App\Http\Livewire\Dashboard;

use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Illuminate\Support\Str;

class ProcessSubmissions extends Component
{
    public $process = "off";
    public $messages;
    protected $refid;

    protected $listeners = ['actionRunning' => 'running'];


    public function start()
    {
        #$this->process = "running";
        #$this->refid = Str::uuid();
        #$this->messages = Artisan::call('gencc:update-submissions', array('--ref' => $this->refid));
    }
    public function running()
    {
        //dd("asdasd");
        #$this->messages = Artisan::call('gencc:update-submissions');
        #$this->process = "done";
    }

    public function render()
    {
        return view('livewire.dashboard.process-submissions');
    }
}
