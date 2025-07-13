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

    public string $mode = 'all'; // possible values: 'easy', 'hard', 'all', 'read'

    public function mount()
    {
        $this->generatePassword();
    }

    public function generatePassword()
    {
        if ($this->mode === 'read' || $this->mode === 'easy' || $this->mode === 'hard') {
            $this->password = $this->generateReadablePassword();
        } else {
            $this->password = $this->generateDefaultPassword();
        }
    }

    private function generateReadablePassword(): string
    {

        $words = [
            'Abendrot', 'Anwaltin', 'Apfelbaum', 'Autobahn', 'Bäckerei', 'Bergwerk', 'Bleistift',
            'Dachboden', 'Einkauf', 'Erfahrung', 'Fahrrad', 'Fenster', 'Feuerwehr', 'Freiheit',
            'Gartenlaube', 'Gesundheit', 'Hauptstadt', 'Kofferraum', 'Landschaft', 'Mädchen',
            'Morgendamm', 'Nachricht', 'Polizei', 'Schlafzimmer', 'Schreibtisch', 'Taschenlampe',
            'Verkehr', 'Wissenschaft', 'Absender', 'Angebote', 'Arbeitszeit', 'Beispiel',
            'Besuch', 'Betrieb', 'Datenbank', 'Drucker', 'Einladung', 'Erlaubnis', 'Fachfrau',
            'Fahrstuhl', 'Festung', 'Führung', 'Gebäude', 'Gedanke', 'Gefahr', 'Geschäft',
            'Gesetz', 'Handlung', 'Heizung', 'Internet', 'Kamera', 'Lektion', 'Lösung',
            'Markt', 'Mittel', 'Nummer', 'Objekt', 'Ordner', 'Papier', 'Quelle', 'Rezept',
            'Schloss', 'Schule', 'Straße', 'Abteilung', 'Aufgabe', 'Ausflug', 'Bildung',
            'Brücke', 'Familie', 'Frucht', 'Gefühl', 'Gericht', 'Geschichte', 'Glaube',
            'Gruppe', 'Hotel', 'Information', 'Küche', 'Lehrer', 'Mutter', 'Nachbar',
            'Presse', 'Reise', 'Schüler', 'Schwester', 'Sendung', 'Tasche', 'Urlaub',
            'Vertrag', 'Wandern', 'Wissen', 'Zahlung', 'Zeichnung', 'Zukunft',
        ];

        $symbols = ['.', '_', '-', '+', '!'];

        if ($this->mode === 'easy') {
            // Word with first capital, one symbol, 3-digit number
            $word = ucfirst($words[array_rand($words)]);
            $symbol = $symbols[array_rand($symbols)];
            $number = rand(100, 999);

            return "{$word}{$symbol}{$number}";
        }

        if ($this->mode === 'hard') {
            // Word + symbol + word + symbol + 2-digit number + symbol
            $word1 = $words[array_rand($words)];
            $word2 = $words[array_rand($words)];
            $symbol1 = $symbols[array_rand($symbols)];
            $symbol2 = $symbols[array_rand($symbols)];
            $symbol3 = $symbols[array_rand($symbols)];
            $number = rand(10, 99);

            // Capitalize only the first word's first letter, keep second word lowercase
            $word1 = ucfirst($word1);
            $word2 = ucfirst($word2);

            return "{$word1}{$symbol1}{$word2}{$symbol2}{$number}{$symbol3}";
        }

        // Fallback: simple word + symbol + number for 'read' or others
        $word = ucfirst($words[array_rand($words)]);
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
