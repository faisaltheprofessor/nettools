<div class="w-[80%] md:w-[70%] mx-auto">
    <flux:card>
        <div class="flex flex-col items-center gap-2">
            <flux:icon.list-ordered class="size-12"/>
            <p>10 User PID Lücken</p>
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

    @if ($pIdsInTextEditor)
        <flux:textarea2 copyable rows="10">
            {{ $pIdsInTextEditor }}
        </flux:textare2>
    @endif

</div>

