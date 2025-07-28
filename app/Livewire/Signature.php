<?php

namespace App\Livewire;

use Livewire\Component;
use App\Ldap\User;

class Signature extends Component
{
    public string $pkennung = '';
    public string $signatureContent = '';
    protected ?User $ldapUser = null;

    public function generate()
    {
        $this->resetErrorBag();

        $this->validate([
            'pkennung' => 'required|numeric',
        ]);

        $pid = 'p' . ltrim($this->pkennung, 'pP');
        $user = User::where('cn', '=', $pid)->first();

        if (!$user) {
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
            ($user->givenName[0] ?? '') . ' ' . ($user->sn[0] ?? ''),
            $user->company[0] ?? '',
            $user->description[0] ?? '',
            $user->title[0] ?? '',
            'Post: ' . ($user->physicalDeliveryOfficeName[0] ?? ''),
            'Telefon: +49 30 90295-' . ($user->telephonenumber[0] ?? ''),
            'Fax: +49 30 90295-' . ($user->facsimiletelephonenumber[0] ?? ''),
            substr($user->emailAddress[0] ?? '', 2),
            'Web:',
        ]);

        $htmlContent = '';

        foreach ($lines as $line) {
            $htmlContent .= '<p>' . e($line) . '</p>';
        }

        $this->signatureContent = $htmlContent;
    }

    public function render()
    {
        return view('livewire.signature');
    }
}

