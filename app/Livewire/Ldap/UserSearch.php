<?php

namespace App\Livewire\Ldap;

use App\Ldap\User;
use Livewire\Component;

class UserSearch extends Component
{
    public $searchAttribute = 'PID';
    public $searchTerm = '';

    public $searchResults;
    public $error = null;

    public $selectedUserGroups = null; // holds groups of clicked user

    public function search()
    {
        $this->reset(['error', 'searchResults', 'selectedUserGroups']);

        if (trim($this->searchTerm) === '') {
            $this->error = 'Bitte geben Sie einen Suchbegriff ein.';
            return;
        }

        try {
            $attributeMap = [
                'PID' => 'uid',
                'Nachname' => 'sn',
                'Vollst. Name' => 'cn',
            ];

            $ldapAttribute = $attributeMap[$this->searchAttribute] ?? 'uid';

            // Convert user-friendly wildcard to LDAP syntax
            $pattern = str_replace(['*', '?'], ['*', '?'], $this->searchTerm);

            // Sanitize filter
            $ldapFilter = sprintf('(%s=%s)', $ldapAttribute, $pattern);

            $query = User::query();
            $query->rawFilter($ldapFilter);

            $users = $query->limit(100)->get();

            if ($users->isEmpty()) {
                $this->error = 'Keine Benutzer gefunden.';
                return;
            }

            $results = collect();

            foreach ($users as $user) {
                $results->push([
                    'pid' => $user->getFirstAttribute('uid'),
                    'fullname' => $user->getFirstAttribute('cn') ?? '',
                    'surname' => $user->getFirstAttribute('sn') ?? '',
                    'givenname' => $user->getFirstAttribute('givenname') ?? '',
                    'emails' => $user->getAttribute('mail') ?? [],
                ]);
            }

            $this->searchResults = $results;

        } catch (\Exception $e) {
            $this->error = 'Fehler bei der Suche: ' . $e->getMessage();
        }
    }


    public function loadGroups(string $pid)
    {
        try {
            $user = User::query()->where('uid', '=', $pid)->first();

            if (!$user) {
                $this->selectedUserGroups = [];
                return;
            }

            $rawGroups = $user->getAttribute('groupmembership') ?? [];

            $this->selectedUserGroups = $this->formatGroups(is_array($rawGroups) ? $rawGroups : []);
            $this->modal('groups')->show();

        } catch (\Exception $e) {
            $this->selectedUserGroups = [];
            $this->error = 'Fehler beim Laden der Gruppen: ' . $e->getMessage();
        }
    }

    private function formatGroups(array $groups): array
    {
        $cleanGroups = [];

        foreach ($groups as $group) {
            $g = str_replace(
                [",o=ba", "cn=", ",ou=", ",o=", ","],
                [".ba", "", ".", ".", "."],
                $group
            );

            $cleanGroups[] = $g;
        }

        $cleanGroups = array_unique($cleanGroups);
        sort($cleanGroups, SORT_FLAG_CASE | SORT_STRING);

        return $cleanGroups;
    }

    public function render()
    {
        return view('livewire.ldap.user-search');
    }
}
