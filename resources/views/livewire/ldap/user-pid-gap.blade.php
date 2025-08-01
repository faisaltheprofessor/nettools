<div class="w-1/2 mx-auto">
    <flux:card>
        <div class="flex flex-col items-center gap-2">
            <flux:icon.list-ordered class="size-12" />
            <p>10 User PID Lücken</p>
            <flux:field>
                <flux:input
                    readonly
                    copyable
                    variant="filled"
                    :value="$pIds"
                    class="text-green-700"
                />
            </flux:field>
            @if ($error)
                <p class="text-red-600">{{ $error }}</p>
            @endif

            <flux:button
                variant="primary"
                color="green"
                wire:click="getUserIdGap"
                type="button"
                class="cursor-pointer"
            >
                PIDs abrufen
            </flux:button>
        </div>


    </flux:card>
    @if($pIdsInTextEditor)
        <flux:editor wire:model="pIdsInTextEditor" toolbar="bold italic underline | copy" class="mt-2"/>
    @endif

</div>
