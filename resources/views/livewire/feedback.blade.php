<flux:modal name="submit-feedback" variant="flyout" position="left">
    <div class="mt-10 justify-center space-y-6">
        <div>
            <flux:heading size="lg">Ich möchte</flux:heading>
        </div>
        <flux:radio.group label="" variant="cards" class="flex-col">
            <flux:radio value="feature" label="Funktion vorschlagen" description="Hast du eine Idee? Immer her damit!"/>
            <flux:radio value="bug" label="Fehler melden" description="Irgendwas klemmt? Sag mir Bescheid!"/>
            <flux:radio value="feedback" label="Feedback geben" description="Lob, Kritik oder Gedanken? Ich hör zu!"/>
        </flux:radio.group>
        <flux:field>
            <flux:label>Titel</flux:label>
            <flux:input wire:model="title"/>
            <flux:error name="title"/>
        </flux:field>
        <flux:textarea label="Beschreibung" resize="none"/>
        <flux:input type="file" wire:model="logo" label="📸 Screenshots oder unterstützende Dateien " multiple
                    accept=".jpg,.jpeg,.png,.webp,.pdf"/>

        <div class="flex justify-end">
            <flux:button variant="primary" color="green" class="cursor-pointer">Submit</flux:button>
        </div>
    </div>
</flux:modal>

