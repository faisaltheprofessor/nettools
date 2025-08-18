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

    public $selectedUserGroups = null;

    public $selectedUserInfo = [];

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
                'Vollst. Name' => 'fullname',
            ];

            $ldapAttribute = $attributeMap[$this->searchAttribute] ?? 'uid';

            $term = trim($this->searchTerm);

            // 🔑 Normalize PID input
            if ($this->searchAttribute === 'PID') {
                $term = strtolower($term);
                if (!str_starts_with($term, 'p')) {
                    $term = 'p' . $term;
                }
            }

            // Convert user-friendly wildcard to LDAP syntax
            $pattern = str_replace(['*', '?'], ['*', '?'], $term);

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
                    'email' => $user->getFirstAttribute('mail') ?? '',
                    'external_email' => $user->getFirstAttribute('BAPK-mailext') ?? '',
                ]);
            }

            $this->searchResults = $results;

        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }


    public function loadGroupsAndInfo(string $pid)
    {
        try {
            $user = User::query()->where('uid', '=', $pid)->first();

            if (! $user) {
                $this->selectedUserGroups = [];
                $this->selectedUserInfo = [];

                return;
            }

            $this->selectedUserInfo = [
                'pid' => $user->getFirstAttribute('uid') ?? '',
                'surname' => $user->getFirstAttribute('sn') ?? '',
                'givenname' => $user->getFirstAttribute('givenname') ?? '',
                'info' => $user->getFirstAttribute('description') ?? '',
                'lastLogin' => $user->getFirstAttribute('logintime') ?? '—',
                'context' => $user->getContext() ?? '—',
            ];

            $rawGroups = $user->getAttribute('groupmembership') ?? [];

            $this->selectedUserGroups = $this->formatGroups(is_array($rawGroups) ? $rawGroups : []);
            $this->modal('groups')->show();

        } catch (\Exception $e) {
            $this->selectedUserGroups = [];
            $this->selectedUserInfo = [];
            $this->error = $e->getMessage();
        }
    }

    private function formatGroups(array $groups): array
    {
        $cleanGroups = [];

        foreach ($groups as $group) {
            $g = str_replace(
                [',o=ba', 'cn=', ',ou=', ',o=', ','],
                ['.ba', '', '.', '.', '.'],
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
