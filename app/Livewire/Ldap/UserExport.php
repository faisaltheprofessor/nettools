<?php

namespace App\Livewire\Ldap;

use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class UserExport extends Component
{

    public string|int $pidCount = 20;
    public string $exportMode = 'view'; // 'txt', 'csv', 'view'
    public bool $includeNames = false;
    public ?string $error = null;
    public $exportOutput = null;


    public function exportPids()
    {
        $this->reset(['error', 'exportOutput']);

        $lock = Cache::lock('ldap:pids-export', 30);

        if (! $lock->get()) {
            $this->error = 'Diese Funktion wird aktuell durch jemand anderes verwendet. Bitte warte einen Moment.';
            return;
        }

        try {
            set_time_limit(60);

            $anzahl = $this->pidCount === 'Alle' ? 0 : (int) $this->pidCount;

            $entries = \App\Ldap\User::query()
                ->select(['uid', 'fullname', 'surname', 'givenname'])
                ->where('uid', 'starts_with', 'p')
                ->limit(20000)
                ->get();

            if ($entries->isEmpty()) {
                $this->error = '☐ Keine P-IDs im LDAP gefunden.';
                return;
            }

            $uids = $entries
                ->map(fn ($entry) => trim($entry->getFirstAttribute('uid')))
                ->filter();

            preg_match_all('/([pP]{1})([012]{1})([0-9]{4})/i', $uids->implode("\n"), $matches);

            if (empty($matches[0])) {
                $this->error = 'Keine gültigen P-IDs durch Regex gefunden.';
                return;
            }

            $pids = $matches[0];
            natcasesort($pids);
            $pids = array_values($pids);
            $selectedPids = $anzahl > 0 ? array_slice($pids, -$anzahl) : $pids;

            $filenameDate = now()->format('Ymd');
            $filenameCount = $anzahl > 0 ? $anzahl : 'Alle';

            if ($this->exportMode === 'view') {
                $lines = [];

                foreach ($entries as $entry) {
                    $uid = trim($entry->getFirstAttribute('uid'));

                    if (in_array($uid, $selectedPids)) {
                        if ($this->includeNames) {
                            $lines[] = sprintf(
                                '%s - %s %s (%s)',
                                $uid,
                                $entry->getFirstAttribute('givenname'),
                                $entry->getFirstAttribute('surname'),
                                $entry->getFirstAttribute('fullname')
                            );
                        } else {
                            $lines[] = $uid;
                        }
                    }
                }

                $this->exportOutput = nl2br(implode("\n", $lines));
                return;
            }

            if ($this->exportMode === 'txt') {
                $lines = [];

                foreach ($entries as $entry) {
                    $uid = trim($entry->getFirstAttribute('uid'));

                    if (in_array($uid, $selectedPids)) {
                        if ($this->includeNames) {
                            $lines[] = sprintf(
                                '%s - %s %s (%s)',
                                $uid,
                                $entry->getFirstAttribute('givenname'),
                                $entry->getFirstAttribute('surname'),
                                $entry->getFirstAttribute('fullname')
                            );
                        } else {
                            $lines[] = $uid;
                        }
                    }
                }

                $filename = "{$filenameDate}_PIDs_{$filenameCount}.txt";

                return response()->streamDownload(function () use ($lines) {
                    echo implode("\r\n", $lines);
                }, $filename, [
                    'Content-Type' => 'text/plain',
                ]);
            }

            if ($this->exportMode === 'csv') {
                $filename = "{$filenameDate}_PIDs_{$filenameCount}.csv";

                return response()->streamDownload(function () use ($selectedPids, $entries) {
                    $output = fopen('php://output', 'w');

                    if ($this->includeNames) {
                        fputcsv($output, ['P-ID', 'Vollst. Name', 'Nachname', 'Vorname'], ';');
                    } else {
                        fputcsv($output, ['P-ID'], ';');
                    }

                    foreach ($entries as $entry) {
                        $uid = trim($entry->getFirstAttribute('uid'));

                        if (in_array($uid, $selectedPids)) {
                            if ($this->includeNames) {
                                fputcsv($output, [
                                    $uid,
                                    $entry->getFirstAttribute('fullname'),
                                    $entry->getFirstAttribute('surname'),
                                    $entry->getFirstAttribute('givenname'),
                                ], ';');
                            } else {
                                fputcsv($output, [$uid], ';');
                            }
                        }
                    }

                    fclose($output);
                }, $filename, [
                    'Content-Type' => 'text/csv',
                ]);
            }

            $this->error = 'Unbekannter Export-Modus.';
        } catch (\Exception $e) {
            $this->error = 'Fehler: ' . $e->getMessage();
        } finally {
            $lock->release();
        }
    }

    public function render()
    {
        return view('livewire.ldap.user-export');
    }
}
