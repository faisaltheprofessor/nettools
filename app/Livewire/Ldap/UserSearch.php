<?php

namespace App\Livewire\Ldap;

use App\Ldap\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;

class UserSearch extends Component
{
    public $searchAttribute = 'PID';
    public $searchTerm = '';
    public $searchResults;
    public $error = null;

    public $selectedUserGroups = null;
    public $selectedUserInfo = [];
    public array $selectedUserGroupMap = [];

    /** Per-group state keyed by raw DN */
    public array $memberState = [];

    public ?string $memberListForDn = null;
    public string $memberSearch = '';
    public int $memberPageSize = 10;

    /** Forces inner modal subtree to re-render while keeping the same modal instance open */
    public int $gmNonce = 0;

    public $compareBasePid = null;
    public $compareBaseInfo = [];
    public $compareOtherPidInput = '';
    public $compareOtherPid = null;
    public $compareOtherInfo = [];
    public $compareGroups = null;
    public $compareError = null;
    public $compareView = 'common';

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

            $this->memberState = [];
            $this->memberListForDn = null;
            $this->memberSearch = '';

            $this->modal('groups')->show();
        } catch (\Exception $e) {
            $this->resetGroupsState();
            $this->error = $e->getMessage();
        }
    }

    public function openMembersModal(string $encodedDn): void
    {
        $dn = trim((string) base64_decode($encodedDn, true) ?: '');
        if ($dn === '') return;

        $this->memberListForDn = $dn;

        if (!array_key_exists($dn, $this->memberState)) {
            $cacheKey = 'ldap.group.members.collection.' . md5(mb_strtolower($dn));
            $list = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($dn) {
                $filter = sprintf('(groupmembership=%s)', $dn);
                $users = User::query()->rawFilter($filter)->limit(2000)->get();

                $i = 0;
                return collect($users)->map(function ($u) use (&$i) {
                    $i++;
                    return [
                        'ord' => $i,
                        'pid' => $u->getFirstAttribute('uid') ?? '—',
                        'givenname' => $u->getFirstAttribute('givenname') ?? '',
                        'surname' => $u->getFirstAttribute('sn') ?? '',
                        'tel' => $u->getFirstAttribute('telephonenumber')
                            ?? $u->getFirstAttribute('telephoneNumber')
                                ?? $u->getFirstAttribute('BAPK-telefon')
                                ?? $u->getFirstAttribute('mobile')
                                ?? '',
                    ];
                })->values();
            });

            $original = $list instanceof Collection ? $list : collect($list);
            $this->memberState[$dn] = [
                'original' => $original,
                'view'     => $original, // unsorted first render
                'sorted'   => false,
                'sortBy'   => null,
                'sortDir'  => null,
                'search'   => '',
                'page'     => 1,
                'pageSize' => $this->memberPageSize,
            ];
        } else {
            // Reset to first page when switching groups
            $this->memberState[$dn]['page'] = 1;
        }

        $this->memberSearch = $this->memberState[$dn]['search'] ?? '';
        $this->gmNonce++; // force inner subtree refresh while keeping the same modal open
        $this->modal('groupMembers')->show();
    }

    public function setMemberSort(string $encodedDn, string $by): void
    {
        $dn = trim((string) base64_decode($encodedDn, true) ?: '');
        if ($dn === '' || !isset($this->memberState[$dn])) return;
        if (!in_array($by, ['pid','givenname','surname','tel'], true)) return;

        $state = $this->memberState[$dn];

        if ($state['sorted'] && $state['sortBy'] === $by) {
            $state['sortDir'] = ($state['sortDir'] === 'asc') ? 'desc' : 'asc';
        } else {
            $state['sortBy']  = $by;
            $state['sortDir'] = 'asc';
            $state['sorted']  = true;
        }
        $state['page'] = 1;

        $state['view'] = $this->sortedFromOriginal($state['original'], $state['sortBy'], $state['sortDir']);
        $this->memberState[$dn] = $state;
        $this->gmNonce++;
    }

    public function updatedMemberSearch($value): void
    {
        $dn = $this->memberListForDn;
        if (!$dn || !isset($this->memberState[$dn])) return;

        $state = $this->memberState[$dn];
        $state['search'] = trim((string)$value);
        $state['page'] = 1;
        $this->memberState[$dn] = $state;
        $this->gmNonce++;
    }

    public function nextPage(): void
    {
        $dn = $this->memberListForDn;
        if (!$dn || !isset($this->memberState[$dn])) return;
        $this->memberState[$dn]['page']++;
        $this->gmNonce++;
    }

    public function previousPage(): void
    {
        $dn = $this->memberListForDn;
        if (!$dn || !isset($this->memberState[$dn])) return;
        if ($this->memberState[$dn]['page'] > 1) $this->memberState[$dn]['page']--;
        $this->gmNonce++;
    }

    public function gotoPage($page): void
    {
        $dn = $this->memberListForDn;
        if (!$dn || !isset($this->memberState[$dn])) return;
        $p = max(1, (int)$page);
        $this->memberState[$dn]['page'] = $p;
        $this->gmNonce++;
    }

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

    private function sortedFromOriginal(Collection $original, string $by, string $dir): Collection
    {
        $ties = array_values(array_filter(['surname','givenname','pid'], fn ($k) => $k !== $by));

        return $original->sort(function ($a, $b) use ($by, $dir, $ties) {
            $aval = (string)($a[$by] ?? '');
            $bval = (string)($b[$by] ?? '');
            $cmp  = strnatcasecmp($aval, $bval);

            if ($cmp !== 0) {
                return $dir === 'asc' ? $cmp : -$cmp;
            }

            foreach ($ties as $k) {
                $ta = (string)($a[$k] ?? '');
                $tb = (string)($b[$k] ?? '');
                $t  = strnatcasecmp($ta, $tb);
                if ($t !== 0) return $t;
            }

            $oa = (int)($a['ord'] ?? 0);
            $ob = (int)($b['ord'] ?? 0);
            return $oa <=> $ob;
        })->values();
    }

    private function filterMembers(Collection $rows, string $q): Collection
    {
        $needle = mb_strtolower(trim($q));
        if ($needle === '') return $rows;

        return $rows->filter(function ($r) use ($needle) {
            foreach (['pid', 'givenname', 'surname'] as $k) {
                $val = mb_strtolower((string)($r[$k] ?? ''));
                if ($val !== '' && str_contains($val, $needle)) return true;
            }
            return false;
        })->values();
    }

    public function getMembersPaginator(string $groupDn): ?LengthAwarePaginator
    {
        if (!isset($this->memberState[$groupDn])) return null;

        $state = $this->memberState[$groupDn];
        $view  = $state['view'] ?? null;
        if (!$view instanceof Collection) return null;

        $filtered = $this->filterMembers($view, $state['search'] ?? '');

        $page    = max(1, (int)($state['page'] ?? 1));
        $perPage = (int)($state['pageSize'] ?? $this->memberPageSize);
        $total   = $filtered->count();
        $maxPage = max(1, (int)ceil($total / $perPage));
        if ($page > $maxPage) $page = $maxPage;

        $items = $filtered->forPage($page, $perPage)->values();

        $pageName = 'gm_' . substr(md5($groupDn), 0, 8);
        $this->memberState[$groupDn]['page'] = $page;

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => $pageName]
        );
    }

    private function resetGroupsState(): void
    {
        $this->selectedUserGroups = [];
        $this->selectedUserInfo = [];
        $this->selectedUserGroupMap = [];
        $this->memberState = [];
        $this->memberListForDn = null;
        $this->memberSearch = '';
        $this->gmNonce = 0;
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
