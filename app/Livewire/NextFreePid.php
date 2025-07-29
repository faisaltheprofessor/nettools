<?php

namespace App\Livewire;

use App\Ldap\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class NextFreePid extends Component
{
    public string $mailBoxPid = '';
    public string $userPid = '';
    public string $mailboxError = '';
    public string $userError = '';

    public function getNextMailboxPid()
    {
        $this->reset(['mailBoxPid', 'mailboxError']);

        $lock = Cache::lock('ldap:next-free-mailbox-pid', 10);

        if (!$lock->get()) {
            $this->mailboxError = 'Diese Funktion wird aktuell von jemand anderem verwendet. Bitte warte einen Moment.';
            return;
        }

        try {
            // TODO: Imrove query
            $uids = User::query()
                ->whereStartsWith('uid', 'p7')
                ->get()
                ->pluck('uid')
                ->filter()
                ->map(fn($uid) => strtolower($uid[0]))
                ->filter(fn($uid) => preg_match('/^p7\d{4}$/i', $uid))
                ->unique()
                ->sort()
                ->values();

            if ($uids->isEmpty()) {
                throw new Exception('Keine passenden P-IDs gefunden.');
            }

            // Get the last one and increment
            $lastPid = $uids->last();
            $nextPid = 'p' . ((int)substr($lastPid, 1) + 1);

            $this->mailBoxPid = $nextPid;
        } catch (Exception $e) {
            $this->mailboxError = $e->getMessage();
        } finally {
            $lock->release();
        }
    }


    public function getNextUserPid()
    {
        $this->reset(['userPid', 'userError']);

        $lock = Cache::lock('ldap:next-free-user-pid', 10);

        if (!$lock->get()) {
            $this->userError = 'Diese Funktion wird aktuell von jemand anderem verwendet. Bitte warte einen Moment.';
            return;
        }

        try {
            // Get all users with a non-null UID
            $results = User::where('uid', '!=', null)->limit(100)->get();
            // TODO: Improve query
            $uids = $results->pluck('uid')
                ->filter()
                ->flatten()
                ->map(fn($uid) => trim($uid))
                ->implode("\n");

            $uids = preg_replace('/^\n+|^[\t\s]*\n+/m', '', $uids);

            preg_match_all("/([pP]{1})([012]{1})([0-9]{4})/i", $uids, $matches);

            if (!empty($matches[0])) {
                $pids = $matches[0];
                natcasesort($pids);
                $lastPid = end($pids);

                $numericPart = (int)substr($lastPid, 1);
                $this->userPid = 'p' . ($numericPart + 1);
            } else {
                $this->userError = 'Kein Treffer – es wurden keine passenden P-IDs gefunden.';
            }
        } catch (Exception $e) {
            $this->userError = 'Fehler bei der LDAP-Suche: ' . $e->getMessage();
        } finally {
            $lock->release();
        }
    }

    public function render()
    {
        return view('livewire.next-free-pid');
    }
}

