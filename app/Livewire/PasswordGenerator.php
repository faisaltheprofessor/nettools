<?php

namespace App\Livewire;

use Livewire\Component;

class PasswordGenerator extends Component
{
    public string $password = '';
    public int $length = 10;
    public bool $useUppercase = true;
    public bool $useLowercase = true;
    public bool $useNumbers = true;
    public bool $useCommonSymbols = true;
    public bool $useSymbols = false;
    public string $mode = 'all';

    public function mount()
    {
        $this->generatePassword();
    }

    public function generatePassword()
    {
        if ($this->mode === 'read') {
            $this->password = $this->generateReadablePassword();
        } else {
            $this->password = $this->generateDefaultPassword();
        }
    }

    public function toggleSymbols()
    {
        return "toggleSymbols";
    }

    private function generateReadablePassword(): string
    {
        $words = ['Haus', 'Brot', 'Apfel', 'Buch', 'Licht', 'Baum', 'Hund', 'Zug', 'Glas'];
        $symbols = ['.', '_', '-', '+', '!'];
        $word = $words[array_rand($words)];
        $symbol = $symbols[array_rand($symbols)];
        $number = rand(10, 99);

        return "{$word}{$symbol}{$number}";
    }

    private function generateDefaultPassword(): string
    {
        $characters = '';

        if ($this->useLowercase) {
            $characters .= 'abcdefghijkmnopqrstuvwxyz';
        }
        if ($this->useUppercase) {
            $characters .= 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
        }
        if ($this->useNumbers) {
            $characters .= '123456789';
        }
        if ($this->useSymbols) {
            $characters .= '!@#$%^&*()-_=+[]{};:,.<>?';
        }

        if ($this->useCommonSymbols) {
            $characters .= '!$%.,';
        }

        if ($characters === '') {
            return 'Bitte Option wählen';
        }

        return collect(str_split($characters))
            ->shuffle()
            ->take($this->length)
            ->implode('');
    }

    public function render()
    {
        return view('livewire.password-generator');
    }
}
