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

    public int|string $pidCount = 20;

    public ?string $allPidsError = null;






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
