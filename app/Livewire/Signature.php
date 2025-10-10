<?php

namespace App\Livewire;

use App\Ldap\User;
use Illuminate\Support\Str;
use Livewire\Component;

class Signature extends Component
{
    public string $pkennung = '';

    public string $signatureContent = '';

    protected ?User $ldapUser = null;

    public function generate()
    {
        $this->resetErrorBag();

        // Accept "12345", "p12345" or "P12345"
        $this->validate([
            'pkennung' => ['required', 'regex:/^\s*p?\s*\d+\s*$/i'],
        ]);

        // Normalize to 'p' + digits
        $normalized = preg_replace('/\s+/', '', $this->pkennung); // remove spaces
        $digits = ltrim($normalized, 'pP');                       // drop any leading p/P
        $pid = 'p' . $digits;

        $user = User::where('cn', '=', $pid)->first();

        if (! $user) {
            $this->addError('pkennung', 'Benutzer nicht gefunden.');
            $this->ldapUser = null;
            $this->signatureContent = '';
            return;
        }

        $this->ldapUser = $user;

        // Build HTML content with paragraphs for the editor
        $lines = array_filter([
            'Freundliche Grüße',
            'Im Auftrag',
            (Str::title($user->givenName[0] ?? '')).' '.(Str::title($user->sn[0] ?? '')),
            $user->company[0] ?? '',
            $user->description[0] ?? '',
            $user->title[0] ?? '',
            'Post: '.($user->physicalDeliveryOfficeName[0] ?? ''),
            'Telefon: +49 30 90295-'.($user->telephonenumber[0] ?? ''),
            'Fax: +49 30 90295-'.($user->facsimiletelephonenumber[0] ?? ''),
            substr($user->emailAddress[0] ?? '', 2),
            'Web: '.config('app.signature_website'),
        ]);

        $htmlContent = '';
        foreach ($lines as $line) {
            $htmlContent .= '<p>'.e($line).'</p>';
        }

        $this->signatureContent = $htmlContent;
    }

    public function render()
    {
        return view('livewire.signature');
    }
}
