<flux:card class="w-1/2 mx-auto">
    <div class="flex flex-col items-center gap-2">
        <flux:icon.user class="size-12" />
        <p>Nächste freie User PID</p>
        <flux:field>
            <flux:input
                readonly
                copyable
                variant="filled"
                :value="$pid"
                class="text-green-700"
            />
        </flux:field>
        @if ($error)
            <p class="text-red-600">{{ $error }}</p>
        @endif

        <flux:button
            variant="primary"
            color="green"
            wire:click="getNextUserPid"
            type="button"
            class="cursor-pointer"
        >
            PID abrufen
        </flux:button>
    </div>
</flux:card>
