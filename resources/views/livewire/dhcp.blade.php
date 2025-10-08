<flux:card
    wire:poll.5s="getDhcpStatus"
    x-data
    x-on:start-polling.window="Livewire.dispatch('getDhcpStatus')"
    class="w-1/2 mx-auto space-y-6 relative"
>
    <h2 class="text-lg font-bold flex justify-center items-center">
        DHCP Dienst <span class="flex text-xs">&nbsp; <livewire:service-status-indicator service="dhcp"/></span>
    </h2>

    <flux:callout>
        <x-slot name="icon">
            <flux:icon.information-circle variant="solid" class="text-lime-500"/>
        </x-slot>

        <flux:callout.heading>Service-Migration</flux:callout.heading>

        <flux:callout.text>
            <p>Zum Migrieren des Dienstes auf einen VS bitte mit der rechten Maustaste auf den gewünschten VS
                klicken.</p>
        </flux:callout.text>
    </flux:callout>

    <div class="flex items-center justify-center">
        <div>

            <div class="flex gap-12 mt-3">
                @foreach($servers as $server)
                    @php $disabled = $runningServer === $server @endphp
                    <flux:context :disabled="$disabled">
                        <div class="flex flex-col items-center rounded-md cursor-context-menu relative"
                             style="width: 80px;">
                            <flux:icon.computer-desktop
                                class="size-20 {{ $runningServer === $server && $dhcpStatus === 'running' ? 'text-emerald-600' : 'text-gray-400' }}"
                                variant="solid"
                            />

                            <flux:text>{{ $server }}</flux:text>

                            @if($runningServer === $server && $dhcpStatus === 'running')
                                <div class="mt-2 flex justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         class="h-6 w-6 text-emerald-600 bg-white rounded-full"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <flux:menu>
                            <flux:menu.item
                                wire:click="migrateDhcp('{{ $server }}')"
                                icon="git-compare-arrows"
                                :disabled="$runningServer === $server && $dhcpStatus === 'running'"
                                wire:key="migrate-{{ $server }}"
                            >
                                Hierhin migrieren
                            </flux:menu.item>
                        </flux:menu>
                    </flux:context>
                @endforeach
            </div>

            <hr class="bg-gray-200 mt-4 mb-4"/>

            <div class="flex items-center gap-2 justify-center">
                <flux:modal.trigger name="select-vs">
                    <flux:button
                        variant="primary"
                        color="green"
                        icon="play"
                        :disabled="$dhcpStatus === 'running' || $dhcpStatus === 'loading'"
                        class="cursor-pointer"
                    >
                        Start
                    </flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="confirm-restart">
                    <flux:button
                        variant="primary"
                        color="teal"
                        icon="arrow-path"
                        :disabled="!$runningServer || $dhcpStatus !== 'running'"
                        class="cursor-pointer"
                    >
                        Neustart
                    </flux:button>
                </flux:modal.trigger>

                <div class="absolute right-2 bottom-2 text-xs">
                    @php $dhcpNets = config("urls.dhcp_nets") @endphp
                    <flux:link target="_blank" :href="$dhcpNets">DHCP Netze anzeigen
                        <flux:icon.arrow-top-right-on-square class="inline size-4"/>
                    </flux:link>
                </div>
            </div>
        </div>

        <flux:modal name="confirm-restart">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Neustart auswählen</flux:heading>
                    <flux:text class="mt-2 space-y-2">
                        <p>Wählen Sie, welche Dienste neu gestartet werden sollen.</p>
                    </flux:text>
                </div>

                {{-- Checkbox group: DHCP, DNS, Beide (All) --}}
                <flux:checkbox.group label="Dienst(e)" wire:model="restartServices" class="w-1/2 mx-auto">
                    <flux:checkbox.all label="Beide"/>
                    <flux:checkbox value="dhcp" label="DHCP"/>
                    <flux:checkbox value="dns" label="DNS"/>
                </flux:checkbox.group>

                <div class="flex gap-2 justify-end">
                    <flux:modal.close>
                        <flux:button variant="ghost">Abbrechen</flux:button>
                    </flux:modal.close>
                    <flux:button
                        variant="primary"
                        color="teal"
                        class="cursor-pointer"
                        wire:click.prevent="restartSelectedServices"
                    >
                        Neustart
                    </flux:button>
                </div>
            </div>
        </flux:modal>


        <flux:modal name="select-vs" variant="flyout">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Choose one...</flux:heading>
                    <flux:text class="mt-2">
                        <flux:radio.group label="" variant="cards" class="flex-col" wire:model="selectedServer">
                            @foreach($servers as $server)
                                <flux:radio value="{{ $server }}" icon="server" label="{{ $server }}" description=""/>
                            @endforeach
                        </flux:radio.group>
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer/>
                    <flux:modal>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal>
                    <flux:button color="green" type="submit" wire:click.prevent="startDhcp" class="cursor-pointer">
                        Start
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    </div>
</flux:card>
