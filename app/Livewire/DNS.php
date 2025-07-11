<?php

namespace App\Livewire;

use Livewire\Component;

class DNS extends Component
{
    public $servers = ["vs002", "vs003", "vs004"];
    public function render()
    {
        return view('livewire.d-n-s');
    }
}
