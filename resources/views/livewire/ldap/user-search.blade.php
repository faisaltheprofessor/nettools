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
                    <flux:select wire:model.live="searchAttribute" placeholder="Attribute..." required
                                 class="max-w-fit">
                        <flux:select.option value="PID">PID</flux:select.option>
                        <flux:select.option value="Nachname">Nachname</flux:select.option>
                        <flux:select.option value="Vollst. Name">Vollst. Name</flux:select.option>
                        <flux:select.option value="Titel">Stellenzeichen</flux:select.option>
                        <flux:select.option value="Telefon">Telefon</flux:select.option>
                    </flux:select>

                    @if ($searchAttribute === 'PID')
                        <flux:input.group>
                            <flux:input.group.prefix class="rounded-l-none border-l-0">p</flux:input.group.prefix>
                            <flux:input wire:model.defer="searchTerm" wire:keydown.enter="search"
                                        placeholder="12345 oder p12345"/>
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
                                placeholder="{{ $searchAttribute === 'Titel' ? 'z. B. FM IKT 1*' : 'Suchbegriff eingeben…' }}"
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
        --  RESULTS TABLE
        -- ========================================= --}}
        @if ($searchResults && $searchResults->count() > 0)
            <div class="mt-6" x-data="userTableCopy()">
    <div class="flex items-center justify-between gap-3">
        <flux:heading size="sm" class="text-gray-700 dark:text-gray-100">Ergebnis</flux:heading>
        <div class="flex items-center gap-3 shrink-0 mb-3">
            <span role="button" tabindex="0"
                title="Tabelle kopieren"
                @click="copyTable($refs.userHead, $refs.userBody)"
                :data-copyable-copied="copied ? '' : null"
                class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-xs bg-white/90 dark:bg-gray-800/90 hover:bg-white dark:hover:bg-gray-800 border border-gray-300 dark:border-gray-700 cursor-pointer text-gray-700 dark:text-white hover:text-black shadow-sm">
                <flux:icon.clipboard-document-check variant="mini"
                    class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                <flux:icon.clipboard-document variant="mini"
                    class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                <span class="whitespace-nowrap">Tabelle kopieren</span>
            </span>

            <span role="button" tabindex="0"
                title="Als Excel exportieren"
                @click="exportExcel($refs.userHead, $refs.userBody, 'benutzer-liste.xls')"
                class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-xs bg-white/90 dark:bg-gray-800/90 hover:bg-white dark:hover:bg-gray-800 border border-gray-300 dark:border-gray-700 cursor-pointer text-gray-700 dark:text-white hover:text-black shadow-sm">
