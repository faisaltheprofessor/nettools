<div class="w-1/2 mx-auto">
    @php
        $colors = [
            'zinc', 'green',  'emerald', 'teal', 'amber', 'yellow', 'lime',
            'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'orange'
        ];
    @endphp
    <flux:card>
        <div class="flex flex-col items-center gap-2">
            <flux:icon.square-user-round class="size-12" />
            <p>User PID & Namenssuche</p>

            <div class="flex justify-center w-full space-x-2">
                <flux:input.group class="flex-1">
                    <flux:select wire:model="searchAttribute" placeholder="Attribute..." required class="max-w-fit">
                        <flux:select.option value="PID">PID</flux:select.option>
                        <flux:select.option value="Nachname">Nachname</flux:select.option>
                        <flux:select.option value="Vollst. Name">Vollst. Name</flux:select.option>
                    </flux:select>

                    <flux:input wire:model.defer="searchTerm" placeholder="Suchbegriff eingeben..." />
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
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 table-auto max-h-72 overflow-auto bg-gray-50 rounded p-2">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 whitespace-nowrap w-auto">PID</th>
                    <th class="px-4 py-3 whitespace-nowrap w-auto">Nachname</th>
                    <th class="px-4 py-3 whitespace-nowrap w-auto">Vorname</th>
                    <th class="px-4 py-3 whitespace-nowrap w-auto">Email</th>
                    <th class="px-4 py-3 whitespace-nowrap w-auto">Gruppen</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($searchResults as $user)
                    <tr class="{{ $loop->odd ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800' }} border-b dark:border-gray-700 border-gray-200">
                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ $user['pid'] }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $user['surname'] ?? '–' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $user['givenname'] ?? '–' }}</td>
                        <td class="px-4 py-3">
                            @foreach ($user['emails'] as $index => $email)
                                <flux:badge variant="pill" class="mt-1" color="{{ $colors[$index % count($colors)] }}">
                                    {{ $email }}
                                </flux:badge>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <flux:button size="xs" wire:click="loadGroups('{{ $user['pid'] }}')">
                                Anzeigen
                            </flux:button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <flux:modal name="groups" class="w-fit">
        @if ($selectedUserGroups !== null)


            @if(count($selectedUserGroups) > 0)
                <div class="flex flex-wrap gap-2 mt-4">
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
    </flux:modal>
</div>
