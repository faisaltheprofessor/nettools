 <flux:modal name="submit-feedback" variant="flyout" position="left" :dismissible="false" @feedback-submitted="$flux.modal('submit-feedback').close();confetti()">
        <div class="mt-10 justify-center space-y-6">
            <div>
                <flux:heading size="lg">Ich möchte</flux:heading>
            </div>

            <flux:radio.group label="" variant="cards" class="flex-col" wire:model="type">
                <flux:radio value="feature" label="Funktion vorschlagen" description="Hast du eine Idee? Immer her damit!"/>
                <flux:radio value="bug" label="Fehler melden" description="Irgendwas klemmt? Sag mir Bescheid!"/>
                <flux:radio value="feedback" label="Feedback geben" description="Lob, Kritik oder Gedanken? Ich hör zu!"/>
            </flux:radio.group>

            <flux:field>
                <flux:label>Titel</flux:label>
                <flux:input wire:model.defer="title"/>
                <flux:error name="title"/>
            </flux:field>

            <flux:field>
                <flux:label>Beschreibung</flux:label>
                <flux:textarea wire:model.defer="description" resize="none"/>
                <flux:error name="description"/>
            </flux:field>

            <flux:field>
                <flux:label>Unterstützende Bilder/Dokumente</flux:label>
                <livewire:file-uploader />
            </flux:field>

            <div class="flex justify-end">
                <flux:button variant="primary" color="green" wire:click="submit" class="cursor-pointer">
                    Submit
                </flux:button>
            </div>

            @if (session()->has('message'))

            @endif
        </div>
 </flux:modal>





