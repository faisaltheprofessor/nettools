<?php

namespace App\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public string $quote = '';

    public function mount()
    {
        $this->quote = $this->getRandomQuote();
    }

    public function getRandomQuote()
    {
        $path = 'zitate.txt';
        if (!file_exists('zitate.txt')) {
            return 'Kein Zitat gefunden.';
        }

        $lines = array_filter(array_map('trim', explode("\n", file_get_contents($path))));

        if (empty($lines)) {
            return 'Kein Zitat verfügbar';
        }

        $random = $lines[array_rand($lines)];

        return trim($random);
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
