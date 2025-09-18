<div class="w-[80%] md:w-[70%] mx-auto">
    @php
        $colors = ['green','emerald','teal','cyan','sky','blue','indigo','violet','purple','fuchsia','orange','amber','yellow','lime'];
        $namePid = fn($info) => isset($titleCaseName)
            ? $titleCaseName($info)
            : ( (trim(($info['givenname'] ?? '') . ' ' . ($info['surname'] ?? '')) !== '')
                ? trim(($info['givenname'] ?? '') . ' ' . ($info['surname'] ?? '')) . ' (' . ($info['pid'] ?? '—') . ')'
                : ($info['pid'] ?? '—') );
        $groupClasses = 'w-fit max-w-sm truncate hover:whitespace-normal focus:whitespace-normal break-words';
    @endphp

    <flux:card>
        <div class="flex flex-col items-center gap-2">
            <flux:icon.square-user-round class="size-12"/>
            <p>User Suchen</p>

            <div class="flex justify-center w-full md:w-2/3 lg:w-1/3 space-x-2">
                <flux:input.group class="flex-1">
                    <flux:select wire:model.live="searchAttribute" placeholder="Attribute..." required class="max-w-fit">
                        <flux:select.option value="PID">PID</flux:select.option>
                        <flux:select.option value="Nachname">Nachname</flux:select.option>
                        <flux:select.option value="Vollst. Name">Vollst. Name</flux:select.option>
                        <flux:select.option value="Titel">Stellenzeichen</flux:select.option>
                    </flux:select>

                    @if ($searchAttribute === 'PID')
                        <flux:input.group class="ml-2">
                            <flux:input.group.prefix>p</flux:input.group.prefix>
                            <flux:input wire:model.defer="searchTerm" wire:keydown.enter="search" placeholder="12345" inputmode="numeric" pattern="[0-9]*"/>
                        </flux:input.group>
                    @else
                        <flux:input
                            wire:model.defer="searchTerm"
                            wire:keydown.enter="search"
                            placeholder="{{ $searchAttribute === 'Titel' ? 'z. B. FM IKT 1*' : 'Suchbegriff eingeben...' }}"
                        />
                    @endif
                </flux:input.group>
            </div>

            @if ($error)
                <p class="text-red-600 mt-2">{{ $error }}</p>
            @endif

            <flux:button variant="primary" color="green" wire:click="search" type="button" class="cursor-pointer mt-4">
                Suchen
            </flux:button>
        </div>
    </flux:card>

    @if ($searchResults && $searchResults->count() > 0)
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-6">
            <table class="w-full text-sm text-left rtl:text-right text-gray-600 dark:text-gray-200 table-auto max-h-72 overflow-auto bg-gray-50 dark:bg-gray-900/20 rounded p-2 border border-gray-300 dark:border-gray-700">
                <thead class="text-xs text-gray-700 dark:text-gray-100 uppercase bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 w-auto">PID</th>
                    <th class="px-4 py-3 w-auto">Nachname</th>
                    <th class="px-4 py-3 w-auto">Vorname</th>
                    <th class="px-4 py-3 w-auto">Email</th>
                    <th class="px-4 py-3 w-auto">Aktion</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($searchResults as $user)
                    <tr class="{{ $loop->odd ? 'bg-white dark:bg-gray-800/70' : 'bg-gray-50 dark:bg-gray-800/40' }} hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border-b border-gray-200 dark:border-gray-700">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $user['pid'] }}</td>
                        <td class="px-4 py-3">{{ $user['surname'] ?? '–' }}</td>
                        <td class="px-4 py-3">{{ $user['givenname'] ?? '–' }}</td>
                        <td class="px-4 py-3">
                            @if(!empty($user['email']))
                                <flux:badge variant="pill" class="mt-1" color="green">{{ $user['email'] }}</flux:badge>
                            @else
                                <flux:text variant="subtle">nicht vorhanden</flux:text>
                            @endif

                            @if(!empty($user['external_email']))
                                <flux:badge variant="pill" class="mt-1" color="teal">
                                    {{ $user['external_email'] }}
                                    <flux:tooltip toggleable>
                                        <flux:button icon="information-circle" size="sm" variant="ghost"/>
                                        <flux:tooltip.content class="max-w-[20rem] space-y-2">extern</flux:tooltip.content>
                                    </flux:tooltip>
                                </flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap flex gap-2">
                            <flux:button size="xs" variant="primary" color="green" class="cursor-pointer"
                                         wire:click="loadGroupsAndInfo('{{ $user['pid'] }}')">
                                Anzeigen
                            </flux:button>

                            <flux:button size="xs" variant="primary" color="blue" class="cursor-pointer"
                                         wire:click="openCompare('{{ $user['pid'] }}')">
                                Vergleichen
                            </flux:button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Details modal --}}
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

        @php
            $groupDn = fn($g) => $selectedUserGroupMap[$g] ?? null;
        @endphp

        @if ($selectedUserGroups !== null)
            @if(count($selectedUserGroups) > 0)
                <flux:div copyable class="grid gap-1 mt-2 min-w-fit">
                    @foreach ($selectedUserGroups as $index => $group)
                        @php $dn = $groupDn($group); @endphp
                        <span class="inline-flex items-center gap-2">
                            <flux:button
                                size="xs"
                                variant="ghost"
                                class="p-0"
                                title="Mitglieder anzeigen"
                                wire:click.stop="openMembersModal('{{ addslashes($dn ?? '') }}')">
                                <flux:icon.user-group class="size-4 shrink-0"/>
                            </flux:button>

                            <flux:badge2 copyable variant="pill"
                                color="{{ $colors[$index % count($colors)] }}"
                                class="{{ $groupClasses }}"
                                title="{{ $group }}">
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

    {{-- Members Modal --}}
    <flux:modal name="groupMembers" class="w-[92vw] max-w-4xl" :dismissible="true">
        @php
            $dn = $memberListForDn ?? null;
            $members = $dn ? ($groupMembersByDn[$dn] ?? null) : null;
            $paginator = ($dn && is_array($members)) ? $this->getMembersPaginator($dn) : null;

            $displayGroupName = null;
            if ($dn) {
                $found = array_search($dn, $selectedUserGroupMap ?? [], true);
                $displayGroupName = $found !== false ? $found : $dn;
            }
        @endphp

        <div class="space-y-3">
            <flux:heading size="lg">
                Gruppenmitglieder
                @if($displayGroupName)
                    <span class="text-gray-500 dark:text-gray-400 font-normal"> — {{ $displayGroupName }}</span>
                @endif
            </flux:heading>

            <div class="mb-2">
                <flux:input
                    wire:model.live="memberSearch"
                    placeholder="Suchen: PID, Vorname, Nachname, Telefon"
                />
            </div>

            @if (!$dn)
                <flux:text variant="subtle">Keine Gruppe ausgewählt.</flux:text>
            @elseif ($members === null)
                <flux:text variant="subtle">Lade Mitglieder…</flux:text>
            @elseif ($paginator)
                <div class="border border-gray-300 dark:border-gray-700 rounded-md overflow-hidden">
                    <flux:table :paginate="$paginator" class="w-full">
                        <flux:table.columns>
                            <flux:table.column>
                                <div class="!pl-10 pr-4">
                                    <button type="button" class="w-full text-left"
                                            wire:click="setMemberSort('{{ $dn }}','pid')">
                                        PID
                                    </button>
                                </div>
                            </flux:table.column>
                            <flux:table.column>
                                <button type="button" class="w-full text-left"
                                        wire:click="setMemberSort('{{ $dn }}','givenname')">
                                    Vorname
                                </button>
                            </flux:table.column>
                            <flux:table.column>
                                <button type="button" class="w-full text-left"
                                        wire:click="setMemberSort('{{ $dn }}','surname')">
                                    Nachname
                                </button>
                            </flux:table.column>
                            <flux:table.column>
                                <button type="button" class="w-full text-left"
                                        wire:click="setMemberSort('{{ $dn }}','tel')">
                                    Telefon
                                </button>
                            </flux:table.column>
                        </flux:table.columns>

                        @foreach ($paginator as $row)
                            <flux:table.row
                                class="{{ $loop->odd ? 'bg-gray-50 dark:bg-gray-800/80' : 'bg-gray-100 dark:bg-gray-800/55' }} hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">

                                @php
                                    $pid = $row['pid'] ?? '—';
                                    $v = $row['givenname'] ?: '—';
                                    $n = $row['surname'] ?: '—';
                                    $tel = trim((string)($row['tel'] ?? ''));
                                    $tshow = $tel !== '' ? $tel : '—';
                                @endphp

                                {{-- PID --}}
                                <flux:table.cell class="!pl-10 pr-4 whitespace-nowrap">
                                    <span x-data="{label: @js($pid)}" x-transition.opacity>
                                        <span @click="
                                            if (label !== '—') {
                                                navigator.clipboard.writeText(label);
                                                const orig = label;
                                                label = 'Kopiert 💐';
                                                setTimeout(()=>label=orig,1200);
                                            }
                                        " class="cursor-pointer select-text" x-text="label"></span>
                                    </span>
                                </flux:table.cell>

                                {{-- Vorname --}}
                                <flux:table.cell class="px-4 whitespace-nowrap">
                                    <span x-data="{label: @js($v)}" x-transition.opacity>
                                        <span @click="
                                            if (label !== '—') {
                                                navigator.clipboard.writeText(label);
                                                const orig = label;
                                                label = 'Kopiert 💐';
                                                setTimeout(()=>label=orig,1200);
                                            }
                                        " class="cursor-pointer select-text" x-text="label"></span>
                                    </span>
                                </flux:table.cell>

                                {{-- Nachname --}}
                                <flux:table.cell class="px-4 whitespace-nowrap">
                                    <span x-data="{label: @js($n)}" x-transition.opacity>
                                        <span @click="
                                            if (label !== '—') {
                                                navigator.clipboard.writeText(label);
                                                const orig = label;
                                                label = 'Kopiert 💐';
                                                setTimeout(()=>label=orig,1200);
                                            }
                                        " class="cursor-pointer select-text" x-text="label"></span>
                                    </span>
                                </flux:table.cell>

                                {{-- Telefon --}}
                                <flux:table.cell class="px-4 whitespace-nowrap">
                                    <span x-data="{label: @js($tshow)}" x-transition.opacity>
                                        <span @click="
                                            if (label !== '—') {
                                                navigator.clipboard.writeText(label);
                                                const orig = label;
                                                label = 'Kopiert 💐';
                                                setTimeout(()=>label=orig,1200);
                                            }
                                        " class="cursor-pointer select-text" x-text="label"></span>
                                    </span>
                                </flux:table.cell>

                            </flux:table.row>
                        @endforeach
                    </flux:table>
                </div>
            @else
                <flux:text variant="subtle">Keine Mitglieder gefunden.</flux:text>
            @endif
        </div>
    </flux:modal>






    {{-- Compare modal --}}
    <flux:modal name="compare" class="w-[92vw] max-w-5xl" :dismissible="false">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
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
                            placeholder="12345"
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
                            <flux:heading size="sm">{{ $namePid($compareOtherInfo ?: ['pid' => $compareOtherPid]) }}</flux:heading>
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
                                <flux:heading size="sm">{{ $namePid($compareOtherInfo ?: ['pid' => $compareOtherPid]) }}</flux:heading>
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
