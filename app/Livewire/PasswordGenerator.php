<?php

namespace App\Livewire;

use Livewire\Component;

class PasswordGenerator extends Component
{
    public $strengthPercent = 70;
    public function render()
    {
        return view('livewire.password-generator');
    }
}
