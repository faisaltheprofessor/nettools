<div>
    <flux:card class="w-1/2 mx-auto space-y-6">
        <form wire:submit.prevent="getLdapRaw" class="w-full items-center gap-2">
            <flux:field class="w-full">
                <flux:label>PID</flux:label>
                <flux:input.group>
                    <flux:input.group.prefix>P</flux:input.group.prefix>
                    <flux:input wire:model.defer="pkennung" placeholder="12345 oder p12345" />
                </flux:input.group>
                <flux:error name="pkennung" />
            </flux:field>

            <div class="w-full flex justify-end mt-4">
                <flux:button type="submit" icon="braces" variant="primary" color="green" class="cursor-pointer">
                    Abrufen
                </flux:button>
            </div>
        </form>
    </flux:card>

   @if (!empty($result))
    <flux:card class="w-1/2 mx-auto mt-6">
        <x-json-tree :data="$result"/>
    </flux:card>
@endif
</div>
