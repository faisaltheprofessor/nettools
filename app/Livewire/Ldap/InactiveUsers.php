<?php

namespace App\Livewire\Ldap;

use App\Ldap\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InactiveUsers extends Component
{
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
                fputs($output, "P-ID;Nachname;Vorname;Stellenzeichen;Kontext;Letzter Login;Fachbereich;\n");

                foreach ($results as $entry) {
                    $uid       = $entry->getFirstAttribute('uid');
                    $surname   = $entry->getFirstAttribute('surname') ?? '';
                    $givenname = $entry->getFirstAttribute('givenname') ?? '';
                    $title     = $entry->getFirstAttribute('title') ?? '';
                    $acls      = $entry->getAttribute('acl') ?? [];
                    $logintime = $entry->getFirstAttribute('logintime');

                    if (! $uid || ! is_array($acls) || ! $logintime) {
                        continue;
                    }

                    // Skip user if any ACL contains "DeaktivierteUser"
                    $isDeactivated = collect($acls)->contains(fn ($acl) => str_contains($acl, 'DeaktivierteUser'));
                    if ($isDeactivated) {
                        continue;
                    }

                    // Parse login time
                    $parsed = substr_replace($logintime, '-', 4, 0);
                    $parsed = substr_replace($parsed, '-', 7, 0);
                    $parsed = substr($parsed, 0, 10);
                    $timestamp = strtotime($parsed);
                    $lastLogin = date('d.m.Y', $timestamp);
                    $sixMonthsAgo = strtotime('-6 months');

                    if ($timestamp > $sixMonthsAgo) {
                        continue;
                    }

                    // Clean first ACL for export (or leave blank)
                    $rawAcl = $acls[0] ?? '';
                    $cleaned = str_replace([
                        "6#entry#cn=$uid,", "2#subtree#cn=", "6#entry#cn=",
                        "#[All Attributes Rights]", "#loginScript",
                        ",ou=", "ou=", ",o=", "$uid."
                    ], '', $rawAcl);
                    $cleaned = trim($cleaned);

                    // Output single CSV row
                    fputs($output, "$uid;$surname;$givenname;$title;$cleaned;$lastLogin;;\n");
                }

                fclose($output);
            }, time() . '_P-ID-180-Tage-alt.csv');

        } catch (\Exception $e) {
            Log::error('LDAP Inactive Export Error: ' . $e->getMessage());
            abort(500, 'Fehler beim Erstellen des Exports.');
        } finally {
            $lock->release();
        }
    }

    public function render()
    {
        return view('livewire.ldap.inactive-users');
    }
}
