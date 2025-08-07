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
        <flux:textarea2 copyable readonly disabled rows="10" class="mt-4" x-init="$el.value = $el.value.trim().split('\n').map(s => s.trim()).join('\n')">
            {!! trim($pIdsInTextEditor)!!}

        </flux:textare2>
    @endif

</div>

