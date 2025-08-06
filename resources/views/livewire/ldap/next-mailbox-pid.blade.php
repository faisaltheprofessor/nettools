<div class="w-[80%] md:w-[70%] mx-auto">
    <flux:card>
        <div class="flex flex-col items-center gap-2">
            <flux:icon.mail-question-mark class="size-12"/>
            <p>Nächste freie Mailbox PID</p>
            <flux:field>
                <flux:input
                    readonly
                    copyable
                    variant="filled"
                    :value="$mailBoxPid"
                    class="text-green-700"
                />
            </flux:field>
            @if ($mailboxError)
                <p class="text-red-600">{{ $mailboxError }}</p>
            @endif

            <flux:button
                variant="primary"
                color="green"
                wire:click="getNextMailboxPid"
                type="button"
                class="cursor-pointer"
            >
                PID abrufen
            </flux:button>
        </div>
    </flux:card>
</div>
