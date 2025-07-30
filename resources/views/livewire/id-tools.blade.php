<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 items-center justify-center gap-3">
    <flux:card class=" mx-space-y-4">
        <div class="flex flex-col items-center gap-2">
        <flux:icon.mail-question-mark class="size-12" />
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

    <flux:card class="flex-1 mx-space-y-4">
        <div class="flex flex-col items-center gap-2">
        <flux:icon.user class="size-12" />
        <p>Nächste freie User PID</p>
       <flux:field>
                        <flux:input
                            readonly
                            copyable
                            variant="filled"
                            :value="$userPid"
                            class="text-green-700"
                        />
                    </flux:field>
            @if ($mailboxError)
                <p class="text-red-600">{{ $userError }}</p>
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

<flux:card class="flex-1 mx-space-y-4">
        <div class="flex flex-col items-center gap-2">
        <flux:icon.list-ordered class="size-12" />
        <p>10 PID Lücken</p>
       <flux:field>
                        <flux:input
                            readonly
                            copyable
                            variant="filled"
                            :value="$freePids"
                            class="text-green-700"
                        />
                    </flux:field>
            @if ($freePidsError)
                <p class="text-red-600">{{ $freePidsError }}</p>
            @endif

            <flux:button
                variant="primary"
                color="green"
                wire:click="getFreeUserPids"
                type="button"
                class="cursor-pointer"
            >
            PIDs abrufen
            </flux:button>
        </div>
    </flux:card>

<flux:card class="flex-1 mx-space-y-4">
        <div class="flex flex-col items-center gap-2">
        <flux:icon.list class="size-12" />
        <p>Letzte PIDs</p>
<flux:select wire:model="pidCount" placeholder="Anzahl...">
    <flux:select.option>20</flux:select.option>
    <flux:select.option>50</flux:select.option>
    <flux:select.option>100</flux:select.option>
    <flux:select.option>250</flux:select.option>
    <flux:select.option>All</flux:select.option>
</flux:select>
            @if ($lastPidsError)
                <p class="text-red-600">{{ $lastPidsError }}</p>
            @endif

            <flux:button
                icon="file-up"
                variant="primary"
                color="green"
                wire:click="getLastPids"
                type="button"
                class="cursor-pointer"
            >
            Exportieren
            </flux:button>
        </div>
    </flux:card>


<flux:card class="flex-1 mx-space-y-4">
        <div class="flex flex-col items-center gap-2">
        <flux:icon.list class="size-12" />
        <p>Gesamte PIDs (ink. Namen)</p>

            @if ($allPidsError)
                <p class="text-red-600">{{ $allPidsErro }}</p>
            @endif

            <flux:button
                icon="file-up"
                variant="primary"
                color="green"
                wire:click="getAllPids"
                type="button"
                class="cursor-pointer"
            >
            Exportieren
            </flux:button>
        </div>
    </flux:card>

</div>

