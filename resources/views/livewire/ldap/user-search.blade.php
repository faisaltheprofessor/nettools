<div class="w-[80%] md:w-[70%] mx-auto">
    @php
        $colors = ['green','emerald','teal','cyan','sky','blue','indigo','violet','purple','fuchsia','orange','amber','yellow','lime'];
        // name + pid; falls kein Name -> nur pid
        $namePid = fn($info) => isset($titleCaseName)
            ? $titleCaseName($info)
            : ( (trim(($info['givenname'] ?? '') . ' ' . ($info['surname'] ?? '')) !== '')
                ? trim(($info['givenname'] ?? '') . ' ' . ($info['surname'] ?? '')) . ' (' . ($info['pid'] ?? '—') . ')'
                : ($info['pid'] ?? '—') );
        // shared classes for long group names: truncate + expand on hover/focus
        $groupClasses = 'w-fit max-w-sm truncate hover:whitespace-normal focus:whitespace-normal break-words';
    @endphp

    <flux:card>
        <div class="flex flex-col items-center gap-2">
            <flux:icon.square-user-round class="size-12"/>
            <p>User Suchen</p>

            <div class="flex justify-center w-full space-x-2">
                <flux:input.group class="flex-1">
                    <flux:select wire:model.live="searchAttribute" placeholder="Attribute..." required class="max-w-fit">
                        <flux:select.option value="PID">PID</flux:select.option>
                        <flux:select.option value="Nachname">Nachname</flux:select.option>
                        <flux:select.option value="Vollst. Name">Vollst. Name</flux:select.option>
                    </flux:select>

                    @if ($searchAttribute === 'PID')
                        <flux:input.group class="ml-2">
                            <flux:input.group.prefix>p</flux:input.group.prefix>
                            <flux:input wire:model.defer="searchTerm" wire:keydown.enter="search" placeholder="12345" inputmode="numeric" pattern="[0-9]*"/>
                        </flux:input.group>
                    @else
                        <flux:input wire:model.defer="searchTerm" wire:keydown.enter="search" placeholder="Suchbegriff eingeben..."/>
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
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 table-auto max-h-72 overflow-auto bg-gray-50 rounded p-2">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 w-auto">PID</th>
                    <th class="px-4 py-3 w-auto">Nachname</th>
                    <th class="px-4 py-3 w-auto">Vorname</th>
                    <th class="px-4 py-3 w-auto">Email</th>
                    <th class="px-4 py-3 w-auto">Gruppen</th>

                </thead>
                <tbody>
                @foreach ($searchResults as $user)
                    <tr class="{{ $loop->odd ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700' }} border-b border-gray-200 dark:border-gray-600">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $user['pid'] }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $user['surname'] ?? '–' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $user['givenname'] ?? '–' }}</td>
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

        @if ($selectedUserGroups !== null)
            @if(count($selectedUserGroups) > 0)
                <flux:div copyable class="grid gap-1 mt-2 min-w-fit">
                    @foreach ($selectedUserGroups as $index => $group)
                        <flux:badge2 copyable variant="pill"
                                     color="{{ $colors[$index % count($colors)] }}"
                                     class="{{ $groupClasses }}"
                                     title="{{ $group }}">
                            {{ $group }}
                        </flux:badge2>
                    @endforeach
                </flux:div>
            @else
                <p>Keine Gruppen gefunden.</p>
            @endif
        @endif
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
