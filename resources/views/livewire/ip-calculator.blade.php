<flux:card class="w-2/3 mx-auto space-y-6">
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
            <flux:error name="ip" />
        </flux:field>

        <flux:field style="width: 100%;">
            <flux:label>Subnetzmaske</flux:label>
            <flux:description>Verwenden Sie CIDR-Notation (z.B. 24) oder Punkt-Notation (z.B. 255.255.255.0)</flux:description>
            <input
                type="text"
                wire:model.defer="subnet"
                placeholder="z.B. 27 oder 255.255.255.224"
                autocomplete="off"
                class="flux-input"
                style="width: 100%; padding: 0.5rem; font-size: 1rem; border-radius: 0.375rem; border: 1px solid #ccc;"
            />
            <flux:error name="subnet" />
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

    @if ($results)
        @if (isset($results['error']))
            <p class="text-red-600 mt-6 font-semibold text-center">{{ $results['error'] }}</p>
        @else
            <div class="mt-8 overflow-auto" style="width: 100%;">

                <flux:table style="width: 100%; border-collapse: collapse;">

                    <flux:table.columns>
                        <flux:table.column style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #ccc;">Eigenschaft</flux:table.column>
                        <flux:table.column style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #ccc;">Wert</flux:table.column>
                        <flux:table.column style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #ccc;">Binär</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($results as $key => $value)
                            @if ($key !== 'Binary')
                                <flux:table.row style="border-bottom: 1px solid #eee;">
                                    <flux:table.cell style="padding: 0.5rem 0.75rem;">
                                        @switch($key)
                                            @case('Address')
                                                Adresse
                                                @break
                                            @case('Subnet Mask')
                                                Subnetzmaske
                                                @break
                                            @case('Wildcard Mask')
                                                Wildcard-Maske
                                                @break
                                            @case('Network Address')
                                                Netzwerkadresse
                                                @break
                                            @case('HostMin')
                                                Host (min)
                                                @break
                                            @case('HostMax')
                                                Host (max)
                                                @break
                                            @case('Broadcast')
                                                Broadcast
                                                @break
                                            @case('Hosts/Net')
                                                Hosts / Netz
                                                @break
                                            @case('Class')
                                                Klasse
                                                @break
                                            @case('Type')
                                                Typ
                                                @break
                                            @default
                                                {{ $key }}
                                        @endswitch
                                    </flux:table.cell>
                                    <flux:table.cell style="padding: 0.5rem 0.75rem;">{{ $value }}</flux:table.cell>
                                    <flux:table.cell style="padding: 0.5rem 0.75rem; font-family: monospace;">
                                        {{ $results['Binary'][$key] ?? '—' }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endif
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    @endif

</flux:card>
