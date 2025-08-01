<flux:card
    wire:poll.5s="getDhcpStatus"
    x-data
    x-on:start-polling.window="Livewire.dispatch('getDhcpStatus')"
    class="w-1/2 mx-auto space-y-6"
>
    <h2 class="text-lg font-bold flex justify-center items-center">
        DHCP Dienst <span class="flex text-xs">&nbsp; <livewire:service-status-indicator service="dhcp"/></span>
    </h2>

    <div class="flex mt-32 items-center justify-center">
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
            </div>
        </div>

        <flux:modal name="confirm-restart">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Achtung</flux:heading>
                    <flux:text class="mt-2">
                        <p>Dieser Vorgang wird einige Sekunden dauern. Soll der DHCP Server wirklich gestoppt und danach
                            neugestartet werden?</p>
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer/>
                    <flux:modal>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal>
                    <flux:button variant="danger" type="submit" wire:click.prevent="restartDhcp" class="cursor-pointer">
                        Ja! Neustart
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

