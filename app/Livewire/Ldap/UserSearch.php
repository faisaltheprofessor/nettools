<?php

namespace App\Livewire\Ldap;

use App\Ldap\User;
use Illuminate\Support\Facades\Cache;
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

    public ?string $memberListForDn = null;
    public string $memberSearch = '';
    public int $memberPageSize = 5000;
    public int $gmNonce = 0;

    public array $memberState = [];

    public $compareBasePid = null;
    public $compareBaseInfo = [];
    public $compareOtherPidInput = '';
    public $compareOtherPid = null;
    public $compareOtherInfo = [];
    public $compareGroups = null;
    public $compareError = null;
    public $compareView = 'common';

    protected int $pageCacheTtlMinutes = 45;

    public function search(): void
    {
        $this->reset(['error', 'searchResults', 'selectedUserGroups', 'selectedUserInfo']);
        if (trim($this->searchTerm) === '') { $this->error = 'Bitte geben Sie einen Suchbegriff ein.'; return; }

        try {
            $attributeMap = ['PID' => 'uid', 'Nachname' => 'sn', 'Vollst. Name' => 'fullname', 'Titel' => 'title'];
            $ldapAttribute = $attributeMap[$this->searchAttribute] ?? 'uid';
            $term = trim($this->searchTerm);
            if ($this->searchAttribute === 'PID') $term = $this->normalizePid($term);
            $ldapFilter = sprintf('(%s=%s)', $ldapAttribute, $term);

            $users = User::query()->rawFilter($ldapFilter)->limit(100)->get();
            if ($users->isEmpty()) { $this->error = 'Keine Benutzer gefunden.'; return; }

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
            if (!$user) { $this->resetGroupsState(); return; }

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

        if (!isset($this->memberState[$dn])) {
            $this->memberState[$dn] = [
                'sorted'       => false,
                'sortBy'       => null,
                'sortDir'      => 'asc',
                'page'         => null,
                'rows'         => [],
            ];
        }

        $this->loadMembersCurrentCursor($dn);
        $this->gmNonce++;
        $this->modal('groupMembers')->show();
    }

    public function updatedMemberSearch($value): void
    {
        $dn = $this->memberListForDn;
        if (!$dn || !isset($this->memberState[$dn])) return;
        $this->memberSearch = trim((string)$value);
        $this->loadMembersCurrentCursor($dn);
    }

    public function setMemberSort(string $encodedDn, string $by): void
    {
        $dn = trim((string) base64_decode($encodedDn, true) ?: '');
        if ($dn === '' || !isset($this->memberState[$dn])) return;
        if (!in_array($by, ['pid','givenname','surname','tel','title'], true)) return;

        $state = $this->memberState[$dn];
        if ($state['sorted'] && $state['sortBy'] === $by) {
            $state['sortDir'] = ($state['sortDir'] === 'asc') ? 'desc' : 'asc';
        } else {
            $state['sortBy']  = $by;
            $state['sortDir'] = 'asc';
            $state['sorted']  = true;
        }
        $this->memberState[$dn] = $state;
        $this->sortCurrentPage($dn);
    }

    private function loadMembersCurrentCursor(string $dn): void
    {
        try {
            $state  = $this->memberState[$dn];
            $sortBy  = $state['sortBy'] ?? null;
            $sortDir = $state['sortDir'] ?? 'asc';

            $page = $this->fetchMembersPage(
                dn: $dn,
                pageSize: $this->memberPageSize,
                query: $this->memberSearch,
                sortBy: $sortBy,
                sortDir: $sortDir
            );

            $this->memberState[$dn]['rows'] = $page['rows'];
            $this->memberState[$dn]['page'] = ['rows' => $page['rows']];

            $this->sortCurrentPage($dn);
        } catch (\Throwable $e) {
            $this->memberState[$dn]['rows'] = [];
            $this->memberState[$dn]['page'] = ['rows' => []];
            $this->error = $e->getMessage();
        }
    }

    private function sortCurrentPage(string $dn): void
    {
        $st = $this->memberState[$dn] ?? null;
        if (!$st || empty($st['page']['rows'])) return;
        if (!($st['sorted'] ?? false) || !($st['sortBy'] ?? null)) return;

        $by  = $st['sortBy'];
        $dir = $st['sortDir'] ?? 'asc';

        $rows = $st['page']['rows'];
        usort($rows, function ($a, $b) use ($by, $dir) {
            $aval = (string)($a[$by] ?? '');
            $bval = (string)($b[$by] ?? '');
            $cmp  = strnatcasecmp($aval, $bval);
            if ($cmp !== 0) return $dir === 'asc' ? $cmp : -$cmp;

            foreach (['surname','givenname','title','pid'] as $k) {
                if ($k === $by) continue;
                $ta = (string)($a[$k] ?? '');
                $tb = (string)($b[$k] ?? '');
                $t  = strnatcasecmp($ta, $tb);
                if ($t !== 0) return $t;
            }
            return 0;
        });

        $this->memberState[$dn]['page']['rows'] = $rows;
        $this->memberState[$dn]['rows'] = $rows;
    }

    private function fetchMembersPage(string $dn, int $pageSize, string $query = '', ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        $filter = $this->buildFilter($dn, $query);
        $attrs = ['uid','givenname','sn','title','telephonenumber','telephoneNumber','BAPK-telefon','mobile'];

        $cacheKey = 'ldap.members.page.full.' . md5(json_encode([
            'dn'     => mb_strtolower($dn),
            'filter' => $filter,
            'size'   => $pageSize,
            'sort'   => [$sortBy, $sortDir],
        ]));

        return Cache::remember($cacheKey, now()->addMinutes($this->pageCacheTtlMinutes), function () use ($filter, $attrs) {
            $q = User::query()->select($attrs)->rawFilter($filter);
            $results = $q->limit($this->memberPageSize)->get();

            $rows = [];
            foreach ($results as $u) {
                $pid   = $u->getFirstAttribute('uid') ?? '';
                $given = $u->getFirstAttribute('givenname') ?? '';
                $sur   = $u->getFirstAttribute('sn') ?? '';
                $title = $u->getFirstAttribute('title') ?? '';
                $tel   = $u->getFirstAttribute('telephonenumber')
                    ?? $u->getFirstAttribute('telephoneNumber')
                    ?? $u->getFirstAttribute('BAPK-telefon')
                    ?? $u->getFirstAttribute('mobile')
                    ?? '';

                if ($pid === '' && $given === '' && $sur === '' && $title === '' && $tel === '') continue;

                $rows[] = [
                    'pid'       => $pid !== '' ? $pid : '—',
                    'title'     => $title,
                    'givenname' => $given,
                    'surname'   => $sur,
                    'tel'       => $tel,
                ];
            }

            return [
                'rows'        => $rows,
            ];
        });
    }

    private function buildFilter(string $dn, string $query): string
    {
        $base = '(groupmembership=' . $dn . ')';
        $q = trim($query);
        if ($q === '') return $base;
        $e = self::esc($q);
        $or = '(|(uid=*' . $e . '*)(givenname=*' . $e . '*)(sn=*' . $e . '*)(title=*' . $e . '*)(telephonenumber=*' . $e . '*)(telephoneNumber=*' . $e . '*)(BAPK-telefon=*' . $e . '*)(mobile=*' . $e . '*))';
        return '(&' . $base . $or . ')';
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

        if (!$this->compareBasePid) { $this->compareError = 'Basis-PID fehlt.'; return; }
        if (trim($this->compareOtherPidInput) === '') { $this->compareError = 'Bitte zweite PID eingeben.'; return; }

        $this->compareOtherPid = $this->normalizePid($this->compareOtherPidInput);

        try {
            $leftGroups  = $this->getUserGroupsAndInfo($this->compareBasePid, $this->compareBaseInfo);
            $rightGroups = $this->getUserGroupsAndInfo($this->compareOtherPid, $this->compareOtherInfo);

            $leftSet  = collect($leftGroups)->unique()->values()->all();
            $rightSet = collect($rightGroups)->unique()->values()->all();

            $onlyLeft = array_values(array_diff($leftSet, $rightSet));
            $onlyRight= array_values(array_diff($rightSet, $leftSet));
            $common   = array_values(array_intersect($leftSet, $rightSet));

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
        if (!in_array($view, ['user1','user2','common','diffs'], true)) return;
        $this->compareView = $view;
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

    private static function esc(string $s): string
    {
        return str_replace(['\\', '*', '(', ')', "\x00"], ['\5c','\2a','\28','\29','\00'], $s);
    }

    private function titleCaseName(array $info): string
    {
        $given = trim((string)($info['givenname'] ?? ''));
        $sur   = trim((string)($info['surname'] ?? ''));
        $pid   = $info['pid'] ?? '—';
        $given = $given !== '' ? (mb_strtoupper(mb_substr($given, 0, 1)) . mb_strtolower(mb_substr($given, 1))) : '';
        $sur   = $sur   !== '' ? (mb_strtoupper(mb_substr($sur, 0, 1)) . mb_strtolower(mb_substr($sur, 1))) : '';
        $full = trim($given . ' ' . $sur);
        return ($full !== '') ? "$full ($pid)" : $pid;
    }

    public function render()
    {
        return view('livewire.ldap.user-search', [
            'titleCaseName' => fn($info) => $this->titleCaseName($info ?? []),
        ]);
    }
}
