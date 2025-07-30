<?php

namespace App\Livewire;

use App\Ldap\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class IdTools extends Component
{

    public string $mailBoxPid = '';

    public string $userPid = '';

    public string $mailboxError = '';

    public string $userError = '';

    public string $freePids = '';

    public string $freePidsError = '';

    public string $lastPidsExportUrl = '';

    public string $lastPidsError = '';

    public int|string $pidCount = '';

    public ?string $allPidsError = null;

    public function getNextMailboxPid()
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

    public function getNextUserPid()
    {
        $this->reset(['userPid', 'userError']);

        $lock = Cache::lock('ldap:next-free-user-pid', 10);

        if (! $lock->get()) {
            $this->userError = 'Diese Funktion wird aktuell von jemand anderem verwendet. Bitte warte einen Moment.';

            return;
        }

        try {
            // Get all users with a non-null UID
            $results = User::where('uid', '!=', null)->limit(25)->get();
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
                $this->userPid = 'p'.($numericPart + 1);
            } else {
                $this->userError = 'keine passenden P-IDs gefunden.';
            }
        } catch (Exception $e) {
            $this->userError = 'Fehler bei der LDAP-Suche: '.$e->getMessage();
        } finally {
            $lock->release();
        }
    }


public function getLastPids()
{
    $this->reset('lastPidsError');

    $lock = Cache::lock('ldap:last-pids-export', 15);

    if (! $lock->get()) {
        $this->lastPidsError = 'Diese Funktion wird aktuell durch jemand anderes verwendet. Bitte warte einen Moment.';
        return;
    }

    try {
        set_time_limit(60);

        $anzahl = $this->pidCount === 'Alle' ? 10000 : (int) $this->pidCount;


        $entries = User::where('uid', '!=', null)->limit(20000)->get('uid');


        $uids = $entries
            ->map(fn ($entry) => $entry->getFirstAttribute('uid'))
            ->filter()
            ->map(fn ($uid) => trim($uid))
            ->implode("\n");

        preg_match_all('/([pP]{1})([012]{1})([0-9]{4})/i', $uids, $matches);

        if (empty($matches[0])) {
            $this->lastPidsError = 'Keine P-IDs durch Regex gefunden.';
            return;
        }

        $pids = $matches[0];
        natcasesort($pids);
        $pids = array_values($pids);
        $lastPids = array_slice($pids, -$anzahl);
        if($anzahl === 0) $anzahl = 'Alle';
        $filename = now()->format('Ymd') . "_Letzte_{$anzahl}_PIDs.txt";

        return response()->streamDownload(function () use ($lastPids) {
            echo implode("\r\n", $lastPids);
        }, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    } catch (\Exception $e) {
        $this->lastPidsError = 'Fehler: ' . $e->getMessage();
    } finally {
        $lock->release();
    }
}


public function getAllPids()
{
    $this->reset('allPidsError');

    $lock = Cache::lock('ldap:all-pids-export', 30);

    if (! $lock->get()) {
        $this->allPidsError = 'Diese Funktion wird aktuell durch jemand anderes verwendet. Bitte warte einen Moment.';
        return;
    }

    try {
        set_time_limit(30);

        $users = \App\Ldap\User::query()
            ->select(['uid', 'fullname', 'surname', 'givenname'])
            ->where('uid', 'starts_with', 'p') // equivalent to p*
            ->limit(10000)
            ->get();

        if ($users->isEmpty()) {
            $this->allPidsError = '☐ Keine P-IDs im LDAP gefunden.';
            return;
        }

        $filename = now()->format('Ymd') . '_Gesamt_P-ID_' . rand(1000, 1000000) . '.csv';

        return response()->streamDownload(function () use ($users) {
            // Open output stream
            $output = fopen('php://output', 'w');

            // Write CSV header
            fputcsv($output, ['P-ID', 'Vollst. Name', 'Nachname', 'Vorname'], ';');

            // Write each row
            foreach ($users as $user) {
                fputcsv($output, [
                    $user->getFirstAttribute('uid'),
                    $user->getFirstAttribute('fullname'),
                    $user->getFirstAttribute('surname'),
                    $user->getFirstAttribute('givenname'),
                ], ';');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    } catch (\Exception $e) {
        $this->allPidsError = 'LDAP Fehler: ' . $e->getMessage();
    } finally {
        $lock->release();
    }
}


    public function render()
    {
        return view('livewire.id-tools');
    }
}
