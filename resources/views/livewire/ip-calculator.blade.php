<flux:card class="w-1/2 mx-auto space-y-6">
    <form wire:submit.prevent="calculate" class="space-y-6">
        <flux:field>
            <flux:label>IP-Adresse</flux:label>
            <flux:description>Geben Sie eine gültige IPv4-Adresse ein.</flux:description>
            <input
                type="text"
                wire:model.defer="ip"
                pattern="\d{1,3}(\.\d{1,3}){3}"
                placeholder="z.B. 10.93.14.15"
                autocomplete="off"
                class="flux-input"
                style="width: 100%; padding: 0.5rem; font-size: 1rem; border-radius: 0.375rem; border: 1px solid #ccc;"
            />
            <flux:error name="ip"/>
        </flux:field>

        <flux:field>
            <flux:label>Subnetzmaske</flux:label>
            <flux:description>Verwenden Sie CIDR-Notation (z.B. 24) oder Punkt-Notation (z.B. 255.255.255.0)
            </flux:description>
            <input
                type="text"
                wire:model.defer="subnet"
                placeholder="z.B. 27 oder 255.255.255.224"
                autocomplete="off"
                class="flux-input"
                style="width: 100%; padding: 0.5rem; font-size: 1rem; border-radius: 0.375rem; border: 1px solid #ccc;"
            />
            <flux:error name="subnet"/>
        </flux:field>

        <flux:button
            type="submit"
            variant="primary"
            color="blue"
            style="width: 100%; padding: 0.75rem; font-size: 1.125rem; font-weight: 600; border-radius: 0.375rem; cursor: pointer;"
        >
            Berechnen
        </flux:button>
    </form>

    @if ($results && isset($results['error']))
        <p class="text-red-600 mt-6 font-semibold text-center">{{ $results['error'] }}</p>
    @endif

    <flux:modal name="results-modal" wire:model="showResultsModal" class="md:w-[48rem]">
        @if ($results && !isset($results['error']))
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Berechnungsergebnisse</flux:heading>
                    <flux:text class="mt-2">Hier sind die Details zur IP-Berechnung.</flux:text>
                </div>

                <div class="overflow-auto max-h-[30rem]">
                    <flux:table class="w-full border-collapse">
                        <flux:table.columns>
                            <flux:table.column
                                class="text-left px-4 py-3 border-b-2 border-gray-200 dark:border-gray-700 bg-gray-50 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Eigenschaft
                            </flux:table.column>
                            <flux:table.column
                                class="text-left px-4 py-3 border-b-2 border-gray-200 dark:border-gray-700 bg-gray-50 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Wert
                            </flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($results as $key => $value)
                                @if ($key !== 'Binary' && $key !== 'Type')
                                    <flux:table.row
                                        class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                        <flux:table.cell class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">
                                            @switch($key)
                                                @case('Address') Adresse @break
                                                @case('Subnet Mask') Subnetzmaske @break
                                                @case('Wildcard Mask') Wildcard-Maske @break
                                                @case('Network Address') Netzwerkadresse @break
                                                @case('HostMin') Host (min) @break
                                                @case('HostMax') Host (max) @break
                                                @case('Broadcast') Broadcast @break
                                                @case('Hosts/Net') Hosts / Netz @break
                                                @case('Class') Klasse @break
                                                @default {{ $key }}
                                            @endswitch
                                        </flux:table.cell>
                                        <flux:table.cell class="px-4 py-2 font-mono text-gray-800 dark:text-gray-200">
                                            {{ $value }}
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endif
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button variant="ghost" wire:click="$set('showResultsModal', false)" class="cursor-pointer">
                        Schließen
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</flux:card>

