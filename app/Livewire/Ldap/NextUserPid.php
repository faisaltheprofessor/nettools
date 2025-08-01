<?php

namespace App\Livewire\Ldap;

use Exception;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class NextUserPid extends Component
{
    public $pid;
    public $error;
    public function getNextUserPid(): void
    {
        $this->reset(['pid', 'error']);

        $lock = Cache::lock('ldap:next-free-user-pid', 10);

        if (! $lock->get()) {
            $this->userError = 'Diese Funktion wird aktuell von jemand anderem verwendet. Bitte warte einen Moment.';

            return;
        }

        try {
            // Get all users with a non-null UID
            $results = \App\Ldap\User::where('uid', '!=', null)->limit(25)->get();
            // TODO: Improve query
            $uids = $results->pluck('uid')
                ->filter()
                ->flatten()
                ->map(fn ($uid) => trim($uid))
                ->implode("\n");

            $uids = preg_replace('/^\n+|^[\t\s]*\n+/m', '', $uids);

            preg_match_all('/([pP]{1})([012]{1})([0-9]{4})/i', $uids, $matches);

            if (! empty($matches[0])) {
                $pids = $matches[0];
                natcasesort($pids);
                $lastPid = end($pids);

                $numericPart = (int) substr($lastPid, 1);
                $this->pid = 'p'.($numericPart + 1);
            } else {
                $this->error = 'keine passenden P-IDs gefunden.';
            }
        } catch (Exception $e) {
            $this->error = 'Fehler bei der LDAP-Suche: '.$e->getMessage();
        } finally {
            $lock->release();
        }
    }
    public function render()
    {
        return view('livewire.ldap.next-user-pid');
    }
}
