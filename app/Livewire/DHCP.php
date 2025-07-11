<?php

namespace App\Livewire;

use Livewire\Component;

class DHCP extends Component
{
    public $servers = ['vs002', 'vs003', 'vs004'];
    public function render()
    {
        return view('livewire.dhcp');
    }
}
