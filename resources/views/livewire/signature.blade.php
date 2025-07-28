<flux:card class="w-1/2 mx-auto space-y-6">
    <div class="flex w-full items-end gap-2">
        <flux:field class="w-full">
            <flux:label>P-Kennung</flux:label>
            <flux:input.group>
                <flux:input.group.prefix>P</flux:input.group.prefix>
                <flux:input wire:model="pkennung" placeholder="16184" />
            </flux:input.group>
            <flux:error name="pkennung" />
        </flux:field>
        <flux:button icon="signature" variant="primary" color="green" class="cursor-pointer">Generieren</flux:button>
    </div>
</flux:card>

