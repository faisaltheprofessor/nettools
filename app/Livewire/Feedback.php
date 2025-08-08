<?php

namespace App\Livewire;

use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class Feedback extends Component
{

    #[On('show-modal')]
    public function showModal($title): void
    {
        Flux::modal($title)->show();
    }

    public function render()
    {
        return view('livewire.feedback');
    }
}
