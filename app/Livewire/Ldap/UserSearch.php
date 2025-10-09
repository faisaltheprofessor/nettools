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

    public $userSorted = false;

    public $userSortBy = null;

    public $userSortDir = 'asc';

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

    /**
     * Execute search by selected attribute.
     * - PID normalized to leading "p".
     * - Telefon strictly matches last 4 digits (post-filtered).
     * - Wildcards (*) supported for name/title; RFC4515 escaping.
     */
    public function search(): void
    {
        $this->reset(['error', 'searchResults', 'selectedUserGroups', 'selectedUserInfo']);

        if (trim($this->searchTerm) === '') {
            $this->error = 'Bitte geben Sie einen Suchbegriff ein.';

            return;
        }

        try {
            $attribute = $this->searchAttribute;
            $term = trim($this->searchTerm);

            // Normalize PID
            if ($attribute === 'PID') {
                $term = $this->normalizePid($term);

                // Normalize phone number to last 4 digits
            } elseif ($attribute === 'Telefon') {
                $term = $this->normalizeShortTel($term);
                if (strlen($term) !== 4) {
                    $this->error = 'Bitte genau 4 Ziffern eingeben.';

                    return;
                }

                // Extract only last name if format is "Lastname, Firstname"
            } elseif ($attribute === 'Nachname') {
                if (str_contains($term, ',')) {
                    [$last, $first] = array_map('trim', explode(',', $term, 2));
                    $term = $last;
                }

                // Normalize "Firstname Lastname" or "Lastname, Firstname"
            } elseif ($attribute === 'Vollst. Name') {
                $term = trim($term);
                if (str_contains($term, ',')) {
                    [$last, $first] = array_map('trim', explode(',', $term, 2));
                    $firstEsc = self::withWildcards(self::rfc4515Escape($first, true));
                    $lastEsc = self::withWildcards(self::rfc4515Escape($last, true));
                    $filter = sprintf('(&(givenname=%s)(sn=%s))', $firstEsc, $lastEsc);
                } else {
                    $parts = preg_split('/\s+/', $term);
                    if (count($parts) >= 2) {
                        [$first, $last] = $parts;
                        $firstEsc = self::withWildcards(self::rfc4515Escape($first, true));
                        $lastEsc = self::withWildcards(self::rfc4515Escape($last, true));
                        $filter = sprintf('(&(givenname=%s)(sn=%s))', $firstEsc, $lastEsc);
                    } else {
                        // Fallback to cn search if only one word entered
                        $t = self::withWildcards(self::rfc4515Escape($term, true));
                        $filter = sprintf('(cn=%s)', $t);
                    }
                }
            }

            // Build default filter if not already created above
            if (! isset($filter)) {
                $filter = $this->ldapFilterForUserSearch($attribute, $term);
            }

            $attrs = [
                'uid', 'cn', 'sn', 'givenname', 'title', 'mail',
                'BAPK-mailext', 'telephonenumber', 'telephoneNumber',
                'BAPK-telefon', 'mobile', 'logindisabled',
            ];

            $users = User::query()->select($attrs)->rawFilter($filter)->limit(500)->get();

            if ($users->isEmpty()) {
                $this->error = 'Keine Benutzer gefunden.';

                return;
            }

            $results = collect();

            foreach ($users as $user) {
                $rawTel = $user->getFirstAttribute('telephonenumber')
                    ?? $user->getFirstAttribute('telephoneNumber')
                    ?? $user->getFirstAttribute('BAPK-telefon')
                    ?? $user->getFirstAttribute('mobile')

                    ?? '';

                $shortTel = $this->normalizeShortTel($rawTel);

                if ($attribute === 'Telefon' && $shortTel !== $term) {
                    continue;
                }

                $active = $user->getContext() !== 'DeaktivierteUser.ba';
                $loginDisabled = $user->getFirstAttribute('logindisabled') === 'TRUE' ?? true;

                $results->push([
                    'pid' => $user->getFirstAttribute('uid'),
                    'fullname' => $user->getFirstAttribute('cn') ?? '',
                    'surname' => $user->getFirstAttribute('sn') ?? '',
                    'givenname' => $user->getFirstAttribute('givenname') ?? '',
                    'title' => $user->getFirstAttribute('title') ?? '',
                    'email' => $user->getFirstAttribute('mail') ?? '',
                    'external_email' => $user->getFirstAttribute('BAPK-mailext') ?? '',
                    'tel' => $shortTel,
                    'active' => $active,
                    'logindisabled' => $loginDisabled,
                    'additionalinfo' => 'Konto deaktiviert: '.strtolower($user->getFirstAttribute('logindisabled')).' | Kontext: '.$user->getContext(),
                ]);
            }

            $results = $results->unique('pid')->values();

            if ($results->isEmpty()) {
                $this->error = 'Keine Benutzer gefunden.';

                return;
            }

            $this->searchResults = $this->sortUserCollection($results);

        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    /**
     * Toggle header sort for results table.
     */
    public function setUserSort(string $by): void
    {
        if (! in_array($by, ['pid', 'surname', 'givenname', 'title', 'tel', 'email'], true)) {
            return;
        }
        if ($this->userSorted && $this->userSortBy === $by) {
            $this->userSortDir = $this->userSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->userSortBy = $by;
            $this->userSortDir = 'asc';
            $this->userSorted = true;
        }
        if ($this->searchResults) {
            $this->searchResults = $this->sortUserCollection(collect($this->searchResults));
        }
    }

    /**
     * Natural sort with stable fallbacks.
     */
    private function sortUserCollection($collection)
    {
        if (! $this->userSorted || ! $this->userSortBy) {
            return $collection->values();
        }
        $by = $this->userSortBy;
        $dir = $this->userSortDir;

        $sorted = $collection->sort(function ($a, $b) use ($by, $dir) {
            $aa = (string) ($a[$by] ?? '');
            $bb = (string) ($b[$by] ?? '');
            $cmp = strnatcasecmp($aa, $bb);
            if ($cmp !== 0) {
                return $dir === 'asc' ? $cmp : -$cmp;
            }
            foreach (['surname', 'givenname', 'title', 'pid'] as $k) {
                if ($k === $by) {
                    continue;
                }
                $ta = (string) ($a[$k] ?? '');
                $tb = (string) ($b[$k] ?? '');
                $t = strnatcasecmp($ta, $tb);
                if ($t !== 0) {
                    return $t;
                }
            }

            return 0;
        });

        return $sorted->values();
    }

    /**
     * Build LDAP filter for the main search form.
     * Preserves * as wildcard; escapes RFC4515 chars; phone is broad then filtered to last-4.
     */
    private function ldapFilterForUserSearch(string $attribute, string $term): string
    {
        if ($attribute === 'PID') {
            return sprintf('(uid=%s)', self::rfc4515Escape($term, true));
        }

        if ($attribute === 'Nachname') {
            $t = self::withWildcards(self::rfc4515Escape($term, true));

            return sprintf('(sn=%s)', $t);
        }

        if ($attribute === 'Vollst. Name') {
            $t = self::withWildcards(self::rfc4515Escape($term, true));

            return sprintf('(cn=%s)', $t);
        }

        if ($attribute === 'Titel') {
            $t = self::withWildcards(self::rfc4515Escape($term, true));

            return sprintf('(title=%s)', $t);
        }

        if ($attribute === 'Telefon') {
            $t = self::withWildcards(self::rfc4515Escape($term, true));

            return sprintf('(|(telephonenumber=%1$s)(telephoneNumber=%1$s)(BAPK-telefon=%1$s)(mobile=%1$s))', $t);
        }

        $t = self::withWildcards(self::rfc4515Escape($term, true));

        return sprintf(
            '(|(uid=%1$s)(sn=%1$s)(givenname=%1$s)(cn=%1$s)(title=%1$s)(telephonenumber=%1$s)(telephoneNumber=%1$s)(BAPK-telefon=%1$s)(mobile=%1$s))',
            $t
        );
    }

    /**
     * Load info and groups for a PID.
     */
    public function loadGroupsAndInfo(string $pid): void
    {
        try {
            $pid = $this->normalizePid($pid);
            $user = User::query()->where('uid', '=', $pid)->first();
            if (! $user) {
                $this->resetGroupsState();

                return;
            }

            $rawTel = $user->getFirstAttribute('telephonenumber')
                ?? $user->getFirstAttribute('telephoneNumber')
                ?? $user->getFirstAttribute('BAPK-telefon')
                ?? $user->getFirstAttribute('mobile')
                ?? '';

            $this->selectedUserInfo = [
                'pid' => $user->getFirstAttribute('uid') ?? '',
                'surname' => $user->getFirstAttribute('sn') ?? '',
                'givenname' => $user->getFirstAttribute('givenname') ?? '',
                'title' => $user->getFirstAttribute('title') ?? '',
                'info' => $user->getFirstAttribute('description') ?? '',
                'lastLogin' => $user->getFirstAttribute('logintime') ?? '—',
                'context' => method_exists($user, 'getContext') ? ($user->getContext() ?? '—') : '—',
                'tel' => $this->normalizeShortTel($rawTel),
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

    /**
     * Open group members modal for DN.
     */
    public function openMembersModal(string $encodedDn): void
    {
        $dn = trim((string) base64_decode($encodedDn, true) ?: '');
        if ($dn === '') {
            return;
        }

        $this->memberListForDn = $dn;

        if (! isset($this->memberState[$dn])) {
            $this->memberState[$dn] = [
                'sorted' => false,
                'sortBy' => null,
                'sortDir' => 'asc',
                'page' => null,
                'rows' => [],
            ];
        }

        $this->loadMembersCurrentCursor($dn);
        $this->gmNonce++;
        $this->modal('groupMembers')->show();
    }

    /**
     * Live-search inside members table. For phones we still normalize to last-4 if digits.
     */
    public function updatedMemberSearch($value): void
    {
        $dn = $this->memberListForDn;
        if (! $dn || ! isset($this->memberState[$dn])) {
            return;
        }
        $val = (string) $value;
        $this->memberSearch = ctype_digit($val) ? $this->normalizeShortTel($val) : $val;
        $this->loadMembersCurrentCursor($dn);
    }

    /**
     * Toggle sort for members table.
     */
    public function setMemberSort(string $encodedDn, string $by): void
    {
        $dn = trim((string) base64_decode($encodedDn, true) ?: '');
        if ($dn === '' || ! isset($this->memberState[$dn])) {
            return;
        }
        if (! in_array($by, ['pid', 'givenname', 'surname', 'tel', 'title'], true)) {
            return;
        }

        $state = $this->memberState[$dn];
        if ($state['sorted'] && $state['sortBy'] === $by) {
            $state['sortDir'] = ($state['sortDir'] === 'asc') ? 'desc' : 'asc';
        } else {
            $state['sortBy'] = $by;
            $state['sortDir'] = 'asc';
            $state['sorted'] = true;
        }
        $this->memberState[$dn] = $state;
        $this->sortCurrentPage($dn);
    }

    /**
     * Load current cursor page for members and apply sorting.
     */
    private function loadMembersCurrentCursor(string $dn): void
    {
        try {
            $state = $this->memberState[$dn];
            $sortBy = $state['sortBy'] ?? null;
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

    /**
     * Sort currently loaded members page.
     */
    private function sortCurrentPage(string $dn): void
    {
        $st = $this->memberState[$dn] ?? null;
        if (! $st || empty($st['page']['rows'])) {
            return;
        }
        if (! ($st['sorted'] ?? false) || ! ($st['sortBy'] ?? null)) {
            return;
        }

        $by = $st['sortBy'];
        $dir = $st['sortDir'] ?? 'asc';

        $rows = $st['page']['rows'];
        usort($rows, function ($a, $b) use ($by, $dir) {
            $aval = (string) ($a[$by] ?? '');
            $bval = (string) ($b[$by] ?? '');
            $cmp = strnatcasecmp($aval, $bval);
            if ($cmp !== 0) {
                return $dir === 'asc' ? $cmp : -$cmp;
            }

            foreach (['surname', 'givenname', 'title', 'pid'] as $k) {
                if ($k === $by) {
                    continue;
                }
                $ta = (string) ($a[$k] ?? '');
                $tb = (string) ($b[$k] ?? '');
                $t = strnatcasecmp($ta, $tb);
                if ($t !== 0) {
                    return $t;
                }
            }

            return 0;
        });

        $this->memberState[$dn]['page']['rows'] = $rows;
        $this->memberState[$dn]['rows'] = $rows;
    }

    /**
     * Fetch full members page; supports broad filter, then optional strict last-4 for digits.
     */
    private function fetchMembersPage(string $dn, int $pageSize, string $query = '', ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        $filter = $this->buildFilter($dn, $query);
        $attrs = ['uid', 'givenname', 'sn', 'title', 'telephonenumber', 'telephoneNumber', 'BAPK-telefon', 'mobile'];

        $cacheKey = 'ldap.members.page.full.'.md5(json_encode([
            'dn' => mb_strtolower($dn),
            'filter' => $filter,
            'size' => $pageSize,
            'sort' => [$sortBy, $sortDir],
        ]));

        return Cache::remember($cacheKey, now()->addMinutes($this->pageCacheTtlMinutes), function () use ($filter, $attrs, $query) {
            $q = User::query()->select($attrs)->rawFilter($filter);
            $results = $q->limit($this->memberPageSize)->get();

            $rows = [];
            foreach ($results as $u) {
                $pid = $u->getFirstAttribute('uid') ?? '';
                $given = $u->getFirstAttribute('givenname') ?? '';
                $sur = $u->getFirstAttribute('sn') ?? '';
                $title = $u->getFirstAttribute('title') ?? '';
                $rawTel = $u->getFirstAttribute('telephonenumber')
                    ?? $u->getFirstAttribute('telephoneNumber')
                    ?? $u->getFirstAttribute('BAPK-telefon')
                    ?? $u->getFirstAttribute('mobile')
                    ?? '';
                $shortTel = $this->normalizeShortTel($rawTel);

                if ($query !== '' && ctype_digit($query) && strlen($query) === 4 && $shortTel !== $query) {
                    continue;
                }

                if ($pid === '' && $given === '' && $sur === '' && $title === '' && $shortTel === '') {
                    continue;
                }

                $rows[] = [
                    'pid' => $pid !== '' ? $pid : '—',
                    'title' => $title,
                    'givenname' => $given,
                    'surname' => $sur,
                    'tel' => $shortTel,
                ];
            }

            return ['rows' => $rows];
        });
    }

    /**
     * Build LDAP filter for members modal (group DN + optional search).
     */
    private function buildFilter(string $dn, string $query): string
    {
        $base = '(groupmembership='.self::rfc4515Escape($dn, true).')';
        $q = trim($query);
        if ($q === '') {
            return $base;
        }

        $t = self::withWildcards(self::rfc4515Escape($q, true));
        $or = '(|'
            .'(uid='.$t.')'
            .'(givenname='.$t.')'
            .'(sn='.$t.')'
            .'(title='.$t.')'
            .'(telephonenumber='.$t.')'
            .'(telephoneNumber='.$t.')'
            .'(BAPK-telefon='.$t.')'
            .'(mobile='.$t.')'
            .')';

        return '(&'.$base.$or.')';
    }

    /**
     * Open compare modal with a base PID.
     */
    public function openCompare(string $basePid): void
    {
        $this->resetCompare();
        $this->compareBasePid = $this->normalizePid($basePid);
        $this->compareView = 'common';
        $this->modal('compare')->show();
    }

    /**
     * Reset compare state.
     */
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

    /**
     * Run groups compare between base and other PID.
     */
    public function runCompare(): void
    {
        $this->compareError = null;
        $this->compareGroups = null;
        $this->compareBaseInfo = [];
        $this->compareOtherInfo = [];

        if (! $this->compareBasePid) {
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

    /**
     * Switch compare view tab.
     */
    public function setCompareView(string $view): void
    {
        if (! in_array($view, ['user1', 'user2', 'common', 'diffs'], true)) {
            return;
        }
        $this->compareView = $view;
    }

    /**
     * Fetch groups and minimal info for a PID.
     */
    private function getUserGroupsAndInfo(string $pid, array &$info): array
    {
        $user = User::query()->where('uid', '=', $pid)->first();
        if (! $user) {
            throw new \RuntimeException("Benutzer $pid nicht gefunden.");
        }

        $info = [
            'pid' => $user->getFirstAttribute('uid') ?? $pid,
            'surname' => $user->getFirstAttribute('sn') ?? '',
            'givenname' => $user->getFirstAttribute('givenname') ?? '',
        ];

        $rawGroups = $user->getAttribute('groupmembership') ?? [];

        return $this->formatGroups(is_array($rawGroups) ? $rawGroups : []);
    }

    /**
     * Reset group modal state.
     */
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

    /**
     * Ensure PID starts with "p".
     */
    private function normalizePid(string $term): string
    {
        $t = strtolower(trim($term));
        if (! str_starts_with($t, 'p')) {
            $t = 'p'.$t;
        }

        return $t;
    }

    /**
     * Keep last 4 digits of any phone-like value.
     */
    private function normalizeShortTel(string $tel): string
    {
        $digits = preg_replace('/\D+/', '', (string) $tel);
        if ($digits === '') {
            return '';
        }

        return substr($digits, -4);
    }

    /**
     * Display-transform LDAP groups.
     */
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

    /**
     * Map display group → DN.
     */
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

    /**
     * RFC4515 escape for LDAP filters. Optionally preserve * as wildcard.
     */
    private static function rfc4515Escape(string $s, bool $preserveAsterisk = true): string
    {
        $s = str_replace(
            ['\\', '(', ')', "\x00"],
            ['\\5c', '\\28', '\\29', '\\00'],
            $s
        );
        if (! $preserveAsterisk) {
            $s = str_replace('*', '\\2a', $s);
        }

        return $s;
    }

    /**
     * Surround with * if user didn’t provide wildcards.
     */
    private static function withWildcards(string $term): string
    {
        return str_contains($term, '*') ? $term : ('*'.$term.'*');
    }

    /**
     * Title case for names in UI.
     */
    private function titleCaseName(array $info): string
    {
        $given = trim((string) ($info['givenname'] ?? ''));
        $sur = trim((string) ($info['surname'] ?? ''));
        $pid = $info['pid'] ?? '—';
        $given = $given !== '' ? (mb_strtoupper(mb_substr($given, 0, 1)).mb_strtolower(mb_substr($given, 1))) : '';
        $sur = $sur !== '' ? (mb_strtoupper(mb_substr($sur, 0, 1)).mb_strtolower(mb_substr($sur, 1))) : '';
        $full = trim($given.' '.$sur);

        return ($full !== '') ? "$full ($pid)" : $pid;
    }

    /**
     * Render Livewire view.
     */
    public function render()
    {
        return view('livewire.ldap.user-search', [
            'titleCaseName' => fn ($info) => $this->titleCaseName($info ?? []),
        ]);
    }
}
