<?php

namespace App\Livewire;

use Livewire\Component;

class PasswordGenerator extends Component
{
       public string $password = '';
    public int $length = 13;
    public bool $useUppercase = true;
    public bool $useLowercase = true;
    public bool $useNumbers = true;
    public bool $useSymbols = true;
    public string $mode = 'all'; // 'say', 'read', 'all'

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

    private function generateReadablePassword(): string
    {
        $words = ['Haus', 'Brot', 'Apfel', 'Buch', 'Licht', 'Baum', 'Hund', 'Zug', 'Glas'];
        $symbols = ['.', '_', '-', '+'];
        $word = $words[array_rand($words)];
        $symbol = $symbols[array_rand($symbols)];
        $number = rand(10, 99);

        return "{$word}{$symbol}{$number}";
    }

    private function generateDefaultPassword(): string
    {
        $characters = '';

        if ($this->useLowercase) {
            $characters .= 'abcdefghijklmnopqrstuvwxyz';
        }
        if ($this->useUppercase) {
            $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        if ($this->useNumbers) {
            $characters .= '0123456789';
        }
        if ($this->useSymbols) {
            $characters .= '!@#$%^&*()-_=+[]{};:,.<>?';
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
