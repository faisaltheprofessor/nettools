<div class="w-2/3 mx-auto grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 place-items-stretch gap-3">
    <flux:card class="w-auto">
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

    <flux:card class="w-auto space-y-4">
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

<flux:card class="flex-1 w-auto space-y-4">
        <div class="flex flex-col items-center gap-2">
        <flux:icon.list-ordered class="size-12" />
        <p>10 User PID Lücken</p>
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

<flux:card class="flex-1 w-auto space-y-4">
        <div class="flex flex-col items-center gap-2">
        <flux:icon.list class="size-12" />
        <p>Letzte PIDs</p>
<flux:select wire:model="pidCount" placeholder="Anzahl..." required>
    <flux:select.option>20</flux:select.option>
    <flux:select.option>50</flux:select.option>
    <flux:select.option>100</flux:select.option>
    <flux:select.option>250</flux:select.option>
    <flux:select.option>Alle</flux:select.option>
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
            Als txt exportieren
            </flux:button>
        </div>
    </flux:card>


<flux:card class="flex-1 w-auto space-y-4">
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
            Als csv exportieren
            </flux:button>
        </div>
    </flux:card>

<flux:card class="col-span-2 w-auto space-y-4">
        <div class="flex flex-col items-center gap-2">
        <flux:icon.user-round-search class="size-12" />
        <p>Namenssuche nach User-ID</p>

            @if ($allPidsError)
                <p class="text-red-600">{{ $allPidsErro }}</p>
            @endif

            <div class="flex w-full">
<flux:input.group>
    <flux:select class="max-w-fit">
        <flux:select.option selected>Nachname</flux:select.option>
        <flux:select.option>Vollständiger Name</flux:select.option>
    </flux:select>
    <flux:input placeholder="Mustermann oder Max, Mustermann" />
    <flux:tooltip toggleable class="ml-2">
        <flux:button icon="information-circle" size="sm" variant="ghost" />
        <flux:tooltip.content class="max-w-[20rem] space-y-2">
            <p>Für den vollständigen Namen bitte Vorname, Nachname angeben.</p>
        </flux:tooltip.content>
    </flux:tooltip>
</flux:input.group>

            </div>
        <flux:button variant="primary" color="green" class="cursor-pointer">Suchen</flux:button>

           </div>
    </flux:card>

</div>

