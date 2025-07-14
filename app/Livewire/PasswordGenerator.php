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

    public float $entropy = 0.0;
    public string $strengthLabel = '';
    public string $strengthColor = 'bg-red-500';

    private array $wordListCache = [];

    public function mount()
    {
        $this->generatePassword();
    }

    public function generatePassword()
    {
        if (in_array($this->mode, ['read', 'easy', 'hard'])) {
            $this->password = $this->generateReadablePassword();
        } else {
            $this->password = $this->generateDefaultPassword();
        }

        $this->updateEntropy();
    }

    private function updateEntropy(): void
    {
        $charSet = '';

        if ($this->useLowercase) {
            $charSet .= 'abcdefghijkmnopqrstuvwxyz';
        }
        if ($this->useUppercase) {
            $charSet .= 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
        }
        if ($this->useNumbers) {
            $charSet .= '1234567890';
        }
        if ($this->useSymbols) {
            $charSet .= '!@#$%^&*()-_=+[]{};:,.<>?';
        }
        if ($this->useCommonSymbols) {
            $charSet .= '!$%.,';
        }

        $length = strlen($this->password);
        $poolSize = strlen($charSet) ?: 1;

        $this->entropy = round($length * log($poolSize, 2), 2);

        if ($this->entropy < 40) {
            $this->strengthLabel = 'Sehr schwach';
            $this->strengthColor = 'bg-red-500';
        } elseif ($this->entropy < 60) {
            $this->strengthLabel = 'Schwach';
            $this->strengthColor = 'bg-yellow-500';
        } elseif ($this->entropy < 80) {
            $this->strengthLabel = 'Mittel';
            $this->strengthColor = 'bg-blue-500';
        } elseif ($this->entropy < 100) {
            $this->strengthLabel = 'Stark';
            $this->strengthColor = 'bg-green-500';
        } else {
            $this->strengthLabel = 'Sehr stark';
            $this->strengthColor = 'bg-emerald-600';
        }
    }

    private function getWordList(): array
    {
        if (!empty($this->wordListCache)) {
            return $this->wordListCache;
        }

        $filePath = public_path('wordlist/german.txt');
        if (!file_exists($filePath)) {
            return ['Fehler']; // this is fallck word
        }

        $words = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Filter for word length (6–12)
        $words = array_filter($words, fn($w) => strlen($w) >= 6 && strlen($w) <= 12);

        // Cache and return
        $this->wordListCache = array_values($words);

        return $this->wordListCache;
    }

    private function generateReadablePassword(): string
    {
        $words = $this->getWordList();
        $symbols = ['.', '_', '-', '+', '!'];

        if (empty($words)) {
            return 'Fehler123!';
        }

        if ($this->mode === 'easy') {
            return ucfirst($this->randomItem($words)) . $this->randomItem($symbols) . rand(100, 999);
        }

        if ($this->mode === 'hard') {
            $word1 = ucfirst($this->randomItem($words));
            $word2 = ucfirst($this->randomItem($words));
            return "{$word1}{$this->randomItem($symbols)}{$word2}{$this->randomItem($symbols)}" .
                rand(10, 99) . "{$this->randomItem($symbols)}";
        }

        return ucfirst($this->randomItem($words)) . $this->randomItem($symbols) . rand(10, 99);
    }

    private function generateDefaultPassword(): string
    {
        $charPool = '';

        if ($this->useLowercase) {
            $charPool .= 'abcdefghijkmnopqrstuvwxyz';
        }
        if ($this->useUppercase) {
            $charPool .= 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
        }
        if ($this->useNumbers) {
            $charPool .= '1234567890';
        }
        if ($this->useSymbols) {
            $charPool .= '!@#$%^&*()-_=+[]{};:,.<>?';
        }
        if ($this->useCommonSymbols) {
            $charPool .= '!$%.,';
        }

        if (empty($charPool)) {
            return 'Bitte Option wählen';
        }

        $chars = str_split($charPool);
        $repeats = ceil($this->length / count($chars));
        $extendedPool = array_merge(...array_fill(0, $repeats, $chars));
        shuffle($extendedPool);

        return implode('', array_slice($extendedPool, 0, $this->length));
    }

    private function randomItem(array $array)
    {
        return $array[array_rand($array)];
    }

    public function render()
    {
        return view('livewire.password-generator');
    }
}

