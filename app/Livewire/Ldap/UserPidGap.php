<?php

namespace App\Livewire\Ldap;

use App\Ldap\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
            $entries = User::limit(100000)->get('uid');

            $uids = $entries
                ->map(fn ($entry) => $entry->getFirstAttribute('uid'))
                ->filter()
                ->map(fn ($uid) => trim($uid))
                ->toArray();

            $uidText = implode("\n", $uids);

            preg_match_all('/([pP]{1})([012]{1})([0-9]{4})/i', $uidText, $matches);

            if (! empty($matches[0])) {
                $rawPids = $matches[0];

                $numbers = collect($rawPids)
                    ->map(fn ($pid) => (int) substr(strtolower($pid), 1))
                    ->unique()
                    ->sort()
                    ->values();

                $range = range(1, $numbers->last());

                $missing = array_values(array_diff($range, $numbers->all()));

                $filtered = array_filter($missing, fn ($n) => $n >= 10000);

                if (empty($filtered)) {
                    $this->error = 'Keine freien P-IDs ab 10000 gefunden.';

                    return;
                }

                $lastMissing = collect($filtered)
                    ->sortDesc()
                    ->take(10)
                    ->map(fn ($n) => 'p'.$n)
                    ->values();

                // Implode to multiline string
                $rawText = $lastMissing->implode("\n");

                // Trim whitespace from EACH line
                $lines = explode("\n", $rawText);
                $trimmedLines = array_map(fn ($line) => trim($line), $lines);

                $this->pIdsInTextEditor = implode("\n", $trimmedLines);

                $this->pIds = $lastMissing->implode(', ');
            } else {
                $this->error = 'Keine passenden P-IDs gefunden.';
            }

        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('LDAP P-ID Gap Error: '.$e->getMessage());
        } finally {
            $lock->release();
        }
    }

    public function render()
    {
        return view('livewire.ldap.user-pid-gap');
    }
}