<flux:icon.table-cells variant="mini" class="size-4"/>
<span class="whitespace-nowrap">Excel</span>
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
            <table
                class="min-w-[80rem] w-full table-auto text-sm text-left text-gray-600 dark:text-gray-200 bg-gray-50 dark:bg-gray-900/20">
                <colgroup>
                    <col class="w-[8rem]">
                    <col class="w-[8rem]">
                    <col class="w-[12rem]">
                    <col class="w-[12rem]">
                    <col class="w-[16rem]">
                    <col class="w-[12rem]">
                    <col class="w-[20rem]"> {{-- Email --}}
                    <col class="w-[8rem]"> {{-- Aktionen --}}
                </colgroup>
                <thead x-ref="userHead" class="text-xs uppercase bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3">
                        <div class="inline-flex items-center gap-1">
                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                    wire:click="setUserSort('pid')">
                                PID
                                @if($userSorted && $userSortBy==='pid')
                                    @if($userSortDir==='asc')
                                        <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                    @else
                                        <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                    @endif
                                @endif
                            </button>
                            <span role="button" tabindex="0" title="Spalte kopieren"
                                    @click="copyColumnByIndex(0, $refs.userHead, $refs.userBody)"
                                    :data-copyable-copied="colCopied[0] ? '' : null"
                                    :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                    class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                <flux:icon.clipboard-document-check variant="mini"
                                                                    class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                <flux:icon.clipboard-document variant="mini"
                                                            class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                            </span>
                        </div>
                    </th>

                    {{-- Aktiv Header - Centered and Sortable --}}
                    <th class="px-4 py-3 text-center">
                        <div class="inline-flex items-center gap-1">
                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                    wire:click="setUserSort('active')">
                                Aktiv
                                @if($userSorted && $userSortBy==='active')
                                    @if($userSortDir==='asc')
                                        <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                    @else
                                        <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                    @endif
                                @endif
                            </button>
                             <span role="button" tabindex="0" title="Spalte kopieren"
                                    @click="copyColumnByIndex(1, $refs.userHead, $refs.userBody)"
                                    :data-copyable-copied="colCopied[1] ? '' : null"
                                    :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                    class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                <flux:icon.clipboard-document-check variant="mini"
                                                                    class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                <flux:icon.clipboard-document variant="mini"
                                                            class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                            </span>
                        </div>
                    </th>
                    <th class="px-4 py-3">
                        <div class="inline-flex items-center gap-1">
                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                    wire:click="setUserSort('surname')">
                                Nachname
                                @if($userSorted && $userSortBy==='surname')
                                    @if($userSortDir==='asc')
                                        <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                    @else
                                        <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                    @endif
                                @endif
                            </button>
                            <span role="button" tabindex="0" title="Spalte kopieren"
                                    @click="copyColumnByIndex(2, $refs.userHead, $refs.userBody)"
                                    :data-copyable-copied="colCopied[2] ? '' : null"
                                    :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                    class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                <flux:icon.clipboard-document-check variant="mini"
                                                                    class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                <flux:icon.clipboard-document variant="mini"
                                                            class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                            </span>
                        </div>
                    </th>
                    <th class="px-4 py-3">
                        <div class="inline-flex items-center gap-1">
                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                    wire:click="setUserSort('givenname')">
                                Vorname
                                @if($userSorted && $userSortBy==='givenname')
                                    @if($userSortDir==='asc')
                                        <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                    @else
                                        <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                    @endif
                                @endif
                            </button>
                            <span role="button" tabindex="0" title="Spalte kopieren"
                                    @click="copyColumnByIndex(3, $refs.userHead, $refs.userBody)"
                                    :data-copyable-copied="colCopied[3] ? '' : null"
                                    :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                    class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                <flux:icon.clipboard-document-check variant="mini"
                                                                    class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                <flux:icon.clipboard-document variant="mini"
                                                            class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                            </span>
                        </div>
                    </th>

                    <th class="px-4 py-3">
                        <div class="inline-flex items-center gap-1">
                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                    wire:click="setUserSort('title')">
                                Stellenzeichen
                                @if($userSorted && $userSortBy==='title')
                                    @if($userSortDir==='asc')
                                        <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                    @else
                                        <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                    @endif
                                @endif
                            </button>
                            <span role="button" tabindex="0" title="Spalte kopieren"
                                    @click="copyColumnByIndex(4, $refs.userHead, $refs.userBody)"
                                    :data-copyable-copied="colCopied[4] ? '' : null"
                                    :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                    class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                <flux:icon.clipboard-document-check variant="mini"
                                                                    class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                <flux:icon.clipboard-document variant="mini"
                                                            class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                            </span>
                        </div>
                    </th>

                    <th class="px-4 py-3">
                        <div class="inline-flex items-center gap-1">
                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                    wire:click="setUserSort('tel')">
                                Telefon
                                @if($userSorted && $userSortBy==='tel')
                                    @if($userSortDir==='asc')
                                        <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                    @else
                                        <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                    @endif
                                @endif
                            </button>
                            <span role="button" tabindex="0" title="Spalte kopieren"
                                    @click="copyColumnByIndex(5, $refs.userHead, $refs.userBody)"
                                    :data-copyable-copied="colCopied[5] ? '' : null"
                                    :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                    class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                <flux:icon.clipboard-document-check variant="mini"
                                                                    class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                <flux:icon.clipboard-document variant="mini"
                                                            class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                            </span>
                        </div>
                    </th>

                    {{-- Email header --}}
                    <th class="px-4 py-3">
                        <div class="inline-flex items-center gap-1">
                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                    wire:click="setUserSort('email')">
                                Email
                                @if($userSorted && $userSortBy==='email')
                                    @if($userSortDir==='asc')
                                        <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                    @else
                                        <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                    @endif
                                @endif
                            </button>
                            <span role="button" tabindex="0" title="Spalte kopieren"
                                    @click="copyColumnByIndex(6, $refs.userHead, $refs.userBody)"
                                    :data-copyable-copied="colCopied[6] ? '' : null"
                                    :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                    class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                <flux:icon.clipboard-document-check variant="mini"
                                                                    class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                <flux:icon.clipboard-document variant="mini"
                                                            class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                            </span>
                        </div>
                    </th>

                    {{-- Gruppen header (sticky) --}}
                    <th class="px-4 py-3 sticky right-0 z-20 bg-gray-100 dark:bg-gray-800">
                        <div class="inline-flex items-center gap-1">
                            <span>Gruppen</span>
                        </div>
                    </th>
                </tr>
                </thead>
            </table>
        </div>

        {{-- Body --}}
        <div class="max-h-[60vh] overflow-y-auto overflow-x-auto" x-ref="userBodyWrap"
             @scroll="$refs.userHeadWrap.scrollLeft = $event.target.scrollLeft">
            <table
                class="min-w-[80rem] w-full table-fixed text-sm text-left text-gray-600 dark:text-gray-200 bg-white dark:bg-gray-900/20">
                <colgroup>
                    <col class="w-[8rem]">
                    <col class="w-[8rem]">
                    <col class="w-[12rem]">
                    <col class="w-[12rem]">
                    <col class="w-[16rem]">
                    <col class="w-[12rem]">
                    <col class="w-[20rem]"> {{-- Email --}}
                    <col class="w-[10rem]"> {{-- Aktionen --}}
                </colgroup>
                <tbody x-ref="userBody" class="bg-white dark:bg-gray-800/60">
                @foreach ($searchResults as $idx => $user)
                    @php $tel = $user['tel'] ?? ''; @endphp
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $loop->odd ? 'bg-gray-50 dark:bg-gray-800/40' : 'bg-white dark:bg-gray-800/70' }}"
                        data-user-row="rk-{{ $idx }}-{{ $user['pid'] }}">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100 truncate">{{ $user['pid'] }}</td>
                        {{-- Aktiv Data Cell - Centered and now with px-4 --}}
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100 truncate text-center">@if($user['active']) <flux:icon.check-circle variant="solid" class="text-green-600" /> @else <flux:icon.x-circle variant="solid" class="text-red-600" />@endif</td>
                        {{-- Added px-4 to subsequent cells --}}
                        <td class="px-4 py-3 truncate">{{ $user['surname'] ?? '–' }}</td>
                        <td class="px-4 py-3 truncate">{{ $user['givenname'] ?? '–' }}</td>
                        <td class="px-4 py-3 truncate">{{ $user['title'] ?? '–' }}</td>
                        <td class="px-4 py-3 truncate">{{ $tel !== '' ? $tel : '–' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col space-y-1">
                                @if(!empty($user['email']))
                                    <flux:badge2 copyable variant="pill" color="green">
                                        {{ $user['email'] }}
                                    </flux:badge2>
                                @else
                                    <flux:text variant="subtle">nicht vorhanden</flux:text>
                                @endif

                                @if(!empty($user['external_email']))
                                    <div class="flex items-center space-x-2">
                                        <flux:badge2 copyable variant="pill" color="teal">
                                            {{ $user['external_email'] }}
                                        </flux:badge2>
                                        <flux:tooltip toggleable>
                                            <flux:button icon="information-circle" size="sm" variant="ghost"/>
                                            <flux:tooltip.content class="max-w-[20rem]">extern</flux:tooltip.content>
                                        </flux:tooltip>
                                    </div>
                                @endif
                            </div>
                        </td>


                        {{-- Aktionen cell (sticky) - Padding was already px-2, keeping it consistent with the original --}}
                        <td class="px-2 py-3 whitespace-nowrap no-copy sticky right-0 z-10 bg-inherit [background:inherit] border-l-0 border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="xs" variant="primary" color="green"
                                             class="cursor-pointer"
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
                                    <flux:icon.clipboard-document-check variant="mini"
                                                                        class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                    <flux:icon.clipboard-document variant="mini"
                                                                class="block size-4 [[data-copyable-copied]>&]:hidden"/>
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
    <flux:modal
        name="groups"
        class="w-content max-w-content overflow-hidden" {{-- prevent double scroll --}}
        :dismissible="false"
    >
        {{-- Modal content as a fixed header + scrollable body --}}
        <div class="flex flex-col max-h-[80vh]"> {{-- control modal height --}}
            {{-- FIXED HEADER (user info) --}}
            @if ($selectedUserInfo)
                <div class="shrink-0">
                    <flux:heading class="flex justify-center">
                        {{ $selectedUserInfo['pid'] }}
                    </flux:heading>
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
                </div>
            @endif

            {{-- FIXED SEPARATOR (title + count) --}}
            <div class="shrink-0">
                <flux:separator class="mt-3 mb-3">
                    Gruppenzugehörigkeiten
                    @if($selectedUserGroups != null)
                        <flux:badge size="sm" color="lime">{{ count($selectedUserGroups) }}</flux:badge>
                    @endif
                </flux:separator>
            </div>

            @php $groupDn = fn($g) => $selectedUserGroupMap[$g] ?? null; @endphp


            <div class="min-h-[12rem] max-h-[50vh] overflow-y-auto">
                @if ($selectedUserGroups !== null)
                    @if(count($selectedUserGroups) > 0)
                        <flux:div copyable class="grid gap-1 mt-2 min-w-fit">
                            @foreach ($selectedUserGroups as $index => $group)
                                @php $dn = $groupDn($group); $dn64 = $b64($dn); @endphp
                                <span class="inline-flex items-center gap-2">
                                <flux:button size="xs" variant="ghost" class="p-0" title="Mitglieder anzeigen"
                                             wire:click="openMembersModal('{{ $dn64 }}')">
                                    <flux:icon.user-group class="size-4 shrink-0"/>
                                </flux:button>
                                <flux:badge2 copyable variant="pill" color="{{ $colors[$index % count($colors)] }}"
                                             class="{{ $groupClasses }}" title="{{ $group }}">
                                    {{ $group }}
                                </flux:badge2>
                            </span>
                            @endforeach
                        </flux:div>
                    @else
                        <p>Keine Gruppen gefunden.</p>
                    @endif
                @endif
            </div>
        </div>
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
                            <span class="text-gray-500 dark:text-gray-400 font-normal"> <flux:badge>{{ $displayGroupName }}</flux:badge></span>
                        @endif
                        <flux:badge size="sm" color="sky">{{ $memberCount }}</flux:badge>
                    </flux:heading>
                </div>

                <div class="flex items-center gap-3 shrink-0 mr-1">
                <span role="button" tabindex="0"
                      title="Tabelle kopieren"
                      @click="copyTable($refs.gmTable.querySelector('thead'), $refs.gmTable.querySelector('tbody'))"
                      :data-copyable-copied="copied ? '' : null"
                      class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs bg-white/80 dark:bg-gray-800/80 hover:bg-white dark:hover:bg-gray-800 border border-gray-300 dark:border-gray-700 cursor-pointer text-gray-700 dark:text-white hover:text-black transition-colors">
                    <flux:icon.clipboard-document-check variant="mini"
                                                        class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                    <flux:icon.clipboard-document variant="mini"
                                                  class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                    <span class="whitespace-nowrap">Tabelle kopieren</span>
                </span>

                    <span role="button" tabindex="0"
                          title="Als Excel exportieren"
                          @click="exportExcel($refs.gmTable.querySelector('thead'), $refs.gmTable.querySelector('tbody'), '{{ $displayGroupName }} - gruppenmitglieder.xls')"
                          class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs bg-white/80 dark:bg-gray-800/80 hover:bg-white dark:hover:bg-gray-800 border border-gray-300 dark:border-gray-700 cursor-pointer text-gray-700 dark:text-white hover:text-black transition-colors">
  <flux:icon.table-cells variant="mini" class="size-4"/>
  <span class="whitespace-nowrap">Excel</span>
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
                    {{-- Single scroll container for BOTH header and body so horizontal scroll keeps headers aligned --}}
                    <div class="max-h-[65vh] overflow-auto">
                        <table class="w-full table-auto text-sm" x-ref="gmTable">
                            <colgroup>
                                <col>
                                <col>
                                <col>
                                <col>
                                <col>
                                <col>
                            </colgroup>

                            <thead
                                class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-100 sticky top-0 z-10">
                            <tr>
                                <th class="!pl-10 pr-2 py-2 text-left">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                                wire:click="setMemberSort('{{ base64_encode($dn) }}','pid')"
                                                wire:target="setMemberSort"
                                                wire:loading.attr="disabled">
                                            PID
                                            @if($isSorted && $sortBy === 'pid')
                                                @if($sortDir === 'asc')
                                                    <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                                @else
                                                    <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                                @endif
                                            @endif
                                        </button>
                                        <span role="button" tabindex="0" title="Spalte kopieren"
                                              @click="copyColumnByIndex(0, $refs.gmTable)"
                                              :data-copyable-copied="colCopied[0] ? '' : null"
                                              :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                              class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                            <flux:icon.clipboard-document-check variant="mini"
                                                                                class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                            <flux:icon.clipboard-document variant="mini"
                                                                          class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-2 py-2 text-left">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                                wire:click="setMemberSort('{{ base64_encode($dn) }}','title')">
                                            Stellenzeichen
                                            @if($isSorted && $sortBy === 'title')
                                                @if($sortDir === 'asc')
                                                    <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                                @else
                                                    <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                                @endif
                                            @endif
                                        </button>
                                        <span role="button" tabindex="0" title="Spalte kopieren"
                                              @click="copyColumnByIndex(1, $refs.gmTable)"
                                              :data-copyable-copied="colCopied[1] ? '' : null"
                                              :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                              class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                            <flux:icon.clipboard-document-check variant="mini"
                                                                                class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                            <flux:icon.clipboard-document variant="mini"
                                                                          class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-2 py-2 text-left">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                                wire:click="setMemberSort('{{ base64_encode($dn) }}','givenname')">
                                            Vorname
                                            @if($isSorted && $sortBy === 'givenname')
                                                @if($sortDir === 'asc')
                                                    <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                                @else
                                                    <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                                @endif
                                            @endif
                                        </button>
                                        <span role="button" tabindex="0" title="Spalte kopieren"
                                              @click="copyColumnByIndex(2, $refs.gmTable)"
                                              :data-copyable-copied="colCopied[2] ? '' : null"
                                              :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                              class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini"
                                                                                    class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini"
                                                                              class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                    </div>
                                </th>
                                <th class="px-2 py-2 text-left">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                                wire:click="setMemberSort('{{ base64_encode($dn) }}','surname')">
                                            Nachname
                                            @if($isSorted && $sortBy === 'surname')
                                                @if($sortDir === 'asc')
                                                    <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                                @else
                                                    <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                                @endif
                                            @endif
                                        </button>
                                        <span role="button" tabindex="0" title="Spalte kopieren"
                                              @click="copyColumnByIndex(3, $refs.gmTable)"
                                              :data-copyable-copied="colCopied[3] ? '' : null"
                                              :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                              class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                            <flux:icon.clipboard-document-check variant="mini"
                                                                                class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                            <flux:icon.clipboard-document variant="mini"
                                                                          class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-2 py-2 text-left">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                                wire:click="setMemberSort('{{ base64_encode($dn) }}','tel')">
                                            Telefon
                                            @if($isSorted && $sortBy === 'tel')
                                                @if($sortDir === 'asc')
                                                    <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                                @else
                                                    <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                                @endif
                                            @endif
                                        </button>
                                        <span role="button" tabindex="0" title="Spalte kopieren"
                                              @click="copyColumnByIndex(4, $refs.gmTable)"
                                              :data-copyable-copied="colCopied[4] ? '' : null"
                                              :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                              class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                            <flux:icon.clipboard-document-check variant="mini"
                                                                                class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                            <flux:icon.clipboard-document variant="mini"
                                                                          class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                        </span>
                                    </div>
                                </th>

                                {{-- Aktionen header cell: render only in copy mode; no sticky --}}
                                <th class="px-2 py-2 text-left no-copy"
                                    x-show="showCopy"
                                    x-cloak>
                                    <span class="text-xs text-gray-500">Aktionen</span>
                                </th>
                            </tr>
                            </thead>

                            <tbody class="bg-white dark:bg-gray-800/60">
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
                                        <span
                                            @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                            class="cursor-pointer select-text" x-text="label"></span>
                                    </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                    <span x-data="{label: @js($title)}" x-transition.opacity>
                                        <span
                                            @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                            class="cursor-pointer select-text" x-text="label"></span>
                                    </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                    <span x-data="{label: @js($v)}" x-transition.opacity>
                                        <span
                                            @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                            class="cursor-pointer select-text" x-text="label"></span>
                                    </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                    <span x-data="{label: @js($n)}" x-transition.opacity>
                                        <span
                                            @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                            class="cursor-pointer select-text" x-text="label"></span>
                                    </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                    <span x-data="{label: @js($tshow)}" x-transition.opacity>
                                        <span
                                            @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                            class="cursor-pointer select-text" x-text="label"></span>
                                    </span>
                                    </td>

                                    <td class="px-2 py-2 whitespace-nowrap no-copy"
                                        x-show="showCopy"
                                        x-cloak>
                                    <span role="button" tabindex="0" title="Zeile kopieren"
                                          @click="copyRowByKey('{{ $rk }}', $refs.gmTable)"
                                          :data-copyable-copied="rowCopied['{{ $rk }}'] ? '' : null"
                                          class="inline-flex w-5 justify-center items-center cursor-pointer text-gray-500/80 hover:text-black transition-colors">
                                        <flux:icon.clipboard-document-check variant="mini"
                                                                            class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                        <flux:icon.clipboard-document variant="mini"
                                                                      class="block size-4 [[data-copyable-copied]>&]:hidden"/>
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


    {{-- Compare modal --}}
    <flux:modal name="compare" class="w-[92vw] max-w-5xl" :dismissible="false">
        <div class="space-y-4">
            <div class="flex items-center gap-2">
                <flux:heading size="lg">Gruppenvergleich</flux:heading>
                <flux:badge variant="pill">Basis: {{ $compareBasePid ?? '—' }}</flux:badge>
            </div>

            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <flux:text class="mb-1">Zweite PID</flux:text>
                    <flux:input.group>
                        <flux:input.group.prefix>p</flux:input.group.prefix>
                        <flux:input
                            wire:model.defer="compareOtherPidInput"
                            wire:keydown.enter="runCompare"
                            placeholder="12345 oder p12345"
                            inputmode="numeric"
                            pattern="[0-9]*"
                        />
                    </flux:input.group>
                </div>
                <flux:button variant="primary" color="blue" class="cursor-pointer" wire:click="runCompare">
                    Vergleichen
                </flux:button>
            </div>

            @if ($compareError)
                <p class="text-red-600">{{ $compareError }}</p>
            @endif

            @if ($compareGroups)
                {{-- Filter buttons --}}
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <flux:button
                        size="sm"
                        :variant="$compareView === 'user1' ? 'primary' : 'ghost'"
                        color="lime"
                        class="cursor-pointer"
                        wire:click="setCompareView('user1')">
                        Benutzer&nbsp;1: {{ $namePid($compareBaseInfo) }}
                        <flux:badge size="xs" class="ml-2">{{ $compareGroups['count_first'] }}</flux:badge>
                    </flux:button>

                    <flux:button
                        size="sm"
                        :variant="$compareView === 'user2' ? 'primary' : 'ghost'"
                        color="sky"
                        class="cursor-pointer"
                        wire:click="setCompareView('user2')">
                        Benutzer&nbsp;2: {{ $namePid($compareOtherInfo ?: ['pid' => $compareOtherPid]) }}
                        <flux:badge size="xs" class="ml-2">{{ $compareGroups['count_second'] }}</flux:badge>
                    </flux:button>

                    <flux:button
                        size="sm"
                        :variant="$compareView === 'common' ? 'primary' : 'ghost'"
                        color="violet"
                        class="cursor-pointer"
                        wire:click="setCompareView('common')">
                        Gemeinsam
                        <flux:badge size="xs" class="ml-2">{{ count($compareGroups['common']) }}</flux:badge>
                    </flux:button>

                    <flux:button
                        size="sm"
                        :variant="$compareView === 'diffs' ? 'primary' : 'ghost'"
                        color="orange"
                        class="cursor-pointer"
                        wire:click="setCompareView('diffs')">
                        Unterschiede
                        <flux:badge size="xs" class="ml-2">
                            {{ count($compareGroups['only_first']) + count($compareGroups['only_second']) }}
                        </flux:badge>
                    </flux:button>
                </div>

                {{-- Views --}}
                <div class="mt-4">
                    @if ($compareView === 'user1')
                        <flux:card>
                            <flux:heading size="sm">{{ $namePid($compareBaseInfo) }}</flux:heading>
                            <flux:div copyable class="mt-2 space-x-2 space-y-2">
                                @forelse ($compareGroups['all_first'] as $i => $g)
                                    <flux:badge2 variant="pill"
                                                 color="{{ $colors[$i % count($colors)] }}"
                                                 class="{{ $groupClasses }}"
                                                 title="{{ $g }}">
                                        {{ $g }}
                                    </flux:badge2>
                                @empty
                                    <flux:text variant="subtle">—</flux:text>
                                @endforelse
                            </flux:div>
                        </flux:card>
                    @elseif ($compareView === 'user2')
                        <flux:card>
                            <flux:heading
                                size="sm">{{ $namePid($compareOtherInfo ?: ['pid' => $compareOtherPid]) }}</flux:heading>
                            <flux:div copyable class="mt-2 space-x-2 space-y-2">
                                @forelse ($compareGroups['all_second'] as $i => $g)
                                    <flux:badge2 variant="pill"
                                                 color="{{ $colors[$i % count($colors)] }}"
                                                 class="{{ $groupClasses }}"
                                                 title="{{ $g }}">
                                        {{ $g }}
                                    </flux:badge2>
                                @empty
                                    <flux:text variant="subtle">—</flux:text>
                                @endforelse
                            </flux:div>
                        </flux:card>
                    @elseif ($compareView === 'common')
                        <flux:card>
                            <flux:heading size="sm">Gemeinsame Gruppen</flux:heading>
                            <flux:div copyable class="mt-2 space-x-2 space-y-2">
                                @forelse ($compareGroups['common'] as $i => $g)
                                    <flux:badge2 variant="pill"
                                                 color="{{ $colors[$i % count($colors)] }}"
                                                 class="{{ $groupClasses }}"
                                                 title="{{ $g }}">
                                        {{ $g }}
                                    </flux:badge2>
                                @empty
                                    <flux:text variant="subtle">—</flux:text>
                                @endforelse
                            </flux:div>
                        </flux:card>
                    @elseif ($compareView === 'diffs')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:card>
                                <flux:heading size="sm">{{ $namePid($compareBaseInfo) }}</flux:heading>
                                <flux:div copyable class="mt-2 space-x-2 space-y-2">
                                    @forelse ($compareGroups['only_first'] as $i => $g)
                                        <flux:badge2 variant="pill"
                                                     color="{{ $colors[$i % count($colors)] }}"
                                                     class="{{ $groupClasses }}"
                                                     title="{{ $g }}">
                                            {{ $g }}
                                        </flux:badge2>
                                    @empty
                                        <flux:text variant="subtle">—</flux:text>
                                    @endforelse
                                </flux:div>
                            </flux:card>

                            <flux:card>
                                <flux:heading
                                    size="sm">{{ $namePid($compareOtherInfo ?: ['pid' => $compareOtherPid]) }}</flux:heading>
                                <flux:div copyable class="mt-2 space-x-2 space-y-2">
                                    @forelse ($compareGroups['only_second'] as $i => $g)
                                        <flux:badge2 variant="pill"
                                                     color="{{ $colors[$i % count($colors)] }}"
                                                     class="{{ $groupClasses }}"
                                                     title="{{ $g }}">
                                            {{ $g }}
                                        </flux:badge2>
                                    @empty
                                        <flux:text variant="subtle">—</flux:text>
                                    @endforelse
                                </flux:div>
                            </flux:card>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </flux:modal>
</div>

{{-- =========================================
--  ALPINE HELPERS (COPY LOGIC)
-- ========================================= --}}
<script>
    function _clean(s) {
        return String(s ?? '').replace(/\s+/g, ' ').trim()
    }

    function _visCells(row) {
        return [...row.querySelectorAll('th,td')].filter(c => !c.classList.contains('no-copy'))
    }

    // Use innerText so <br> becomes newlines; Excel supports newlines if quoted
    function _cellText(cell) {
        // normalize Windows/mac/newlines to \n first
        const t = (cell.innerText ?? cell.textContent ?? '').replace(/\r\n/g, '\n').replace(/\r/g, '\n')
        return t.trim()
    }

    // Expand colspans so each row has a consistent number of columns
    function _cellsWithColspan(row) {
        const out = []
        for (const cell of _visCells(row)) {
            const txt = _cellText(cell)
            const span = Number(cell.colSpan || 1)
            out.push(txt)
            for (let i = 1; i < span; i++) out.push('') // fill extra columns for colspan
        }
        return out
    }

    function _rows(tbody) {
        return [...(tbody?.querySelectorAll('tr') ?? [])].filter(r => r.offsetParent !== null)
    }


    function _escapeTSV(s) {
        if (s == null) return ''
        const needsQuote = /[\t"\n]/.test(s)
        if (!needsQuote) return s
        return `"${s.replace(/"/g, '""')}"`
    }

    // Build TSV with CRLF line endings for Excel
    function _toTSV(rows) {
        const maxCols = rows.reduce((m, r) => Math.max(m, r.length), 0)
        const lines = rows.map(r => {
            const padded = r.slice()
            while (padded.length < maxCols) padded.push('')
            return padded.map(_escapeTSV).join('\t')
        })
        return lines.join('\r\n')
    }

    // ===== Components =====
    function userTableCopy() {
        return {
            showCopy: false, copied: false, colCopied: {}, rowCopied: {},

            async copyTable(headEl, bodyEl) {
                if (!headEl || !bodyEl) return
                const headRow = headEl.querySelector('tr');
                if (!headRow) return
                const head = _cellsWithColspan(headRow).map(_clean)

                const bodyRows = _rows(bodyEl).map(r => _cellsWithColspan(r).map(_clean))
                const tsv = _toTSV([head, ...bodyRows])

                await navigator.clipboard.writeText(tsv)
                this.copied = true;
                setTimeout(() => this.copied = false, 1200)
            },

            async copyRowByKey(key, headEl, bodyEl) {
                if (!headEl || !bodyEl) return
                const tr = document.querySelector(`[data-user-row='${key}']`);
                if (!tr) return
                const row = _cellsWithColspan(tr).map(_clean)
                const tsv = _toTSV([row])

                await navigator.clipboard.writeText(tsv)
                this.rowCopied[key] = true;
                setTimeout(() => this.rowCopied[key] = false, 1200)
            },

            async copyColumnByIndex(idx, headEl, bodyEl) {
                if (!headEl || !bodyEl) return
                const headRow = headEl.querySelector('tr');
                if (!headRow) return

                // Build full table first to ensure the same column indexing after colspans
                const head = _cellsWithColspan(headRow).map(_clean)
                const bodyRows = _rows(bodyEl).map(r => _cellsWithColspan(r).map(_clean))
                const maxCols = Math.max(head.length, ...bodyRows.map(r => r.length))

                const get = (arr, i) => (i < arr.length ? arr[i] : '')
                const col = [get(head, idx)]
                for (const r of bodyRows) col.push(get(r, idx))

                const tsv = _toTSV(col.map(c => [c])) // one column TSV
                await navigator.clipboard.writeText(tsv)
                this.colCopied[idx] = true;
                setTimeout(() => this.colCopied[idx] = false, 1200)
            },

            exportExcel(headEl, bodyEl, filename = 'export.xls') {
                if (!headEl || !bodyEl) return;
                const rows = _rowsForExport(headEl, bodyEl);
                _downloadExcelFromRows(rows, filename);
            }
        }
    }


    function gmCopyTable() {
        return {
            showCopy: false, copied: false, colCopied: {}, rowCopied: {},
            async copyTable(headEl, bodyEl) {
                if (!headEl || !bodyEl) return
                const headRow = headEl.querySelector('tr');
                if (!headRow) return
                const head = _cellsWithColspan(headRow).map(_clean)

                const rows = _rows(bodyEl).map(r => _cellsWithColspan(r).map(_clean))
                const tsv = _toTSV([head, ...rows])

                await navigator.clipboard.writeText(tsv)
                this.copied = true;
                setTimeout(() => this.copied = false, 1200)
            },
            async copyRowByKey(key, headEl, bodyEl) {
                if (!headEl || !bodyEl) return
                const tr = bodyEl.querySelector(`[data-gm-row='${key}']`);
                if (!tr) return
                const row = _cellsWithColspan(tr).map(_clean)
                const tsv = _toTSV([row])

                await navigator.clipboard.writeText(tsv)
                this.rowCopied[key] = true;
                setTimeout(() => this.rowCopied[key] = false, 1200)
            },
            async copyColumnByIndex(idx, headEl, bodyEl) {
                if (!headEl || !bodyEl) return
                const headRow = headEl.querySelector('tr');
                if (!headRow) return
                const head = _cellsWithColspan(headRow).map(_clean)
                const bodyRows = _rows(bodyEl).map(r => _cellsWithColspan(r).map(_clean))

                const get = (arr, i) => (i < arr.length ? arr[i] : '')
                const col = [get(head, idx)]
                for (const r of bodyRows) col.push(get(r, idx))

                const tsv = _toTSV(col.map(c => [c]))
                await navigator.clipboard.writeText(tsv)
                this.colCopied[idx] = true;
                setTimeout(() => this.colCopied[idx] = false, 1200)
            },

            exportExcel(headEl, bodyEl, filename = '{{ $displayGroupName }} - gruppenmitglieder.xls') {
                if (!headEl || !bodyEl) return
                const headRow = headEl.querySelector('tr')
                if (!headRow) return

                const head = _cellsWithColspan(headRow).map(_clean)
                const rows = _rows(bodyEl).map(r => _cellsWithColspan(r).map(_clean))
                const tsv = _toTSV([head, ...rows])

                const blob = new Blob([tsv], {type: 'application/vnd.ms-excel'})
                const url = URL.createObjectURL(blob)
                const a = document.createElement('a')
                a.href = url
                a.download = filename
                document.body.appendChild(a)
                a.click()
                a.remove()
                URL.revokeObjectURL(url)
            }
        }
    }
</script>

