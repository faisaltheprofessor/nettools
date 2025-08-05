<div class="w-[80%] md:w-[70%] mx-auto">
    @php
        $colors = [
            'zinc', 'green',  'emerald', 'teal', 'amber', 'yellow', 'lime',
            'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'orange'
        ];
    @endphp
    <flux:card>
        <div class="flex flex-col items-center gap-2">
            <flux:icon.square-user-round class="size-12"/>
            <p>User Suchen</p>

            <div class="flex justify-center w-full space-x-2">
                <flux:input.group class="flex-1">
                    <flux:select wire:model="searchAttribute" placeholder="Attribute..." required class="max-w-fit">
                        <flux:select.option value="PID">PID</flux:select.option>
                        <flux:select.option value="Nachname">Nachname</flux:select.option>
                        <flux:select.option value="Vollst. Name">Vollst. Name</flux:select.option>
                    </flux:select>

                    <flux:input wire:model.defer="searchTerm" wire:keydown.enter="search"
                                placeholder="Suchbegriff eingeben..."/>
                </flux:input.group>
            </div>

            @if ($error)
                <p class="text-red-600 mt-2">{{ $error }}</p>
            @endif

            <flux:button
                variant="primary"
                color="green"
                wire:click="search"
                type="button"
                class="cursor-pointer mt-4"
            >
                Suchen
            </flux:button>
        </div>
    </flux:card>

    @if ($searchResults && $searchResults->count() > 0)
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-6">
            <table
                class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 table-auto max-h-72 overflow-auto bg-gray-50 rounded p-2">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 whitespace-nowrap w-auto">PID</th>
                    <th class="px-4 py-3 whitespace-nowrap w-auto">Nachname</th>
                    <th class="px-4 py-3 whitespace-nowrap w-auto">Vorname</th>
                    <th class="px-4 py-3 whitespace-nowrap w-auto">Email

                    </th>
                    <th class="px-4 py-3 whitespace-nowrap w-auto">Gruppen und Details</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($searchResults as $user)
                    <tr class="{{ $loop->odd ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700' }} border-b border-gray-200 dark:border-gray-600">
                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900 dark:text-gray-100">{{ $user['pid'] }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-200">{{ $user['surname'] ?? '–' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-200">{{ $user['givenname'] ?? '–' }}</td>
                        <td class="px-4 py-3">
                            @if(!empty($user['email']))
                                <flux:badge variant="pill" class="mt-1" color="green">
                                    {{ $user['email'] }}
                                </flux:badge>
                                @else
                                <flux:text variant="subtle">nicht vorhanden</flux:text>
                            @endif

                            @if(!empty($user['external_email']))
                                <flux:badge variant="pill" class="mt-1" color="teal">
                                    {{ $user['external_email'] }}
                                    <flux:tooltip toggleable>
                                        <flux:button icon="information-circle" size="sm" variant="ghost"/>
                                        <flux:tooltip.content class="max-w-[20rem] space-y-2">extern
                                        </flux:tooltip.content>
                                    </flux:tooltip>
                                </flux:badge>
                            @endif

                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <flux:button size="xs" variant="primary" color="green" class="cursor-pointer"
                                         wire:click="loadGroupsAndInfo('{{ $user['pid'] }}')">
                                Anzeigen
                            </flux:button>
                        </td>
                    </tr>
                @endforeach

                </tbody>
            </table>
        </div>
    @endif

    <flux:modal name="groups" class="w-fit" :dismissible="false">
        @if ($selectedUserInfo)
            <flux:heading class="flex justify-center">{{ $selectedUserInfo['pid'] }}</flux:heading>

            <flux:text class="mt-2">
                Nachname: {{ $selectedUserInfo['surname'] ?? '—' }}
            </flux:text>
            <flux:text class="mt-2">
                Vorname: {{ $selectedUserInfo['givenname'] ?? '—' }}
            </flux:text>
            <flux:text class="mt-2">
                Info: {{ $selectedUserInfo['info'] ?? '—' }}
            </flux:text>
            <flux:text class="mt-2">
                Letzter
                Login: {{ \Carbon\Carbon::parse($selectedUserInfo['lastLogin'])->setTimezone("Europe/Berlin") . " ("  . \Carbon\Carbon::parse($selectedUserInfo['lastLogin'])->setTimezone("Europe/Berlin")->diffForHumans() . ")" ?? '—' }}
            </flux:text>
            <flux:text class="mt-2">
                Kontext: {{ $selectedUserInfo['context'] ?? '—' }}
            </flux:text>
        @endif
        <hr class="mt-2">
        @if ($selectedUserGroups !== null)
            <flux:heading class="mt-2 flex justify-center">
                Gruppenzugehörigkeiten <flux:badge color="lime" size="sm" inset="top bottom" class="ml-2">{{ count($selectedUserGroups) }}</flux:badge>
            </flux:heading>
            @if(count($selectedUserGroups) > 0)
                <div class="grid grid-cols-1 gap-1 mt-2">
                    @foreach ($selectedUserGroups as $index => $group)
                            <flux:badge variant="pill" color="{{ $colors[$index % count($colors)] }}">
                                {{ $group }}
                            </flux:badge>
                    @endforeach
                </div>
            @else
                <p>Keine Gruppen gefunden.</p>
            @endif
        @endif
    </flux:accordion.item>

</div>
