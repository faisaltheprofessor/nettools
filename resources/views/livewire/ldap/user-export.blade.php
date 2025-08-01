<flux:card class="w-1/2 mx-auto">
    <div class="flex flex-col items-center gap-2">
        <flux:icon.file-up class="size-12" />
        <p>P-ID Export</p>

       <div class="flex justify-center">
           <flux:input.group>
               <flux:select wire:model="pidCount" placeholder="Anzahl..." required class="max-w-fit">
                   <flux:select.option>20</flux:select.option>
                   <flux:select.option>50</flux:select.option>
                   <flux:select.option>100</flux:select.option>
                   <flux:select.option>250</flux:select.option>
                   <flux:select.option>Alle</flux:select.option>
               </flux:select>

               <flux:select wire:model="exportMode" placeholder="Export-Modus" class="max-w-fit">
                   <flux:select.option value="view">hier anzeigen</flux:select.option>
                   <flux:select.option value="txt">als Text-Datei herunterladen</flux:select.option>
                   <flux:select.option value="csv">als CSV-Datei herunterladen</flux:select.option>
               </flux:select>
           </flux:input.group>

           <flux:field variant="inline" class="flex items-center w-[40%] ml-2">
               <flux:checkbox wire:model="includeNames" />
               <flux:label class="w-fit no-wrap">inkl. Namen</flux:label>
               <flux:error name="icludeName" />
           </flux:field>
       </div>



        @if ($error)
            <p class="text-red-600 text-sm">{{ $error }}</p>
        @endif

        <flux:button
            variant="primary"
            color="green"
            wire:click="exportPids"
            type="button"
            class="cursor-pointer"
        >
            Fortfahren
        </flux:button>

        @if ($exportOutput)
            <flux:editor wire:model="exportOutput" toolbar="bold italic underline | copy" readonly/>
        @endif
    </div>
</flux:card>
