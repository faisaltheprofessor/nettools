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

    // --- Compare state ---
    public $compareBasePid = null;          // first user pid (normalized)
    public $compareBaseInfo = [];           // ['pid','surname','givenname']
    public $compareOtherPidInput = '';      // raw from modal
    public $compareOtherPid = null;         // normalized pid
    public $compareOtherInfo = [];          // second user info
    public $compareGroups = null;           // ['only_first','only_second','common','all_first','all_second']
    public $compareError = null;

    // active view: 'user1' | 'user2' | 'common' | 'diffs'
    public $compareView = 'common';

    public function search()
    {
        $this->reset(['error', 'searchResults', 'selectedUserGroups', 'selectedUserInfo']);

        if (trim($this->searchTerm) === '') {
            $this->error = 'Bitte geben Sie einen Suchbegriff ein.';
            return;
        }

        try {
            $attributeMap = [
                'PID'          => 'uid',
                'Nachname'     => 'sn',
                'Vollst. Name' => 'fullname',
            ];

            $ldapAttribute = $attributeMap[$this->searchAttribute] ?? 'uid';
            $term = trim($this->searchTerm);

            if ($this->searchAttribute === 'PID') {
                $term = $this->normalizePid($term);
            }

            // user-friendly wildcard passthrough
            $pattern = str_replace(['*', '?'], ['*', '?'], $term);
            $ldapFilter = sprintf('(%s=%s)', $ldapAttribute, $pattern);

            $users = User::query()->rawFilter($ldapFilter)->limit(100)->get();

            if ($users->isEmpty()) {
                $this->error = 'Keine Benutzer gefunden.';
                return;
            }

            $results = collect();
            foreach ($users as $user) {
                $results->push([
                    'pid'            => $user->getFirstAttribute('uid'),
                    'fullname'       => $user->getFirstAttribute('cn') ?? '',
                    'surname'        => $user->getFirstAttribute('sn') ?? '',
                    'givenname'      => $user->getFirstAttribute('givenname') ?? '',
                    'email'          => $user->getFirstAttribute('mail') ?? '',
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
            $pid  = $this->normalizePid($pid);
            $user = User::query()->where('uid', '=', $pid)->first();

            if (!$user) {
                $this->selectedUserGroups = [];
                $this->selectedUserInfo   = [];
                return;
            }

            $this->selectedUserInfo = [
                'pid'       => $user->getFirstAttribute('uid') ?? '',
                'surname'   => $user->getFirstAttribute('sn') ?? '',
                'givenname' => $user->getFirstAttribute('givenname') ?? '',
                'info'      => $user->getFirstAttribute('description') ?? '',
                'lastLogin' => $user->getFirstAttribute('logintime') ?? '—',
                'context'   => method_exists($user, 'getContext') ? ($user->getContext() ?? '—') : '—',
            ];

            $rawGroups = $user->getAttribute('groupmembership') ?? [];
            $this->selectedUserGroups = $this->formatGroups(is_array($rawGroups) ? $rawGroups : []);

            $this->modal('groups')->show();
        } catch (\Exception $e) {
            $this->selectedUserGroups = [];
            $this->selectedUserInfo   = [];
            $this->error = $e->getMessage();
        }
    }

    // Open compare modal (pick base)
    public function openCompare(string $basePid): void
    {
        $this->resetCompare();
        $this->compareBasePid = $this->normalizePid($basePid);
        $this->compareView    = 'common';
        $this->modal('compare')->show();
    }

    // Run comparison
    public function runCompare(): void
    {
        $this->compareError     = null;
        $this->compareGroups    = null;
        $this->compareBaseInfo  = [];
        $this->compareOtherInfo = [];

        if (!$this->compareBasePid) {
            $this->compareError = 'Basis-PID fehlt.';
            return;
        }

        if (trim($this->compareOtherPidInput) === '') {
            $this->compareError = 'Bitte zweite PID eingeben.';
            return;
        }

        $this->compareOtherPid = $this->normalizePid($this->compareOtherPidInput);

        try {
            // fetch both users + groups
            $leftGroups  = $this->getUserGroupsAndInfo($this->compareBasePid, $this->compareBaseInfo);
            $rightGroups = $this->getUserGroupsAndInfo($this->compareOtherPid, $this->compareOtherInfo);

            // sets
            $leftSet  = collect($leftGroups)->unique()->values()->all();
            $rightSet = collect($rightGroups)->unique()->values()->all();

            $onlyLeft  = array_values(array_diff($leftSet, $rightSet));
            $onlyRight = array_values(array_diff($rightSet, $leftSet));
            $common    = array_values(array_intersect($leftSet, $rightSet));

            sort($onlyLeft, SORT_FLAG_CASE | SORT_STRING);
            sort($onlyRight, SORT_FLAG_CASE | SORT_STRING);
            sort($common, SORT_FLAG_CASE | SORT_STRING);

            $this->compareGroups = [
                'only_first'   => $onlyLeft,
                'only_second'  => $onlyRight,
                'common'       => $common,
                'all_first'    => $leftSet,
                'all_second'   => $rightSet,
                'count_first'  => count($leftSet),
                'count_second' => count($rightSet),
            ];
        } catch (\Exception $e) {
            $this->compareError = $e->getMessage();
        }
    }

    public function setCompareView(string $view): void
    {
        if (!in_array($view, ['user1','user2','common','diffs'], true)) {
            return;
        }
        $this->compareView = $view;
    }

    private function getUserGroupsAndInfo(string $pid, array &$info): array
    {
        $user = User::query()->where('uid', '=', $pid)->first();
        if (!$user) {
            throw new \RuntimeException("Benutzer $pid nicht gefunden.");
        }

        $info = [
            'pid'       => $user->getFirstAttribute('uid') ?? $pid,
            'surname'   => $user->getFirstAttribute('sn') ?? '',
            'givenname' => $user->getFirstAttribute('givenname') ?? '',
        ];

        $rawGroups = $user->getAttribute('groupmembership') ?? [];
        return $this->formatGroups(is_array($rawGroups) ? $rawGroups : []);
    }

    private function normalizePid(string $term): string
    {
        $t = strtolower(trim($term));
        if (!str_starts_with($t, 'p')) {
            $t = 'p' . $t;
        }
        return $t;
    }

    private function formatGroups(array $groups): array
    {
        $clean = [];
        foreach ($groups as $group) {
            $g = str_replace(
                [',o=ba', 'cn=', ',ou=', ',o=', ','],
                ['.ba',   '',    '.',    '.',   '.'],
                $group
            );
            $clean[] = $g;
        }
        $clean = array_values(array_unique($clean));
        sort($clean, SORT_FLAG_CASE | SORT_STRING);
        return $clean;
    }

    /** Title-case helper for names shown in compare UI */
    private function titleCaseName(array $info): string
    {
        $given = trim((string)($info['givenname'] ?? ''));
        $sur   = trim((string)($info['surname'] ?? ''));
        $pid   = $info['pid'] ?? '—';

        $given = $given !== '' ? (mb_strtoupper(mb_substr($given, 0, 1)) . mb_strtolower(mb_substr($given, 1))) : '';
        $sur   = $sur   !== '' ? (mb_strtoupper(mb_substr($sur,   0, 1)) . mb_strtolower(mb_substr($sur,   1))) : '';

        $full  = trim($given . ' ' . $sur);
        return ($full !== '') ? "$full ($pid)" : $pid;
    }

    private function resetCompare(): void
    {
        $this->compareOtherPidInput = '';
        $this->compareOtherPid      = null;

        $this->compareBaseInfo      = [];
        $this->compareOtherInfo     = [];

        $this->compareGroups        = null;
        $this->compareError         = null;

        // default tab
        $this->compareView          = 'common';
    }


    public function render()
    {
        // Pass a callable into the view for neat title-case rendering
        return view('livewire.ldap.user-search', [
            'titleCaseName' => fn($info) => $this->titleCaseName($info),
        ]);
    }
}
