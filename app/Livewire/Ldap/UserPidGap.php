<?php

namespace App\Livewire\Ldap;

use App\Ldap\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class UserPidGap extends Component
{

    public string $pIds = '';

    public string $error = '';
    public string $pIdsInTextEditor = '';


    public function getUserIdGap(): void
    {
        $this->reset(['pIds', 'error', 'pIdsInTextEditor']);

        $lock = Cache::lock('ldap:free-user-pids', 15);

        if (! $lock->get()) {
            $this->error = 'Diese Funktion wird aktuell von jemand anderem verwendet. Bitte warte einen Moment.';
            return;
        }

        try {
            // Step 1: Fetch all UIDs using LDAP Record

            $entries = User::where('uid', '!=', null)->limit(10000)->get('uid');


            $uids = $entries
                ->map(fn ($entry) => $entry->getFirstAttribute('uid'))
                ->filter()
                ->map(fn ($uid) => trim($uid))
                ->implode("\n");

            // Step 2: Extract all valid P-IDs using regex
            preg_match_all('/([pP]{1})([012]{1})([0-9]{4})/i', $uids, $matches);

            if (! empty($matches[0])) {
                $rawPids = $matches[0];
                $numeric = collect($rawPids)
                    ->map(fn ($pid) => (int) substr(strtolower($pid), 1))
                    ->unique()
                    ->sort()
                    ->values();

                $max = $numeric->last() ?? 10000;
                $range = range(1, $max);
                $missing = array_values(array_diff($range, $numeric->all()));

                $free = collect($missing)
                    ->filter(fn ($n) => $n >= 10000)
                    ->sortDesc()
                    ->take(10)
                    ->map(fn ($n) => 'p' . $n)
                    ->values();

                if ($free->isEmpty()) {
                    $this->error = 'Keine freien P-IDs ab 10000 gefunden.';
                } else {
                    $this->pIds = implode(", ", $free->all());
                    $this->pIdsInTextEditor = nl2br(implode("\n ", $free->all()));
                }
            } else {
                $this->error = 'Keine passenden P-IDs gefunden.';
            }
        } catch (\Exception $e) {
            $this->error = 'Fehler bei der LDAP-Abfrage: ' . $e->getMessage();
        } finally {
            $lock->release();
        }
    }

    public function render()
    {
        return view('livewire.ldap.user-pid-gap');
    }
}
