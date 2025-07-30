<div>
    <!-- Input Form -->
    <flux:card class="w-1/2 mx-auto space-y-6">
        <form wire:submit.prevent="generate" class="w-full items-center gap-2">

            <flux:field class="w-full">
                <flux:label>PID</flux:label>
                <flux:input.group>
                    <flux:input.group.prefix>P</flux:input.group.prefix>
                    <flux:input wire:model.defer="pkennung" placeholder="16184"/>
                </flux:input.group>
                <flux:error name="pkennung"/>
            </flux:field>


            <div class="w-full flex justify-end mt-4">
                 <flux:button type="submit" icon="signature" variant="primary" color="green" class="cursor-pointer">
                Generieren
            </flux:button>

            </div>
                   </form>
    </flux:card>

    <!-- Output Signature -->
    @if (!empty($this->signatureContent))
        <flux:card class="w-1/2 mx-auto mt-6">
            <flux:editor
                wire:model="signatureContent"
                label="Signatur"
                description="Diese Signatur wurde aus dem LDAP generiert."
                toolbar="bold italic underline | undo redo | copy"
                readonly
            />
        </flux:card>
    @endif
</div>

