<div>
    <h2 class="text-lg font-bold">DCHP Process</h2>

   <div class="flex">
        @foreach($servers as $server)

   <flux:context>
        <flux:icon.computer-desktop class="size-64" />
        <flux:text>{{ $server }}</flux:text>

    <flux:menu>
        <flux:menu.item icon="check-circle" class="text-green-500">All OK</flux:menu.item>

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
</div>
