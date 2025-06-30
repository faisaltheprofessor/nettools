<div class="flex w-1/2 mx-auto justify-center">
    <div class="flex flex-col gap-8 items-start w-full">
        <!-- Passwortfeld + Stärke-Anzeige -->
        <div class="flex w-full">
            <div class="relative w-full rounded-lg bg-white shadow-sm flex items-center">
                <flux:input
                    icon="key"
                    placeholder="Passwort anzeigen"
                    wire:model="password"
                    readonly
                    copyable
                    class="!rounded-lg !border-none !bg-white !shadow-none text-xl flex-1"
                />


                <!-- Stärke-Balken -->
                <div class="absolute bottom-0 left-0 w-full h-[4px]">
                    <div class="h-full w-full bg-green-700"></div>
                </div>
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
                />
            </div>

            <!-- Zeichenoptionen -->
            <div class="grid grid-cols-2 gap-4 pt-2">
                <label class="flex items-center gap-2">
                    <flux:checkbox wire:model="useUppercase" wire:change="generatePassword" />
                    <span>Großbuchstaben</span>
                </label>

                <label class="flex items-center gap-2">
                    <flux:checkbox wire:model="useLowercase" wire:change="generatePassword" />
                    <span>Kleinbuchstaben</span>
                </label>

                <label class="flex items-center gap-2">
                    <flux:checkbox wire:model="useNumbers" wire:change="generatePassword" />
                    <span>Zahlen</span>
                </label>

                <label class="flex items-center gap-2">
                    <flux:checkbox wire:model="useSymbols" wire:change="generatePassword" />
                    <span>Sonderzeichen</span>
                </label>
            </div>

            <!-- Moduswahl -->
            <div class="space-y-3 pt-2">
                <label class="flex items-center gap-2">
                    <flux:radio wire:model="mode" wire:change="generatePassword" name="mode" value="say" />
                    <span>Einfach auszusprechen (ohne Zahlen und Sonderzeichen)</span>
                </label>

                <label class="flex items-center gap-2">
                    <flux:radio wire:model="mode" wire:change="generatePassword" name="mode" value="read" />
                    <span>Einfach zu lesen (ohne l, 1, o, 0)</span>
                </label>

                <label class="flex items-center gap-2">
                    <flux:radio wire:model="mode" wire:change="generatePassword" name="mode" value="all" />
                    <span>Alle Zeichen (beliebige Kombination)</span>
                </label>
            </div>
        </flux:card>
    </div>
</div>
