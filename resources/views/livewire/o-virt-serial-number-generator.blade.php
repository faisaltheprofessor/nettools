<div>
    <flux:card class="w-1/2 mx-auto space-y-6">
        <form wire:submit.prevent="generateSerial" class="space-y-6">

            {{-- Servername Field --}}
            <div>
                <flux:field>
                    <flux:label>Servername</flux:label>
                    <input
                        type="text"
                        wire:model.defer="servername"
                        placeholder="z. B. vs123"
                        autocomplete="off"
                        class="flux-input"
                        style="width: 100%; padding: 0.5rem; font-size: 1rem; border-radius: 0.375rem; border: 1px solid #ccc;"
                    />
                </flux:field>
                <div style="min-height: 1.5rem; padding-top: 0.25rem;">
                    <flux:error name="servername" />
                </div>
            </div>

            {{-- MAC Address Field --}}
            <div>
                <flux:field>
                    <flux:label>MAC-Adresse</flux:label>
                    <input
                        type="text"
                        wire:model.defer="mac"
                        placeholder="z. B. 00:1A:2B:3C:4D:5E"
                        autocomplete="off"
                        class="flux-input"
                        style="width: 100%; padding: 0.5rem; font-size: 1rem; border-radius: 0.375rem; border: 1px solid #ccc;"
                    />
                </flux:field>
                <div style="min-height: 1.5rem; padding-top: 0.25rem;">
                    <flux:error name="mac" />
                </div>
            </div>

            {{-- Button --}}
            <div class="flex justify-end items-end">
                <flux:button
                    type="submit"
                    variant="primary"
                    color="green"
                    class="cursor-pointer"
                >
                    Generieren
                </flux:button>
            </div>

        </form>
    </flux:card>

    {{-- Result --}}
    @if ($serial)
        <div class="w-1/2 mx-auto mt-10">
            <flux:input
                icon="barcode"
                wire:model="serial"
                readonly
                copyable
            />
        </div>
    @endif
</div>

