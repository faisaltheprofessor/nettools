<flux:card class="w-2/3 mx-auto space-y-6">
        <flux:field>
            <flux:label>Servername</flux:label>
          <input
              type="text"
              wire:model.defer="servername"
              placeholder="vs123"
              autocomplete="off"
              class="flux-input"
              style="width: 100%; padding: 0.5rem; font-size: 1rem; border-radius: 0.375rem; border: 1px solid #ccc;"
            />
            <flux:error name="servername" />
        </flux:field>

        <flux:field>
            <flux:label>MAC-Adresse</flux:label>
            <input
              type="text"
              placeholder="00:16:3e:ba:12:34"
              wire:model.defer="mac"
              placeholder="4"
              autocomplete="off"
              class="flux-input"
              style="width: 100%; padding: 0.5rem; font-size: 1rem; border-radius: 0.375rem; border: 1px solid #ccc;"
            />
            <flux:error name="mac" />
        </flux:field>

        <div class="w-full flex flex-col gap-2">
          <flux:button
              type="submit"
              variant="primary"
              color="blue"
              style="padding: 0.75rem; font-size: 1.125rem; font-weight: 600; border-radius: 0.375rem; cursor: pointer;"
              wire:click="generateSerial"
          >
              Generieren
          </flux:button>
    
          <flux:input
             icon="barcode"
             wire:model="serial"
             readonly
             copyable
             class="ring-0 focus:ring-0"
          />
        </div>
</flux:card>
