<div>

    <h2 class="text-lg font-bold">DNS Dienst</h2>
<div class="flex mt-32 items-center justify-center">
    <div>


   <div class="flex gap-12 mt-3">
        @foreach($servers as $server)

   <flux:context>
    <div class="flex flex-col items-center">
        <flux:icon.computer-desktop class="size-20 text-amber-500 cursor-context-menu" variant="solid"/>
        <flux:text>{{ $server }}</flux:text>
    </div>
    <flux:menu>
        <flux:menu.item icon="check-circle">All OK</flux:menu.item>

        <flux:menu.separator />

        <flux:menu.submenu heading="DHCP">
            <flux:menu.item icon="play">Start</flux:button>
            <flux:menu.item icon="power">Stop</flux:button>
            <flux:menu.item icon="rotate-ccw">Neuestart</flux:button>
        </flux:menu.submenu>


        <flux:menu.separator />

        <flux:menu.submenu heading="DNS">
            <flux:menu.item icon="play">Start</flux:button>
            <flux:menu.item icon="power">Stop</flux:button>
            <flux:menu.item icon="rotate-ccw">Neuestart</flux:button>
        </flux:menu.submenu>

    </flux:menu>
</flux:context>
        @endforeach

    </div>
    <hr class="bg-gray-200 mt-4 mb-4" />

    <div class="flex items-center gap-2 justify-center">
            <flux:button variant="primary" color="green" icon="play" x-on:click="$flux.toast({heading: 'Erfolg', text: 'Erledigt 🎉', variant: 'success', duration: 3000})" class="cursor-pointer">Start</flux:button>
        <flux:button variant="primary" color="red" icon="power" x-on:click="$flux.toast({heading: 'Fehler', text: 'Etwas ist schiefgelaufen', variant: 'danger', duration: 3000})" class="cursor-pointer" disabled>Stop</flux:button>

        <flux:modal.trigger name="confirm-action">
            <flux:button variant="primary" color="teal" icon="arrow-path" class="cursor-pointer">Neuestart</flux:button>
        </flux:modal.trigger>
    </div>
</div>

<flux:modal name="confirm-action" class="min-w-[22rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Achtung</flux:heading>
            <flux:text class="mt-2">
                <p>Dieser Vorgang wird einige Sekunden dauern. Soll der DNS Server wirklich gestoppt und danach neugestartet werden?</p>
            </flux:text>
        </div>

        <div class="flex gap-2">
            <flux:spacer />

            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>

            <flux:button type="submit" variant="danger">Ja! Neuestart</flux:button>
        </div>
    </div>
</flux:modal>
</div>


