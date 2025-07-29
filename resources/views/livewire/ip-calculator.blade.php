<flux:card class="w-1/2 mx-auto space-y-auto">
    <form wire:submit.prevent="calculate" class="space-y-6">
        <flux:field>
            <flux:label>IP-Adresse</flux:label>
            <flux:description>Geben Sie eine gültige IPv4-Adresse ein.</flux:description>
            <input
                type="text"
                wire:model.defer="ip"
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

        <div class="flex justify-end">
            <flux:button
                type="submit"
                variant="primary"
                class="cursor-pointer"
                color="green"
            >
                Berechnen
            </flux:button>
        </div>
    </form>

    <flux:modal name="results-modal" wire:model="showResultsModal" class="md:w-[48rem]" :dismissible="false">
        @if ($results && !isset($results['error']))
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Berechnungsergebnisse</flux:heading>
                    <flux:text class="mt-2">Hier sind die Details zur IP-Berechnung.</flux:text>
                </div>

                <div class="overflow-auto max-h-[30rem]">
                    <flux:table class="w-full border-collapse">
                        <flux:table.column class="text-left px-4 py-3 border-b-2 text-sm font-semibold">
                            Eigenschaft
                        </flux:table.column>
                        <flux:table.column class="text-left px-4 py-3 border-b-2 text-sm font-semibold">
                            Wert
                        </flux:table.column>

                        <flux:table.rows>
                            @foreach ($results as $key => $value)
                                <flux:table.row class="border-b hover:bg-gray-100 transition-colors">
                                    <flux:table.cell class="px-4 py-2 font-medium text-gray-900">
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
                                    <flux:table.cell class="px-4 py-2 font-mono text-gray-800">
                                        {{ $value }}
                                    </flux:table.cell>
                                </flux:table.row>
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
        @elseif ($results && isset($results['error']))
            <div class="p-4 text-red-600 font-semibold">
                {{ $results['error'] }}
            </div>
            <div class="flex justify-end pt-2">
                <flux:button variant="ghost" wire:click="$set('showResultsModal', false)" class="cursor-pointer">
                    Schließen
                </flux:button>
            </div>
        @endif
    </flux:modal>
</flux:card>

