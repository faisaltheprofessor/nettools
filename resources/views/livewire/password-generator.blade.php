<div class="flex w-1/2 mx-auto justify-center">
    <div class="flex flex-col gap-8 items-start w-full">
        <!-- Passwortfeld + Stärke-Anzeige -->
        <div class="flex w-full">
            <div class="relative w-full rounded-lg shadow-sm flex items-center">
                <flux:input
                    icon="key"
                    placeholder="Passwort anzeigen"
                    wire:model="password"
                    readonly
                    copyable
                class="ring-0 focus:ring-0"
                />


            </div>

            <!-- Refresh Button -->
                <button type="button" wire:click="generatePassword" class="mr-3 text-gray-500 hover:text-gray-700">
                    <flux:icon name="arrow-path" class="w-5 h-5 ml-1" />
                </button>
        </div>

        <!-- Einstellungen-Karte -->
        <flux:card class="w-full p-6 space-y-6">
            <h2 class="text-xl font-semibold">Passe dein Passwort an</h2>
            <hr class="border-gray-200">

            <!-- Passwortlänge -->
            <div class="space-y-2">
                <label class="font-medium">Passwortlänge</label>
                <flux:input
                    type="number"
                    wire:model="length"
                    wire:change="generatePassword"
                    min="4"
                    max="64"
                    class="w-24"
                    :disabled="$mode != 'all'"
                />
            </div>

            <!-- Zeichenoptionen -->
            <div class="flex gap-4 pt-2 flex-wrap">
                <flux:field variant="inline">
                    <flux:checkbox wire:model="useUppercase" wire:change="generatePassword" :disabled="$mode != 'all'" />
                    <flux:label>Großbuchstaben</flux:label>
                </flux:field>

            <flux:field variant="inline">
                <flux:checkbox wire:model="useLowercase" wire:change="generatePassword" :disabled="$mode != 'all'" />
                <flux:label>Kleinbuchstaben</flux:label>
            </flux:field>


            <flux:field variant="inline">
                <flux:checkbox wire:model="useNumbers" wire:change="generatePassword" :disabled="$mode != 'all'" />
                <flux:label>Zahlen</flux:label>
            </flux:field>

            <flux:field variant="inline">
                <flux:checkbox wire:model="useCommonSymbols" wire:change="generatePassword" :disabled="$mode != 'all'" />
                <flux:label>Gemeinsame Sonderzeichen (DE/US)</flux:label>
            </flux:field>


            <flux:field variant="inline">
                <flux:checkbox wire:model="useSymbols" wire:change="generatePassword" :disabled="$mode != 'all'" />
                <flux:label>Alle Sonderzeichen</flux:label>
            </flux:field>


            </div>



            <hr class="border-gray-200">


<flux:radio.group wire:model="mode" label="mnemonic" variant="pills" wire:change="generatePassword">
    <flux:radio value="all" label="x" checked/>
    <flux:radio value="easy" label="einfach" />
    <flux:radio value="hard" label="erweritert" />
</flux:radio.group>

            <!-- Moduswahl -->
        </flux:card>
    </div>
</div>
