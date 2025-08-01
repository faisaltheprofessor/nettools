<?php

namespace App\Livewire\Ldap;

use App\Ldap\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class NextMailboxPid extends Component
{

    public string $mailBoxPid = '';

    public string $mailboxError = '';


    public function getNextMailboxPid(): void
    {
        $this->reset(['mailBoxPid', 'mailboxError']);

        $lock = Cache::lock('ldap:next-free-mailbox-pid', 10);

        if (! $lock->get()) {
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
                ->map(fn ($uid) => strtolower($uid[0]))
                ->filter(fn ($uid) => preg_match('/^p7\d{4}$/i', $uid))
                ->unique()
                ->sort()
                ->values();

            if ($uids->isEmpty()) {
                throw new Exception('Keine passenden P-IDs gefunden.');
            }

            // Get the last one and increment
            $lastPid = $uids->last();
            $nextPid = 'p'.((int) substr($lastPid, 1) + 1);

            $this->mailBoxPid = $nextPid;
        } catch (Exception $e) {
            $this->mailboxError = $e->getMessage();
        } finally {
            $lock->release();
        }
    }

    public function render()
    {
        return view('livewire.ldap.next-mailbox-pid');
    }
}
