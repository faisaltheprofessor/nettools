<div>
    <form wire:submit.prevent="searchNow">
        {{-- ===== Top Search Bar (Enter submits) ===== --}}
        <div class="w-3/4 mx-auto mt-10 mb-8">
            <flux:input
                wire:model.defer="search"
                wire:keydown.enter.prevent="searchNow"
                icon="magnifying-glass"
                label="Domain oder URL suchen"
                placeholder="example.com oder https://foo.example.com"
                class="w-full text-lg py-3"
            />
            <p class="mt-1 text-xs text-zinc-500">Drücke Enter, um zu suchen.</p>
        </div>

        {{-- ===== Results Area ===== --}}
        <flux:card class="w-3/4 mx-auto space-y-6">
            {{-- ONE result => card --}}
            @if($selected && count($results) === 1)
                <div class="rounded-xl border p-6 grid gap-4 bg-white/60 dark:bg-zinc-900/40 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs text-zinc-500">Domain</div>
                            <div class="text-2xl font-semibold font-mono tracking-tight">{{ $selected->host }}</div>
                        </div>
                        {{-- no "Öffnen" button per request --}}
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="rounded-lg border p-3">
                            <div class="text-xs text-zinc-500">Kategorie</div>
                            <div class="text-sm font-medium">{{ $selected->category->name ?? '–' }}</div>
                        </div>
                        <div class="rounded-lg border p-3">
                            <div class="text-xs text-zinc-500">TLD</div>
                            <div class="text-sm font-medium">{{ $selected->tld ?? '–' }}</div>
                        </div>
                        <div class="rounded-lg border p-3">
                            <div class="text-xs text-zinc-500">First seen</div>
                            <div class="text-sm font-medium">
                                {{ optional($selected->first_seen_at)->format('Y-m-d H:i') ?? '–' }}
                            </div>
                        </div>
                        <div class="rounded-lg border p-3">
                            <div class="text-xs text-zinc-500">Normalized Host</div>
                            <div class="text-sm font-mono break-all">{{ $selected->normalized_host }}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- MANY results => table --}}
            @if(!$selected && count($results) > 0)
                <div class="overflow-x-auto rounded-xl border shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-zinc-800/40">
                            <tr>
                                <th class="text-left p-3 font-semibold">Domain</th>
                                <th class="text-left p-3 font-semibold">Kategorie</th>
                                <th class="text-left p-3 font-semibold">TLD</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @foreach($results as $d)
                                {{-- Entire row clickable --}}
                                <tr
                                    wire:click="selectDomain({{ $d->id }})"
                                    class="cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800/30"
                                >
                                    <td class="p-3 font-mono">
                                        {{-- Keep pointer cursor on the text itself --}}
                                        <span
                                            role="button"
                                            class="hover:underline cursor-pointer"
                                            wire:click.stop="selectDomain({{ $d->id }})"
                                        >
                                            {{ $d->host }}
                                        </span>
                                    </td>
                                    <td class="p-3">{{ $d->category->name ?? '–' }}</td>
                                    <td class="p-3">{{ $d->tld ?? '–' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if(count($results) >= 200)
                        <div class="p-3 text-xs text-zinc-500">Ergebnisse gekürzt (max. 200 angezeigt).</div>
                    @endif
                </div>
            @endif

            {{-- No results --}}
            @if(!$selected && count($results) === 0 && trim($search) !== '' && !$errorMsg)
                <div class="text-sm text-zinc-500">Keine Treffer gefunden.</div>
            @endif

            @if($errorMsg)
                <div class="text-sm text-red-600">{{ $errorMsg }}</div>
            @endif
        </flux:card>
    </form>

    {{-- ===== Bottom-right last sync footer ===== --}}
    <div class="fixed bottom-4 right-6 text-xs text-zinc-500 bg-white/70 dark:bg-zinc-900/60 backdrop-blur px-3 py-2 rounded-lg border">
        @php
            $dt = $lastSyncAt ?? null;
        @endphp
        @if($dt)
            Letzte Synchronisierung:
            <span class="font-medium">{{ $dt->format('Y-m-d H:i') }}</span>
            <span class="ml-2">({{ $dt->diffForHumans() }})</span>
        @else
            Letzte Synchronisierung: –
        @endif
    </div>
</div>
