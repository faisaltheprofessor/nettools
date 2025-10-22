<?php

namespace App\Livewire\Ldap;

use App\Ldap\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class NextUserPid extends Component
{
    public ?string $pid = null;

    public ?string $error = null;

    public function getNextUserPid(): void
    {
        $this->reset(['pid', 'error']);

        $lock = Cache::lock('ldap:next-free-user-pid', 10);
        if (! $lock->get()) {
            $this->error = 'Diese Funktion wird aktuell von jemand anderem verwendet. Bitte warte einen Moment.';

            return;
        }

        try {
            $uids = User::query()
                ->whereStartsWith('uid', 'p')
                ->select(['uid'])
                ->get()
                ->pluck('uid')
                ->flatten()
                ->filter(fn ($v) => is_string($v) && $v !== '')
                ->map(fn ($v) => trim($v));

            $numbers = $uids->map(function ($u) {
                return preg_match('/^[pP]([012]\d{4})$/', $u, $m) ? (int) $m[1] : null;
            })->filter();

            if ($numbers->isEmpty()) {
                $this->error = 'keine passenden P-IDs gefunden.';

                return;
            }

            $next = $numbers->max() + 1;
            $candidate = 'p'.$next;

            while (
                User::query()->whereEquals('uid', $candidate)->exists()
                || User::query()->whereEquals('cn', $candidate)->exists()
            ) {
                $next++;
                $candidate = 'p'.$next;
            }

            $this->pid = $candidate;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            optional($lock)->release();
        }
    }

    public function render()
    {
        return view('livewire.ldap.next-user-pid');
    }
}
