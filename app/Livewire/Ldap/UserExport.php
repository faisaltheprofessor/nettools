<?php

namespace App\Livewire\Ldap;

use App\Ldap\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserExport extends Component
{
    public string|int $pidCount = 20;

    public string $exportMode = 'table'; // 'txt', 'csv', 'table'

    public array $selectedFields = []; // LDAP attribute names

    public ?string $error = null;

    public array $exportOutput = [];

    public array $fieldDisplayNames = [
        'givenname' => 'Vorname',
        'surname' => 'Nachname',
        'fullname' => 'Vollständiger Name',
        'logintime' => 'Letzter Login',
        'mail' => 'E-Mail',
        'dn' => 'Kontext',
    ];

    public function exportPids()
    {
        $this->reset(['error', 'exportOutput']);

        if ($this->exportMode === '') {
            $this->error = 'Export-Modus erforderlich';

            return;
        }

        $lock = Cache::lock('ldap:pids-export', 30);

        if (! $lock->get()) {
            $this->error = 'Diese Funktion wird aktuell durch jemand anderes verwendet. Bitte warte einen Moment.';

            return;
        }

        try {
            set_time_limit(60);

            $anzahl = $this->pidCount === 'Alle' ? 0 : (int) $this->pidCount;

            $orderedFields = array_filter(array_keys($this->fieldDisplayNames), fn ($field) => in_array($field, $this->selectedFields));
            $fieldsToSelect = array_unique(array_merge(['uid'], array_diff($orderedFields, ['dn'])));

            $rawEntries = \App\Ldap\User::query()
                ->select($fieldsToSelect)
                ->where('uid', 'starts_with', 'p')
                ->orderByDesc('uid')
                ->limit(10000)
                ->get();

            if ($rawEntries->isEmpty()) {
                $this->error = 'Keine P-IDs im LDAP gefunden.';

                return;
            }

            $filteredEntries = $rawEntries->filter(function ($entry) {
                return preg_match('/^p[012][0-9]{4}$/i', $entry->getFirstAttribute('uid'));
            });

            if ($filteredEntries->isEmpty()) {
                $this->error = 'Keine gültigen P-IDs im LDAP gefunden.';

                return;
            }

            $selectedEntries = $anzahl > 0
                ? $filteredEntries->take($anzahl)
                : $filteredEntries;

            $filenameDate = now()->format('Ymd');
            $filenameCount = $anzahl > 0 ? $anzahl : 'Alle';

            // TABLE
            if ($this->exportMode === 'table') {
                $this->exportOutput = $selectedEntries->map(function ($entry) use ($orderedFields) {
                    $row = ['uid' => $entry->getFirstAttribute('uid')];

                    foreach ($orderedFields as $field) {
                        if ($field === 'dn') {
                            try {
                                $value = $entry->getContext();
                            } catch (\Exception $e) {
                                $value = '';
                            }
                        } else {
                            $value = $entry->getFirstAttribute($field);

                            if ($field === 'logintime' && $value) {
                                try {
                                    $value = Carbon::parse($value)->format('d.m.Y');
                                } catch (\Exception $e) {
                                    // fallback
                                }
                            }
                        }

                        $row[$field] = $value ?? '';
                    }

                    return $row;
                })->toArray();

                $this->exportOutput = array_reverse($this->exportOutput);

                return;
            }

            // TXT
            if ($this->exportMode === 'txt') {
                $lines = $selectedEntries->map(function ($entry) use ($orderedFields) {
                    $uid = $entry->getFirstAttribute('uid');
                    $extras = [];

                    foreach ($orderedFields as $field) {
                        if ($field === 'dn') {
                            try {
                                $value = $entry->getContext();
                            } catch (\Exception $e) {
                                $value = '';
                            }
                        } else {
                            $value = $entry->getFirstAttribute($field);

                            if ($field === 'logintime' && $value) {
                                try {
                                    $value = Carbon::parse($value)->format('d.m.Y');
                                } catch (\Exception $e) {
                                    // fallback
                                }
                            }
                        }

                        if ($value) {
                            $label = $this->fieldDisplayNames[$field] ?? $field;
                            $extras[] = "{$label}: {$value}";
                        }
                    }

                    return $uid.(! empty($extras) ? ' - '.implode(' | ', $extras) : '');
                })->toArray();

                $lines = array_reverse($lines);
                $filename = "{$filenameDate}_PIDs_{$filenameCount}.txt";

                return response()->streamDownload(function () use ($lines) {
                    echo implode("\r\n", $lines);
                }, $filename, [
                    'Content-Type' => 'text/plain',
                ]);
            }

            // CSV
            if ($this->exportMode === 'csv') {
                $filename = "{$filenameDate}_PIDs_{$filenameCount}.csv";

                return response()->streamDownload(function () use ($selectedEntries, $orderedFields) {
                    $output = fopen('php://output', 'w');

                    $headerLabels = array_merge(['P-ID'], array_map(
                        fn ($field) => $this->fieldDisplayNames[$field] ?? $field,
                        $orderedFields
                    ));

                    fputcsv($output, $headerLabels, ';');

                    foreach ($selectedEntries->reverse() as $entry) {
                        $row = [$entry->getFirstAttribute('uid')];

                        foreach ($orderedFields as $field) {
                            if ($field === 'dn') {
                                try {
                                    $value = $entry->getContext();
                                } catch (\Exception $e) {
                                    $value = '';
                                }
                            } else {
                                $value = $entry->getFirstAttribute($field);

                                if ($field === 'logintime' && $value) {
                                    try {
                                        $value = Carbon::parse($value)->format('d.m.Y');
                                    } catch (\Exception $e) {
                                        // fallback
                                    }
                                }
                            }

                            $row[] = is_scalar($value) ? $value : (is_array($value) ? implode(', ', $value) : '');
                        }

                        fputcsv($output, $row, ';');
                    }

                    fclose($output);
                }, $filename, [
                    'Content-Type' => 'text/csv',
                ]);
            }

            $this->error = 'Unbekannter Export-Modus.';
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $lock->release();
        }
    }

    public function getInactiveUsers(): StreamedResponse
    {
        $lock = Cache::lock('ldap:inactive-users-export', 30);

        if (! $lock->get()) {
            abort(429, 'Diese Funktion wird aktuell verwendet. Bitte versuche es gleich nochmal.');
        }

        try {
            $attributes = ['uid', 'surname', 'givenname', 'title', 'acl', 'logintime'];
            $results = User::startingWithP1()->limit(100000)->get($attributes);

            if ($results->isEmpty()) {
                abort(404, 'Keine passenden Benutzer gefunden.');
            }

            return response()->streamDownload(function () use ($results) {
                $output = fopen('php://output', 'w');
                fwrite($output, "P-ID;Nachname;Vorname;Stellenzeichen;Kontext;Letzter Login;Zuletzt Aktiv;\n");

                foreach ($results as $entry) {
                    $uid = $entry->getFirstAttribute('uid');
                    $surname = $entry->getFirstAttribute('surname') ?? '';
                    $givenname = $entry->getFirstAttribute('givenname') ?? '';
                    $title = $entry->getFirstAttribute('title') ?? '';
                    $acls = $entry->getAttribute('acl') ?? [];
                    $context = $entry->getContext();

                    $logintimeRaw = $entry->getFirstAttribute('logintime');
                    $logintime = $logintimeRaw ? Carbon::parse($logintimeRaw) : null;

                    if (! $uid || ! is_array($acls) || ! $logintime) {
                        continue;
                    }

                    $isDeactivated = collect($acls)->contains(fn ($acl) => str_contains($acl, 'DeaktivierteUser'));
                    if ($isDeactivated) {
                        continue;
                    }

                    if ($logintime->greaterThan(Carbon::now()->subMonths(6))) {
                        continue;
                    }

                    $lastLogin = $logintime->format('d.m.Y');
                    $lastLoginHumanReadable = $logintime->diffForHumans();

                    fwrite($output, "$uid;$surname;$givenname;$title;$context;$lastLogin;$lastLoginHumanReadable;\n");
                }

                fclose($output);
            }, time().'_P-ID-180-Tage-alt.csv');

        } catch (\Exception $e) {
            Log::error('LDAP Inactive Export Error: '.$e->getMessage());
            abort(500, 'Fehler beim Erstellen des Exports.');
        } finally {
            $lock->release();
        }
    }

    public function render()
    {
        return view('livewire.ldap.user-export', [
            'fieldDisplayNames' => $this->fieldDisplayNames,
        ]);
    }
}
