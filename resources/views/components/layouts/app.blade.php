<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nettools</title>
    @vite("resources/css/app.css")
    @fluxAppearance
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
<flux:sidebar sticky stashable
              class="bg-zinc-50 dark:bg-zinc-900 border-r rtl:border-r-0 rtl:border-l border-zinc-200 dark:border-zinc-700">
    <flux:sidebar.toggle class="lg:hidden" icon="x-mark"/>
    <flux:brand href="/dashboard" logo="logo.png" wire:navigate name="{{ config('app.name') }}"
                class="px-2 dark:hidden"/>
    <flux:brand href="/dashboard" logo="logo.png" wire:navigate name="{{ config('app.name') }}"
                class="px-2 hidden dark:flex"/>

    <flux:navlist>
        <flux:navlist.item icon="home" href="/dashboard" wire:navigate>Home</flux:navlist.item>

        {{-- DHCP --}}
        <flux:navlist.item icon="satellite-dish" href="/dhcp" wire:navigate>
            <div class="flex items-center justify-between">
                <span>DHCP</span>
                <livewire:service-status-indicator service="dhcp" display="icon"/>
            </div>
        </flux:navlist.item>

        {{-- DNS --}}
        <flux:navlist.item icon="earth-lock" href="{{ route('dns.index') }}" wire:navigate>
            <div class="flex items-center justify-between">
                <span>DNS</span>
                <livewire:service-status-indicator service="dns" display="icon"/>
            </div>
        </flux:navlist.item>


        <flux:navlist.group expandable heading="ID Tools" class="grid">
            <flux:navlist.item icon="mail-search" href="/next-free-pid" wire:navigate>Nächste freie PID
            </flux:navlist.item>
        </flux:navlist.group>


        <flux:navlist.group expandable heading="Generatoren" class="grid">
            <flux:navlist.item icon="dices" href="/password-generator" wire:navigate>Passwort</flux:navlist.item>
            <flux:navlist.item icon="numbered-list" href="/ovirt-serialnumber-generator" wire:navigate>oVirt
                Seriennummer
            </flux:navlist.item>
            <flux:navlist.item icon="network" href="/ip-calculator" wire:navigate>Subnetting</flux:navlist.item>
            <flux:navlist.item icon="signature" href="/signature-generator" wire:navigate>Signatur</flux:navlist.item>
        </flux:navlist.group>
    </flux:navlist>

    <flux:spacer/>

    <flux:navlist variant="outline">
        <flux:menu.item icon="arrow-right-start-on-rectangle" class="cursor-pointer"
                        x-on:click.prevent="document.getElementById('logout-form').submit()">
            Logout
        </flux:menu.item>
    </flux:navlist>
</flux:sidebar>

<flux:header class="lg:hidden">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left"/>
    <flux:spacer/>
    <flux:dropdown position="top" alignt="start">
        <flux:menu>
            <flux:menu.radio.group></flux:menu.radio.group>
            <flux:menu.separator/>
            <flux:menu.item icon="arrow-right-start-on-rectangle" class="cursor-pointer"
                            x-on:click.prevent="document.getElementById('logout-form').submit()">
                Logout
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</flux:header>

<flux:main>
    <flux:heading size="xl" level="1">
        <div class="flex justify-between">
            <div>
                Hallo, {{ Auth::user()->name }}
            </div>
            <div>
                <flux:dropdown x-data align="end">
                    <flux:button variant="subtle" square class="group" aria-label="Preferred color scheme">
                        <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini"
                                       class="text-zinc-500 dark:text-white"/>
                        <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini"
                                        class="text-zinc-500 dark:text-white"/>
                        <flux:icon.moon x-show="$flux.appearance === 'system' && $flux.dark" variant="mini"/>
                        <flux:icon.sun x-show="$flux.appearance === 'system' && ! $flux.dark" variant="mini"/>
                    </flux:button>

                    <flux:menu>
                        <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">Light</flux:menu.item>
                        <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">Dark</flux:menu.item>
                        <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">System
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    </flux:heading>

    <flux:text class="mb-6 mt-2 text-base">Herzlich willkommen bei {{ config('app.name') }}</flux:text>
    <flux:separator variant="subtle"/>

    <div class="mt-4">
        {{ $slot }}
    </div>

    @persist('toast')
    <flux:toast position="bottom right" class="pt-24"/>
    @endpersist
</flux:main>

<!-- Logout Form -->
<form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
    @csrf
</form>

@fluxScripts
</body>
</html>

