{{-- =========================================
--  WRAPPER + HELPERS
-- ========================================= --}}
<div class="w-[80%] md:w-[70%] mx-auto">
    @php
        $colors = ['green','emerald','teal','cyan','sky','blue','indigo','violet','purple','fuchsia','orange','amber','yellow','lime'];
        $namePid = fn($info) => isset($titleCaseName)
            ? $titleCaseName($info)
            : ((trim(($info['givenname'] ?? '') . ' ' . ($info['surname'] ?? '')) !== '')
                ? trim(($info['givenname'] ?? '') . ' ' . ($info['surname'] ?? '')) . ' (' . ($info['pid'] ?? '—') . ')'
                : ($info['pid'] ?? '—'));
        $groupClasses = 'w-fit max-w-sm truncate hover:whitespace-normal focus:whitespace-normal break-words';
        $b64 = fn (?string $s) => $s === null ? '' : base64_encode($s);
    @endphp

    {{-- =========================================
    --  SEARCH CARD
    -- ========================================= --}}
    <flux:card class="pb-4">
        <div class="flex flex-col items-center gap-2">
            <flux:icon.square-user-round class="size-12"/>
            <p>User Suchen</p>

            <div class="flex justify-center w-full md:w-3/4 lg:w-2/3">
                <flux:input.group>
                    <flux:select wire:model.live="searchAttribute" placeholder="Attribute..." required class="max-w-fit">
                        <flux:select.option value="PID">PID</flux:select.option>
                        <flux:select.option value="Nachname">Nachname</flux:select.option>
                        <flux:select.option value="Vollst. Name">Vollst. Name</flux:select.option>
                        <flux:select.option value="Titel">Stellenzeichen</flux:select.option>
                        <flux:select.option value="Telefon">Telefon</flux:select.option>
                    </flux:select>

                    @if ($searchAttribute === 'PID')
                        <flux:input.group>
                            <flux:input.group.prefix class="rounded-l-none border-l-0">p</flux:input.group.prefix>
                            <flux:input wire:model.defer="searchTerm" wire:keydown.enter="search" placeholder="12345" inputmode="numeric" pattern="[0-9]*"/>
                        </flux:input.group>
                    @elseif ($searchAttribute === 'Telefon')
                        <flux:input
                            wire:model.defer="searchTerm"
                            wire:keydown.enter="search"
                            placeholder="z. B. 1234"
                            maxlength="4"
                            inputmode="numeric"
                            pattern="[0-9]{4}"
                        />
                    @else
                        <div class="relative w-full">
                            <flux:input
                                wire:model.defer="searchTerm"
                                wire:keydown.enter="search"
                                placeholder="{{ $searchAttribute === 'Titel' ? 'z. B. FM IKT 1*' : 'Suchbegriff eingeben… (Wildcards * erlaubt)' }}"
                            />
                            <button type="button"
                                    @click="$wire.set('searchTerm','')"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex items-center justify-center rounded px-2 py-1 text-base leading-none text-gray-500 hover:text-black">
                                ×
                            </button>
                        </div>
                    @endif

                    <flux:button
                        variant="primary"
                        color="green"
                        wire:click="search"
                        type="button"
                        class="cursor-pointer"
                    >
                        Suchen
                    </flux:button>
                </flux:input.group>
            </div>

            @if ($error)
                <p class="text-red-600 text-sm mt-2">{{ $error }}</p>
            @endif
        </div>

        {{-- =========================================
        --  RESULTS TABLE (SYNCED H SCROLL, NON-STICKY AKTIONEN)
        -- ========================================= --}}
        @if ($searchResults && $searchResults->count() > 0)
            <div class="mt-6" x-data="userTableCopy()">
                <div class="flex items-center justify-between gap-3">
                    <flux:heading size="sm" class="text-gray-700 dark:text-gray-100">Ergebnis</flux:heading>
                    <div class="flex items-center gap-3 shrink-0">
                        <span role="button" tabindex="0"
                              title="Tabelle kopieren"
                              @click="copyTable($refs.userHead, $refs.userBody)"
                              :data-copyable-copied="copied ? '' : null"
                              class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-xs bg-white/90 dark:bg-gray-800/90 hover:bg-white dark:hover:bg-gray-800 border border-gray-300 dark:border-gray-700 cursor-pointer text-gray-700 hover:text-black shadow-sm">
                            <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                            <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                            <span class="whitespace-nowrap">Tabelle kopieren</span>
                        </span>

                        <div class="flex items-center gap-2">
                            <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Kopier-Modus</flux:text>
                            <flux:switch @change="showCopy = $event.target.checked"/>
                        </div>
                    </div>
                </div>

                <div class="relative shadow-md sm:rounded-lg border border-gray-300 dark:border-gray-700">
                    {{-- Header --}}
                    <div class="overflow-x-hidden" x-ref="userHeadWrap">
                        <table class="min-w-[80rem] w-full table-fixed text-sm text-left text-gray-600 dark:text-gray-200 bg-gray-50 dark:bg-gray-900/20">
                            <colgroup>
                                <col class="w-[10rem]">
                                <col class="w-[12rem]">
                                <col class="w-[12rem]">
                                <col class="w-[16rem]">
                                <col class="w-[12rem]">
                                <col class="w-[20rem]"> {{-- Email --}}
                                <col class="w-[8rem]">  {{-- Aktionen --}}
                            </colgroup>
                            <thead x-ref="userHead" class="text-xs uppercase bg-gray-100 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer" wire:click="setUserSort('pid')">
                                                PID
                                                @if($userSorted && $userSortBy==='pid')
                                                    @if($userSortDir==='asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(0, $refs.userHead, $refs.userBody)"
                                                  :data-copyable-copied="colCopied[0] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>

                                    <th class="px-4 py-3">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer" wire:click="setUserSort('surname')">
                                                Nachname
                                                @if($userSorted && $userSortBy==='surname')
                                                    @if($userSortDir==='asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(1, $refs.userHead, $refs.userBody)"
                                                  :data-copyable-copied="colCopied[1] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>

                                    <th class="px-4 py-3">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer" wire:click="setUserSort('givenname')">
                                                Vorname
                                                @if($userSorted && $userSortBy==='givenname')
                                                    @if($userSortDir==='asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(2, $refs.userHead, $refs.userBody)"
                                                  :data-copyable-copied="colCopied[2] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>

                                    <th class="px-4 py-3">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer" wire:click="setUserSort('title')">
                                                Stellenzeichen
                                                @if($userSorted && $userSortBy==='title')
                                                    @if($userSortDir==='asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(3, $refs.userHead, $refs.userBody)"
                                                  :data-copyable-copied="colCopied[3] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>

                                    <th class="px-4 py-3">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer" wire:click="setUserSort('tel')">
                                                Telefon
                                                @if($userSorted && $userSortBy==='tel')
                                                    @if($userSortDir==='asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(4, $refs.userHead, $refs.userBody)"
                                                  :data-copyable-copied="colCopied[4] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>

                                    {{-- ✅ Email header added --}}
                                    <th class="px-4 py-3">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer" wire:click="setUserSort('email')">
                                                Email
                                                @if($userSorted && $userSortBy==='email')
                                                    @if($userSortDir==='asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(5, $refs.userHead, $refs.userBody)"
                                                  :data-copyable-copied="colCopied[5] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>

                                    <th class="px-4 py-3">
                                        <div class="inline-flex items-center gap-1">
                                            <span>Aktionen</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    {{-- Body --}}
                    <div class="max-h-[60vh] overflow-y-auto overflow-x-auto" x-ref="userBodyWrap" @scroll="$refs.userHeadWrap.scrollLeft = $event.target.scrollLeft">
                        <table class="min-w-[80rem] w-full table-fixed text-sm text-left text-gray-600 dark:text-gray-200 bg-white dark:bg-gray-900/20">
                            <colgroup>
                                <col class="w-[10rem]">
                                <col class="w-[12rem]">
                                <col class="w-[12rem]">
                                <col class="w-[16rem]">
                                <col class="w-[12rem]">
                                <col class="w-[20rem]"> {{-- Email --}}
                                <col class="w-[8rem]">  {{-- Aktionen --}}
                            </colgroup>
                            <tbody x-ref="userBody" class="bg-white dark:bg-gray-800/60">
                            @foreach ($searchResults as $idx => $user)
                                @php $tel = $user['tel'] ?? ''; @endphp
                                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $loop->odd ? 'bg-gray-50 dark:bg-gray-800/40' : 'bg-white dark:bg-gray-800/70' }}"
                                    data-user-row="rk-{{ $idx }}-{{ $user['pid'] }}">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100 truncate">{{ $user['pid'] }}</td>
                                    <td class="px-4 py-3 truncate">{{ $user['surname'] ?? '–' }}</td>
                                    <td class="px-4 py-3 truncate">{{ $user['givenname'] ?? '–' }}</td>
                                    <td class="px-4 py-3 truncate">{{ $user['title'] ?? '–' }}</td>
                                    <td class="px-4 py-3 truncate">{{ $tel !== '' ? $tel : '–' }}</td>
                                    <td class="px-4 py-3">
                                        @if(!empty($user['email']))
                                            <flux:badge variant="pill" class="mt-1" color="green">{{ $user['email'] }}</flux:badge>
                                        @else
                                            <flux:text variant="subtle">nicht vorhanden</flux:text>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 whitespace-nowrap no-copy">
                                        <div class="flex items-center justify-end gap-1">
                                            <flux:button size="xs" variant="primary" color="green" class="cursor-pointer"
                                                         wire:click="loadGroupsAndInfo('{{ $user['pid'] }}')">
                                                Anzeigen
                                            </flux:button>
                                            <flux:button size="xs" variant="primary" color="blue" class="cursor-pointer"
                                                         wire:click="openCompare('{{ $user['pid'] }}')">
                                                Vergleichen
                                            </flux:button>
                                            <span role="button" tabindex="0" title="Zeile kopieren"
                                                  @click="copyRowByKey('rk-{{ $idx }}-{{ $user['pid'] }}', $refs.userHead, $refs.userBody)"
                                                  :data-copyable-copied="rowCopied['rk-{{ $idx }}-{{ $user['pid'] }}'] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center items-center cursor-pointer text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </flux:card>

    {{-- =========================================
    --  GROUPS MODAL
    -- ========================================= --}}
    <flux:modal name="groups" class="w-content max-w-content max-h-full" :dismissible="false">
        @if ($selectedUserInfo)
            <flux:heading class="flex justify-center">{{ $selectedUserInfo['pid'] }}</flux:heading>
            <flux:text class="mt-2">Nachname: {{ $selectedUserInfo['surname'] ?? '—' }}</flux:text>
            <flux:text class="mt-2">Vorname: {{ $selectedUserInfo['givenname'] ?? '—' }}</flux:text>
            <flux:text class="mt-2">Titel: {{ $selectedUserInfo['title'] ?? '—' }}</flux:text>
            <flux:text class="mt-2">Info: {{ $selectedUserInfo['info'] ?? '—' }}</flux:text>
            <flux:text class="mt-2">
                Letzter Login:
                @php
                    $lastLogin = $selectedUserInfo['lastLogin'] ?? null;
                    try { $loginTime = $lastLogin ? \Carbon\Carbon::parse($lastLogin)->setTimezone('Europe/Berlin') : null; }
                    catch (\Exception $e) { $loginTime = null; }
                @endphp
                {{ $loginTime ? $loginTime->format('d.m.Y H:i') . ' (' . $loginTime->diffForHumans() . ')' : '--' }}
            </flux:text>
            <flux:text class="mt-2">Kontext: {{ $selectedUserInfo['context'] ?? '—' }}</flux:text>
        @endif

        <flux:separator class="mt-3 mb-3">
            Gruppenzugehörigkeiten
            @if($selectedUserGroups != null)
                <flux:badge size="sm" color="lime">{{ count($selectedUserGroups) }}</flux:badge>
            @endif
        </flux:separator>

        @php $groupDn = fn($g) => $selectedUserGroupMap[$g] ?? null; @endphp

        @if ($selectedUserGroups !== null)
            @if(count($selectedUserGroups) > 0)
                <flux:div copyable class="grid gap-1 mt-2 min-w-fit">
                    @foreach ($selectedUserGroups as $index => $group)
                        @php $dn = $groupDn($group); $dn64 = $b64($dn); @endphp
                        <span class="inline-flex items-center gap-2">
                            <flux:button size="xs" variant="ghost" class="p-0" title="Mitglieder anzeigen" wire:click="openMembersModal('{{ $dn64 }}')">
                                <flux:icon.user-group class="size-4 shrink-0"/>
                            </flux:button>
                            <flux:badge2 copyable variant="pill" color="{{ $colors[$index % count($colors)] }}" class="{{ $groupClasses }}" title="{{ $group }}">
                                {{ $group }}
                            </flux:badge2>
                        </span>
                    @endforeach
                </flux:div>
            @else
                <p>Keine Gruppen gefunden.</p>
            @endif
        @endif
    </flux:modal>

    {{-- =========================================
    --  GROUP MEMBERS MODAL (SYNCED H SCROLL, NON-STICKY AKTIONEN)
    -- ========================================= --}}
    @php
        $dn = $memberListForDn ?? null;
        $dnKey = $dn ? substr(md5($dn),0,10) : 'none';
        $state = $dn ? ($memberState[$dn] ?? null) : null;
        $page  = $state['page'] ?? null;
        $displayGroupName = null;
        if ($dn) {
            $found = array_search($dn, $selectedUserGroupMap ?? [], true);
            $displayGroupName = $found !== false ? $found : $dn;
        }
        $isSorted = $state['sorted'] ?? false;
        $sortBy = $state['sortBy'] ?? null;
        $sortDir = $state['sortDir'] ?? 'asc';
        $memberCount = is_array($page) && isset($page['rows']) ? count($page['rows']) : 0;
    @endphp

    <flux:modal name="groupMembers" class="w-[92vw] max-w-4xl" :dismissible="true">
        <div class="space-y-3" wire:key="gm-wrap-{{ $dnKey }}-{{ $gmNonce }}" x-data="gmCopyTable()">
            <div class="flex items-center justify-between gap-3 pr-12">
                <div class="flex items-center gap-2">
                    <flux:heading size="lg" class="flex items-center gap-2">
                        Gruppenmitglieder
                        @if($displayGroupName)
                            <span class="text-gray-500 dark:text-gray-400 font-normal">— {{ $displayGroupName }}</span>
                        @endif
                        <flux:badge size="sm" color="sky">{{ $memberCount }}</flux:badge>
                    </flux:heading>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <span role="button" tabindex="0"
                          title="Tabelle kopieren"
                          @click="copyTable($refs.gmHead, $refs.gmBody)"
                          :data-copyable-copied="copied ? '' : null"
                          class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs bg-white/80 dark:bg-gray-800/80 hover:bg-white dark:hover:bg-gray-800 border border-gray-300 dark:border-gray-700 cursor-pointer text-gray-700 hover:text-black transition-colors">
                        <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                        <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                        <span class="whitespace-nowrap">Tabelle kopieren</span>
                    </span>

                    <div class="flex items-center gap-2">
                        <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Kopier-Modus</flux:text>
                        <flux:switch @change="showCopy = $event.target.checked"/>
                    </div>
                </div>
            </div>

            <div class="mb-2 flex items-center gap-2">
                <div class="relative w-full">
                    <flux:input
                        wire:model.live.debounce.300ms="memberSearch"
                        wire:key="gm-search-{{ $dnKey }}"
                        x-ref="memberSearch"
                        x-init="$nextTick(()=> $refs.memberSearch?.focus())"
                        placeholder="Suchen: PID, Stellenzeichen, Vorname, Nachname, Telefon (4-stellig)"
                        maxlength="4"
                        inputmode="numeric"
                        pattern="[0-9]{0,4}"
                    />
                    <button type="button"
                            @click="$wire.set('memberSearch',''); $nextTick(()=> $refs.memberSearch?.focus())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex items-center justify-center rounded px-2 py-1 text-base leading-none text-gray-500 hover:text-black">
                        ×
                    </button>
                </div>
            </div>

            @if (!$dn)
                <flux:text variant="subtle">Keine Gruppe ausgewählt.</flux:text>
            @elseif (!$page)
                <flux:text variant="subtle">Lade Mitglieder…</flux:text>
            @elseif (($page['rows'] ?? []) === [])
                <flux:text variant="subtle">Keine Mitglieder gefunden.</flux:text>
            @else
                <div class="relative shadow-md sm:rounded-lg border border-gray-300 dark:border-gray-700">
                    {{-- Header --}}
                    <div class="overflow-x-hidden" x-ref="gmHeadWrap">
                        <table class="min-w-[72rem] w-full table-fixed text-sm">
                            <colgroup>
                                <col class="w-[12rem]">
                                <col class="w-[16rem]">
                                <col class="w-[12rem]">
                                <col class="w-[12rem]">
                                <col class="w-[12rem]">
                                <col class="w-[8rem]"> {{-- Aktionen --}}
                            </colgroup>
                            <thead x-ref="gmHead" class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-100">
                                <tr>
                                    <th class="!pl-10 pr-2 py-2 text-left">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                                    wire:click="setMemberSort('{{ base64_encode($dn) }}','pid')"
                                                    wire:target="setMemberSort"
                                                    wire:loading.attr="disabled">
                                                PID
                                                @if($isSorted && $sortBy === 'pid')
                                                    @if($sortDir === 'asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(0, $refs.gmHead, $refs.gmBody)"
                                                  :data-copyable-copied="colCopied[0] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="px-2 py-2 text-left">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                                    wire:click="setMemberSort('{{ base64_encode($dn) }}','title')">
                                                Stellenzeichen
                                                @if($isSorted && $sortBy === 'title')
                                                    @if($sortDir === 'asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(1, $refs.gmHead, $refs.gmBody)"
                                                  :data-copyable-copied="colCopied[1] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="px-2 py-2 text-left">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                                    wire:click="setMemberSort('{{ base64_encode($dn) }}','givenname')">
                                                Vorname
                                                @if($isSorted && $sortBy === 'givenname')
                                                    @if($sortDir === 'asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(2, $refs.gmHead, $refs.gmBody)"
                                                  :data-copyable-copied="colCopied[2] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="px-2 py-2 text-left">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                                    wire:click="setMemberSort('{{ base64_encode($dn) }}','surname')">
                                                Nachname
                                                @if($isSorted && $sortBy === 'surname')
                                                    @if($sortDir === 'asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(3, $refs.gmHead, $refs.gmBody)"
                                                  :data-copyable-copied="colCopied[3] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="px-2 py-2 text-left">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                                    wire:click="setMemberSort('{{ base64_encode($dn) }}','tel')">
                                                Telefon
                                                @if($isSorted && $sortBy === 'tel')
                                                    @if($sortDir === 'asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(4, $refs.gmHead, $refs.gmBody)"
                                                  :data-copyable-copied="colCopied[4] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="px-2 py-2 text-left no-copy">
                                        <span class="text-xs text-gray-500">Aktionen</span>
                                    </th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    {{-- Body --}}
                    <div class="max-h-[65vh] overflow-y-auto overflow-x-auto" x-ref="gmBodyWrap" @scroll="$refs.gmHeadWrap.scrollLeft = $event.target.scrollLeft">
                        <table class="min-w-[72rem] w-full table-fixed text-sm" x-ref="gmTable">
                            <colgroup>
                                <col class="w-[12rem]">
                                <col class="w-[16rem]">
                                <col class="w-[12rem]">
                                <col class="w-[12rem]">
                                <col class="w-[12rem]">
                                <col class="w-[8rem]"> {{-- Aktionen --}}
                            </colgroup>
                            <tbody x-ref="gmBody" class="bg-white dark:bg-gray-800/60">
                            @foreach (($page['rows'] ?? []) as $i => $row)
                                @php
                                    $pid = $row['pid'] ?? '—';
                                    $title = ($row['title'] ?? '') !== '' ? $row['title'] : '—';
                                    $v = $row['givenname'] ?: '—';
                                    $n = $row['surname'] ?: '—';
                                    $tel = trim((string)($row['tel'] ?? ''));
                                    $tshow = $tel !== '' ? $tel : '—';
                                    $rk = $dnKey.'-'.($row['pid'] ?? ('i'.$i));
                                @endphp
                                <tr class="{{ $loop->odd ? 'bg-gray-50 dark:bg-gray-800/80' : 'bg-gray-100 dark:bg-gray-800/55' }} hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                                    wire:key="gm-row-{{ $dnKey }}-{{ $row['pid'] ?? ('i'.$i) }}"
                                    data-gm-row="{{ $rk }}">
                                    <td class="!pl-10 pr-4 py-2 whitespace-nowrap">
                                        <span x-data="{label: @js($pid)}" x-transition.opacity>
                                            <span @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                                  class="cursor-pointer select-text" x-text="label"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span x-data="{label: @js($title)}" x-transition.opacity>
                                            <span @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                                  class="cursor-pointer select-text" x-text="label"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span x-data="{label: @js($v)}" x-transition.opacity>
                                            <span @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                                  class="cursor-pointer select-text" x-text="label"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span x-data="{label: @js($n)}" x-transition.opacity>
                                            <span @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                                  class="cursor-pointer select-text" x-text="label"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span x-data="{label: @js($tshow)}" x-transition.opacity>
                                            <span @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                                  class="cursor-pointer select-text" x-text="label"></span>
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap no-copy">
                                        <span role="button" tabindex="0" title="Zeile kopieren"
                                              @click="copyRowByKey('{{ $rk }}', $refs.gmHead, $refs.gmBody)"
                                              :data-copyable-copied="rowCopied['{{ $rk }}'] ? '' : null"
                                              :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                              class="inline-flex w-5 justify-center items-center cursor-pointer text-gray-500/80 hover:text-black transition-colors">
                                            <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                            <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>
</div>

{{-- =========================================
--  ALPINE HELPERS (COPY LOGIC)
-- ========================================= --}}
<script>
function _clean(s){ return String(s ?? '').replace(/\s+/g,' ').trim() }
function _visCells(row){ return [...row.querySelectorAll('th,td')].filter(c=>!c.classList.contains('no-copy')) }
function _cells(row){ return _visCells(row).map(c=>_clean(c.textContent)) }
function _rows(tbody){ return [...(tbody?.querySelectorAll('tr') ?? [])].filter(r=>r.offsetParent!==null) }
function _widths(header, rows){ const all=[header,...rows]; return header.map((_,i)=>Math.max(...all.map(r=>(r[i]??'').length))) }
function _smartJoin(row, widths){ return row.map((c,i)=>{ const len=(c||'').length, gap=(widths[i]||len)-len, tabs=gap>8?2:1; return (c||'')+'\t'.repeat(tabs) }).join('') }

function userTableCopy(){
    return {
        showCopy:false, copied:false, colCopied:{}, rowCopied:{},
        async copyTable(headEl, bodyEl){
            if(!headEl || !bodyEl) return
            const head=_visCells(headEl.querySelector('tr')).map(c=>_clean(c.textContent))
            const rows=_rows(bodyEl).map(r=>_cells(r))
            const w=_widths(head, rows), out=[_smartJoin(head,w)]
            for(let i=0;i<rows.length;i++){ out.push(_smartJoin(rows[i],w)); if(i%200===199) await new Promise(requestAnimationFrame) }
            await navigator.clipboard.writeText(out.join('\n')); this.copied=true; setTimeout(()=>this.copied=false,1200)
        },
        async copyRowByKey(key, headEl, bodyEl){
            if(!headEl || !bodyEl) return
            const tr=document.querySelector(`[data-user-row='${key}']`); if(!tr) return
            const head=_visCells(headEl.querySelector('tr')).map(c=>_clean(c.textContent))
            const rows=_rows(bodyEl).map(r=>_cells(r))
            const w=_widths(head, rows), text=_smartJoin(_cells(tr),w)
            await navigator.clipboard.writeText(text); this.rowCopied[key]=true; setTimeout(()=>this.rowCopied[key]=false,1200)
        },
        async copyColumnByIndex(idx, headEl, bodyEl){
            if(!headEl || !bodyEl) return
            const headCells=_visCells(headEl.querySelector('tr'))
            const header=headCells[idx]?_clean(headCells[idx].textContent):''
            const rs=_rows(bodyEl), out=[header]
            for(let i=0;i<rs.length;i++){ const cs=_cells(rs[i]); out.push(cs[idx] ?? ''); if(i%300===299) await new Promise(requestAnimationFrame) }
            await navigator.clipboard.writeText(out.join('\n')); this.colCopied[idx]=true; setTimeout(()=>this.colCopied[idx]=false,1200)
        }
    }
}

function gmCopyTable(){
    return {
        showCopy:false, copied:false, colCopied:{}, rowCopied:{},
        async copyTable(headEl, bodyEl){
            if(!headEl || !bodyEl) return
            const headRow = headEl.querySelector('tr'); if(!headRow) return
            const head=_visCells(headRow).map(c=>_clean(c.textContent))
            const rs=_rows(bodyEl).map(r=>_cells(r))
            const w=_widths(head, rs), out=[_smartJoin(head,w)]
            for(let i=0;i<rs.length;i++){ out.push(_smartJoin(rs[i],w)); if(i%200===199) await new Promise(requestAnimationFrame) }
            await navigator.clipboard.writeText(out.join('\n')); this.copied=true; setTimeout(()=>this.copied=false,1200)
        },
        async copyRowByKey(key, headEl, bodyEl){
            if(!headEl || !bodyEl) return
            const tr=bodyEl.querySelector(`[data-gm-row='${key}']`); if(!tr) return
            const headRow = headEl.querySelector('tr'); if(!headRow) return
            const head=_visCells(headRow).map(c=>_clean(c.textContent))
            const rs=_rows(bodyEl).map(r=>_cells(r))
            const w=_widths(head, rs), text=_smartJoin(_cells(tr), w)
            await navigator.clipboard.writeText(text); this.rowCopied[key]=true; setTimeout(()=>this.rowCopied[key]=false,1200)
        },
        async copyColumnByIndex(idx, headEl, bodyEl){
            if(!headEl || !bodyEl) return
            const headRow=headEl.querySelector('tr'); if(!headRow) return
            const headCells=_visCells(headRow)
            const header=headCells[idx]?_clean(headCells[idx].textContent):''
            const bodyRows=_rows(bodyEl)
            const out=[header]
            for(let i=0;i<bodyRows.length;i++){
                const cs=_cells(bodyRows[i]); out.push(cs[idx] ?? '')
                if(i%300===299) await new Promise(requestAnimationFrame)
            }
            await navigator.clipboard.writeText(out.join('\n')); this.colCopied[idx]=true; setTimeout(()=>this.colCopied[idx]=false,1200)
        }
    }
}
</script>
