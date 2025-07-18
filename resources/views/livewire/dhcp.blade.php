<flux:card wire:poll.10s="getDhcpStatus" class="w-2/3 mx-auto space-y-6">
    <h2 class="text-lg font-bold">DCHP Dienst</h2>

    <div class="flex mt-32 items-center justify-center">
        <div>

            <div class="flex gap-12 mt-3">
                @foreach($servers as $server)
                    <flux:context>
                        <div
                            class="flex flex-col items-center rounded-md cursor-context-menu relative""
                            style="width: 80px;"
                        >
                            <flux:icon.computer-desktop
                                class="size-20 {{ $runningServer === $server && $dhcpStatus === 'running' ? 'text-emerald-600' : 'text-gray-400' }}"
                                variant="solid"
                            />

                            <flux:text>{{ $server }}</flux:text>

                            @if($runningServer === $server && $dhcpStatus === 'running')
                                {{-- Active status icon below the computer icon --}}
                                <div class="mt-2 flex justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         class="h-6 w-6 text-emerald-600 bg-white rounded-full"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <flux:menu>
                            <flux:menu.item icon="check-circle">All OK</flux:menu.item>

                            <flux:menu.separator />

                            <flux:menu.submenu heading="DHCP">
                                <flux:menu.item icon="play">Start</flux:menu.item>
                                <flux:menu.item icon="power">Stop</flux:menu.item>
                                <flux:menu.item icon="rotate-ccw">Neustart</flux:menu.item>
                            </flux:menu.submenu>

                            <flux:menu.separator />

                            <flux:menu.submenu heading="DNS">
                                <flux:menu.item icon="play">Start</flux:menu.item>
                                <flux:menu.item icon="power">Stop</flux:menu.item>
                                <flux:menu.item icon="rotate-ccw">Neustart</flux:menu.item>
                            </flux:menu.submenu>
                        </flux:menu>
                    </flux:context>
                @endforeach
            </div>

            <hr class="bg-gray-200 mt-4 mb-4" />

            <div class="flex items-center gap-2 justify-center">
                <flux:button
                    variant="primary"
                    color="green"
                    icon="play"
                    x-on:click="$flux.toast({heading: 'Erfolg', text: 'Erledigt 🎉', variant: 'success', duration: 3000})"
                    class="cursor-pointer"
                >Start</flux:button>

                <flux:button
                    variant="primary"
                    color="red"
                    icon="power"
                    class="cursor-pointer"
                >Stop</flux:button>

                <flux:modal.trigger name="confirm-action">
                    <flux:button
                        variant="primary"
                        color="teal"
                        icon="arrow-path"
                        class="cursor-pointer"
                        :loading="$beingRestarted"
                    >Neustart</flux:button>
                </flux:modal.trigger>

                <flux:button
                    variant="primary"
                    icon="{{ $this->buttonIcon }}"
                    class="cursor-pointer {{ $this->buttonColor }}"
                    wire:click="getDhcpStatus"
                    @if($loading) disabled @endif
                />
            </div>
        </div>

        <flux:modal name="confirm-action" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Achtung</flux:heading>
                    <flux:text class="mt-2">
                        <p>Dieser Vorgang wird einige Sekunden dauern. Soll der DHCP Server wirklich gestoppt und danach neugestartet werden?</p>
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />

                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button variant="danger" wire:click="restartDhcp" class="cursor-pointer">Ja! Neustart</flux:button>
                </div>
            </div>
        </flux:modal>
    </div>
</flux:card>

