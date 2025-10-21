<?php

namespace App\Livewire\Ldap;

use Livewire\Component;
use App\Ldap\User;
use Illuminate\Validation\ValidationException;

class LdapRaw extends Component
{
    public array $result = [];
    public string $pkennung = '';
    public string $errorMessage = '';

    public function render()
    {
        return view('livewire.ldap.ldap-raw');
    }

    public function getLdapRaw()
    {
        $this->reset(['result', 'errorMessage']);

        try {
            $this->validate([
                'pkennung' => ['required', 'regex:/^\s*p?\s*\d+\s*$/i'],
            ]);

            $normalized = preg_replace('/\s+/', '', $this->pkennung);
            $digits = ltrim($normalized, 'pP');
            $pid = 'p' . $digits;

            $user = User::where('cn', '=', $pid)->first();

            if (!$user) {
                $this->errorMessage = "Kein Benutzer mit der Kennung '{$pid}' gefunden.";
                return;
            }

            $data = method_exists($user, 'toArray')
                ? $user->toArray()
                : (array) $user->getAttributes();

            $this->result = collect($data)
                ->reject(fn($value, $key) => str_starts_with(strtolower($key), 'saslogin'))
                ->toArray();
        } catch (ValidationException $e) {
            $this->errorMessage = 'Die eingegebene Kennung ist ungültig.';
            throw $e;
        } catch (\Throwable $e) {
            $this->errorMessage = 'Beim Abrufen der LDAP-Daten ist ein Fehler aufgetreten: ' . $e->getMessage();
        }
    }
}
