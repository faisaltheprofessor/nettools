<?php

namespace App\Livewire\Ldap;

use App\Ldap\User;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class UserSearch extends Component
{
    // --- Top-level search ---
    public $searchAttribute = 'PID';
    public $searchTerm = '';
    public $searchResults;
    public $error = null;

    // --- User details + groups modal ---
    public $selectedUserGroups = null;        // display names
    public $selectedUserInfo = [];
    public array $selectedUserGroupMap = [];   // display => DN

    // --- Members (opened in centered modal) ---
    public array $groupMembersByDn = [];       // DN => rows
    public ?string $memberListForDn = null;    // active group DN
    public string $memberSortBy = 'givenname'; // pid|givenname|surname|tel
    public string $memberSortDir = 'asc';      // asc|desc
    public string $memberSearch = '';          // filter across all fields
    public int $memberPageSize = 10;
    public int $membersCurrentPage = 1;

    // --- Compare modal ---
    public $compareBasePid = null;
    public $compareBaseInfo = [];
    public $compareOtherPidInput = '';
    public $compareOtherPid = null;
    public $compareOtherInfo = [];
    public $compareGroups = null;
    public $compareError = null;
    public $compareView = 'common';

    // ================= Actions =================

    public function search(): void
    {
        $this->reset(['error', 'searchResults', 'selectedUserGroups', 'selectedUserInfo']);

        if (trim($this->searchTerm) === '') {
            $this->error = 'Bitte geben Sie einen Suchbegriff ein.';
            return;
        }

        try {
            $attributeMap = [
                'PID' => 'uid',
                'Nachname' => 'sn',
                'Vollst. Name' => 'fullname',
                'Titel' => 'title',
            ];

            $ldapAttribute = $attributeMap[$this->searchAttribute] ?? 'uid';
            $term = trim($this->searchTerm);
            if ($this->searchAttribute === 'PID') $term = $this->normalizePid($term);

            // Wildcards are passed through as-is
            $ldapFilter = sprintf('(%s=%s)', $ldapAttribute, $term);
            $users = User::query()->rawFilter($ldapFilter)->limit(100)->get();

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
                    'title' => $user->getFirstAttribute('title') ?? '',
                    'email' => $user->getFirstAttribute('mail') ?? '',
                    'external_email' => $user->getFirstAttribute('BAPK-mailext') ?? '',
                ]);
            }
            $this->searchResults = $results;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    public function loadGroupsAndInfo(string $pid): void
    {
        try {
            $pid = $this->normalizePid($pid);
            $user = User::query()->where('uid', '=', $pid)->first();

            if (!$user) {
                $this->resetGroupsState();
                return;
            }

            $this->selectedUserInfo = [
                'pid' => $user->getFirstAttribute('uid') ?? '',
                'surname' => $user->getFirstAttribute('sn') ?? '',
                'givenname' => $user->getFirstAttribute('givenname') ?? '',
                'title' => $user->getFirstAttribute('title') ?? '',
                'info' => $user->getFirstAttribute('description') ?? '',
                'lastLogin' => $user->getFirstAttribute('logintime') ?? '—',
                'context' => method_exists($user, 'getContext') ? ($user->getContext() ?? '—') : '—',
            ];

            $rawGroups = $user->getAttribute('groupmembership') ?? [];
            $rawGroups = is_array($rawGroups) ? $rawGroups : [];

            $this->selectedUserGroups = $this->formatGroups($rawGroups);
            $this->selectedUserGroupMap = $this->mapGroups($rawGroups);

            // Reset members state
            $this->groupMembersByDn = [];
            $this->memberListForDn = null;
            $this->membersCurrentPage = 1;
            $this->memberSearch = '';
            $this->memberSortBy = 'givenname';
            $this->memberSortDir = 'asc';

            $this->modal('groups')->show();
        } catch (\Exception $e) {
            $this->resetGroupsState();
            $this->error = $e->getMessage();
        }
    }

    // === Members modal (open directly) ===
    public function openMembersModal(string $groupDn): void
    {
        $dn = trim($groupDn);
        if ($dn === '') return;

        $this->memberListForDn = $dn;
        $this->membersCurrentPage = 1;

        if (!array_key_exists($dn, $this->groupMembersByDn)) {
            $cacheKey = 'ldap.group.members.' . md5($dn);
            // 5-minute cache TTL (adjust later with explicit invalidation)
            $list = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($dn) {
                $filter = sprintf('(groupmembership=%s)', $dn);
                $users = User::query()->rawFilter($filter)->limit(1000)->get();

                $rows = [];
                foreach ($users as $u) {
                    $rows[] = [
                        'pid' => $u->getFirstAttribute('uid') ?? '—',
                        'givenname' => $u->getFirstAttribute('givenname') ?? '',
                        'surname' => $u->getFirstAttribute('sn') ?? '',
                        'tel' => $u->getFirstAttribute('telephonenumber')
                            ?? $u->getFirstAttribute('telephoneNumber')
                                ?? $u->getFirstAttribute('BAPK-telefon')
                                ?? $u->getFirstAttribute('mobile')
                                ?? '',
                    ];
                }
                return $rows;
            });

            $this->groupMembersByDn[$dn] = $list;
        }

        $this->applySortKeep($dn);
        $this->modal('groupMembers')->show();
    }

    // Sorting + search
    public function setMemberSort(string $groupDn, string $by): void
    {
        if (!in_array($by, ['pid', 'givenname', 'surname', 'tel'], true)) return;
        if ($this->memberListForDn !== $groupDn) $this->memberListForDn = $groupDn;

        if ($this->memberSortBy === $by) {
            $this->memberSortDir = $this->memberSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->memberSortBy = $by;
            $this->memberSortDir = 'asc';
        }

        $this->membersCurrentPage = 1;
        $this->applySortKeep($groupDn);
    }

    public function setMemberSearch(string $groupDn, string $search): void
    {
        if ($this->memberListForDn !== $groupDn) $this->memberListForDn = $groupDn;
        $this->memberSearch = trim($search);
        $this->membersCurrentPage = 1;
    }

    // Pagination hooks expected by Flux/Livewire table
    public function nextPage(): void
    {
        $this->membersCurrentPage++;
    }

    public function previousPage(): void
    {
        if ($this->membersCurrentPage > 1) $this->membersCurrentPage--;
    }

    public function gotoPage($page): void
    {
        $p = (int)$page;
        if ($p < 1) $p = 1;
        $this->membersCurrentPage = $p;
    }

    // === Compare (unchanged logic) ===
    public function openCompare(string $basePid): void
    {
        $this->resetCompare();
        $this->compareBasePid = $this->normalizePid($basePid);
        $this->compareView = 'common';
        $this->modal('compare')->show();
    }

    private function resetCompare(): void
    {
        $this->compareOtherPidInput = '';
        $this->compareOtherPid = null;

        $this->compareBaseInfo = [];
        $this->compareOtherInfo = [];

        $this->compareGroups = null;
        $this->compareError = null;

        // default tab
        $this->compareView = 'common';
    }


    public function runCompare(): void
    {
        $this->compareError = null;
        $this->compareGroups = null;
        $this->compareBaseInfo = [];
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
            $leftGroups = $this->getUserGroupsAndInfo($this->compareBasePid, $this->compareBaseInfo);
            $rightGroups = $this->getUserGroupsAndInfo($this->compareOtherPid, $this->compareOtherInfo);

            $leftSet = collect($leftGroups)->unique()->values()->all();
            $rightSet = collect($rightGroups)->unique()->values()->all();

            $onlyLeft = array_values(array_diff($leftSet, $rightSet));
            $onlyRight = array_values(array_diff($rightSet, $leftSet));
            $common = array_values(array_intersect($leftSet, $rightSet));

            sort($onlyLeft, SORT_FLAG_CASE | SORT_STRING);
            sort($onlyRight, SORT_FLAG_CASE | SORT_STRING);
            sort($common, SORT_FLAG_CASE | SORT_STRING);

            $this->compareGroups = [
                'only_first' => $onlyLeft,
                'only_second' => $onlyRight,
                'common' => $common,
                'all_first' => $leftSet,
                'all_second' => $rightSet,
                'count_first' => count($leftSet),
                'count_second' => count($rightSet),
            ];
        } catch (\Exception $e) {
            $this->compareError = $e->getMessage();
        }
    }

    public function setCompareView(string $view): void
    {
        if (!in_array($view, ['user1', 'user2', 'common', 'diffs'], true)) return;
        $this->compareView = $view;
    }

    // ================= Helpers =================

    private function applySortKeep(string $dn): void
    {
        if (!isset($this->groupMembersByDn[$dn])) return;
        $data = $this->groupMembersByDn[$dn];

        usort($data, function ($a, $b) {
            $key = $this->memberSortBy;
            $dir = $this->memberSortDir;
            $va = (string)($a[$key] ?? '');
            $vb = (string)($b[$key] ?? '');
            $cmp = strnatcasecmp($va, $vb);
            return $dir === 'asc' ? $cmp : -$cmp;
        });

        $this->groupMembersByDn[$dn] = $data;
    }

    private function filterMembers(array $rows): array
    {
        $q = mb_strtolower(trim($this->memberSearch));
        if ($q === '') return $rows;

        return array_values(array_filter($rows, function ($r) use ($q) {
            foreach (['pid', 'givenname', 'surname', 'tel'] as $k) {
                $val = mb_strtolower((string)($r[$k] ?? ''));
                if ($val !== '' && str_contains($val, $q)) return true;
            }
            return false;
        }));
    }

    public function getMembersPaginator(string $groupDn): ?LengthAwarePaginator
    {
        if (!isset($this->groupMembersByDn[$groupDn])) return null;

        // Filter AFTER sorting for predictable order
        $all = $this->filterMembers($this->groupMembersByDn[$groupDn]);

        $page = max(1, (int)$this->membersCurrentPage);
        $perPage = $this->memberPageSize;
        $total = count($all);
        $maxPage = max(1, (int)ceil($total / $perPage));
        if ($page > $maxPage) $page = $maxPage;

        $offset = ($page - 1) * $perPage;
        $items = array_slice($all, $offset, $perPage);

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'] // pageName is irrelevant to Livewire hooks
        );
    }

    private function resetGroupsState(): void
    {
        $this->selectedUserGroups = [];
        $this->selectedUserInfo = [];
        $this->selectedUserGroupMap = [];
        $this->groupMembersByDn = [];
        $this->memberListForDn = null;
        $this->membersCurrentPage = 1;
        $this->memberSearch = '';
        $this->memberSortBy = 'givenname';
        $this->memberSortDir = 'asc';
    }

    private function getUserGroupsAndInfo(string $pid, array &$info): array
    {
        $user = User::query()->where('uid', '=', $pid)->first();
        if (!$user) throw new \RuntimeException("Benutzer $pid nicht gefunden.");

        $info = [
            'pid' => $user->getFirstAttribute('uid') ?? $pid,
            'surname' => $user->getFirstAttribute('sn') ?? '',
            'givenname' => $user->getFirstAttribute('givenname') ?? '',
        ];

        $rawGroups = $user->getAttribute('groupmembership') ?? [];
        return $this->formatGroups(is_array($rawGroups) ? $rawGroups : []);
    }

    private function normalizePid(string $term): string
    {
        $t = strtolower(trim($term));
        if (!str_starts_with($t, 'p')) $t = 'p' . $t;
        return $t;
    }

    private function formatGroups(array $groups): array
    {
        $clean = [];
        foreach ($groups as $group) {
            $g = str_replace(
                [',o=ba', 'cn=', ',ou=', ',o=', ','],
                ['.ba', '', '.', '.', '.'],
                $group
            );
            $clean[] = $g;
        }
        $clean = array_values(array_unique($clean));
        sort($clean, SORT_FLAG_CASE | SORT_STRING);
        return $clean;
    }

    private function mapGroups(array $rawGroups): array
    {
        $map = [];
        foreach ($rawGroups as $dn) {
            $display = str_replace(
                [',o=ba', 'cn=', ',ou=', ',o=', ','],
                ['.ba', '', '.', '.', '.'],
                $dn
            );
            $map[$display] = $dn;
        }
        return $map;
    }

    private function titleCaseName(array $info): string
    {
        $given = trim((string)($info['givenname'] ?? ''));
        $sur = trim((string)($info['surname'] ?? ''));
        $pid = $info['pid'] ?? '—';

        $given = $given !== '' ? (mb_strtoupper(mb_substr($given, 0, 1)) . mb_strtolower(mb_substr($given, 1))) : '';
        $sur = $sur !== '' ? (mb_strtoupper(mb_substr($sur, 0, 1)) . mb_strtolower(mb_substr($sur, 1))) : '';

        $full = trim($given . ' ' . $sur);
        return ($full !== '') ? "$full ($pid)" : $pid;
    }

    public function render()
    {
        return view('livewire.ldap.user-search', [
            'titleCaseName' => fn($info) => $this->titleCaseName($info),
        ]);
    }
}
