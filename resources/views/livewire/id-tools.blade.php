<div>
    <flux:card class="w-1/2 mx-auto space-y-4">
        <div>
            <flux:button
                variant="primary"
                color="green"
                wire:click="getNextMailboxPid"
                type="button"
                class="cursor-pointer"
            >
                Nächste Mailbox PID
            </flux:button>
        </div>

        @if ($mailBoxPid || $mailboxError)
            @if ($mailboxError)
                <p class="text-red-600">{{ $mailboxError }}</p>
            @else
                <div class="flex gap-2 mt-2 items-center">
                    <p>Die nächste freie ID für Mailboxen ist:</p>
                    <flux:field>
                        <flux:input
                            readonly
                            copyable
                            variant="filled"
                            :value="$mailBoxPid"
                            class="text-green-700"
                        />
                    </flux:field>
                </div>
            @endif
        @endif
    </flux:card>


    <flux:card class="w-1/2 mx-auto space-y-4 mt-4">
        <div>
            <flux:button
                variant="primary"
                color="green"
                wire:click="getNextUserPid"
                type="button"
                class="cursor-pointer"
            >
                Nächste User PID
            </flux:button>
        </div>

        @if ($userPid || $userError)
            @if ($userError)
                <p class="text-red-600">{{ $userError }}</p>
            @else
                <div class="flex gap-2 mt-2 items-center">
                    <p>Die nächste freie ID für User ist:</p>
                    <flux:field>
                        <flux:input
                            readonly
                            copyable
                            variant="filled"
                            :value="$userPid"
                            class="text-green-700"
                        />
                    </flux:field>
                </div>
            @endif
        @endif
    </flux:card>

</div>

