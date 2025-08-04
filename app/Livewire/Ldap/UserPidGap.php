<?php

namespace App\Livewire\Ldap;

use App\Ldap\User;
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
            $entries = User::limit(100000)->get('uid');
            // 2. Collect UIDs
            $uids = $entries
                ->map(fn ($entry) => $entry->getFirstAttribute('uid'))
                ->filter()
                ->map(fn ($uid) => trim($uid))
                ->toArray();

            // 3. Combine into one string to apply regex like in original code
            $uidText = implode("\n", $uids);

            // 4. Match all valid P-IDs
            preg_match_all('/([pP]{1})([012]{1})([0-9]{4})/i', $uidText, $matches);

            if (! empty($matches[0])) {
                $rawPids = $matches[0];

                // Remove "p" or "P", cast to int
                $numbers = collect($rawPids)
                    ->map(fn ($pid) => (int) substr(strtolower($pid), 1))
                    ->unique()
                    ->sort()
                    ->values();

                // Full range from 1 to max existing PID
                $range = range(1, $numbers->last());

                // Find missing
                $missing = array_values(array_diff($range, $numbers->all()));

                // Filter for >= 10000
                $filtered = array_filter($missing, fn ($n) => $n >= 10000);

                if (empty($filtered)) {
                    $this->error = 'Keine freien P-IDs ab 10000 gefunden.';

                    return;
                }

                // Get last 10 missing values in descending order
                $lastMissing = collect($filtered)
                    ->sortDesc()
                    ->take(10)
                    ->map(fn ($n) => 'p'.$n)
                    ->values();

                $this->pIds = $lastMissing->implode(', ');
                $this->pIdsInTextEditor = nl2br($lastMissing->implode("\n "));
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
