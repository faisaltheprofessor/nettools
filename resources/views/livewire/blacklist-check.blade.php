<div>
    <form wire:submit.prevent="searchNow">
        <div
            x-data="{ open: @entangle('acOpen').live, idx: @entangle('acIndex').live }"
            class="max-w-5xl mx-auto px-4 mt-10 mb-2 relative"
        >
            <label class="block text-sm text-zinc-600 mb-1">Domain oder URL suchen</label>

            <flux:input
                type="text"
                clearable
                wire:model.live.debounce.150ms="search"
                @keydown.arrow-down.prevent="$wire.acMove(1)"
                @keydown.arrow-up.prevent="$wire.acMove(-1)"
                @keydown.enter.prevent="
                    if (open && idx >= 0) { $wire.acSelectCurrent() }
                    else { open = false; $wire.set('acBlock', true); $wire.searchNow() }
                "
                @keydown.escape.prevent="open = false"
                placeholder="berlin.de"
                autocomplete="off"
            />

            <div
                x-show="open"
                x-transition
                class="absolute z-30 mt-1 w-full rounded-xl border bg-white dark:bg-zinc-900
                       border-zinc-200 dark:border-zinc-700 shadow-lg"
                @click.outside="open = false"
            >
                @if(!empty($acSuggestions))
                    <div class="max-h-80 overflow-auto py-2">
                        @php $row = 0; @endphp
                        @foreach($acSuggestions as $group)
                            <div class="px-3 pt-2 pb-1 text-xs uppercase tracking-wide text-zinc-500">
                                {{ $group['category'] }}
                            </div>
                            @foreach($group['items'] as $it)
                                <button
                                    type="button"
                                    wire:click="acClickSelect({{ $it['id'] }}, @js($it['host']))"
                                    @mouseenter="idx = {{ $row }}"
                                    :class="idx === {{ $row }} ? 'bg-blue-50 dark:bg-blue-950/40' : ''"
                                    class="w-full text-left px-3 py-2 hover:bg-blue-50 dark:hover:bg-blue-950/40 cursor-pointer"
                                >
                                    <span class="font-mono text-sm">{{ $it['host'] }}</span>
                                </button>
                                @php $row++; @endphp
                            @endforeach
                        @endforeach
                    </div>
                @else
                    <div class="px-3 py-3 text-sm text-zinc-500">Keine Vorschläge</div>
                @endif
            </div>
        </div>

        <div class="max-w-5xl mx-auto px-4 mb-4"></div>

        <div class="max-w-5xl mx-auto px-4">
            <div class="min-h-[28rem]">
                @if($hasSearched)
                    <flux:card class="space-y-6 h-full">
                        @php
                            $badgeClass = fn($slug) => match($slug) {
                                'whitelist' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                                'blacklist' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                default     => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                            };
                            $sortByPriorityField = fn($c) => ((int)($c->priority ?? 0)) > 0 ? (int)$c->priority : PHP_INT_MAX;
                        @endphp

                        @if($selected && count($results) === 1)
                            @php $cats = $selected->categories->sortBy($sortByPriorityField)->values(); @endphp
                            @foreach($cats as $i => $cat)
                                <div class="rounded-xl border p-6 grid gap-4 bg-white/60 dark:bg-zinc-900/40 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs text-zinc-500">Domain</div>
                                            <div class="text-2xl font-semibold font-mono tracking-tight">{{ $selected->host }}</div>
                                        </div>
                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $badgeClass($cat->slug) }}">
                                            {{ $cat->slug }} • Priorität {{ $i + 1 }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div class="rounded-lg border p-3">
                                            <div class="text-xs text-zinc-500">Kategorie</div>
                                            <div class="text-sm font-medium">{{ $cat->slug }}</div>
                                        </div>
                                        <div class="rounded-lg border p-3">
                                            <div class="text-xs text-zinc-500">First seen</div>
                                            <div class="text-sm font-medium">
                                                {{ optional($selected->first_seen_at)->format('Y-m-d H:i') ?? '–' }}
                                            </div>
                                        </div>
                                        <div class="rounded-lg border p-3">
                                            <div class="text-xs text-zinc-500">Priorität</div>
                                            <div class="text-sm font-medium">#{{ $i + 1 }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        @if(!$selected && count($results) > 0)
                            <div class="overflow-x-auto rounded-xl border shadow-sm">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 dark:bg-zinc-800/40">
                                        <tr>
                                            <th class="text-left p-3 font-semibold">Domain</th>
                                            <th class="text-left p-3 font-semibold">Kategorie</th>
                                            <th class="text-left p-3 font-semibold">Priorität</th>
                                            <th class="text-left p-3 font-semibold">First seen</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                        @foreach($results as $d)
                                            @php $cats = $d->categories ? $d->categories->sortBy($sortByPriorityField)->values() : collect(); @endphp
                                            @foreach($cats as $i => $cat)
                                                <tr
                                                    wire:click="selectDomain({{ $d->id }})"
                                                    class="cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800/30"
                                                >
                                                    <td class="p-3 font-mono">
                                                        <span class="hover:underline cursor-pointer"
                                                              wire:click.stop="selectDomain({{ $d->id }})">
                                                            {{ $d->host }}
                                                        </span>
                                                    </td>
                                                    <td class="p-3">
                                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $badgeClass($cat->slug) }}">
                                                            {{ $cat->slug }}
                                                        </span>
                                                    </td>
                                                    <td class="p-3">#{{ $i + 1 }}</td>
                                                    <td class="p-3">
                                                        {{ optional($d->first_seen_at)->format('Y-m-d H:i') ?? '–' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if(!$selected && count($results) === 0 && trim($search) !== '' && !$errorMsg)
                            <div class="text-sm text-zinc-500">Keine Treffer gefunden.</div>
                        @endif

                        @if($errorMsg)
                            <div class="text-sm text-red-600">{{ $errorMsg }}</div>
                        @endif
                    </flux:card>
                @else
                    <div class="h-full rounded-xl border border-dashed p-10 text-center text-sm text-zinc-500 flex items-center justify-center">
                        Ergebnisse erscheinen hier nach der Suche.
                    </div>
                @endif
            </div>
        </div>

        <div class="max-w-5xl mx-auto px-4 mt-6 mb-16">
            <flux:accordion>
                <flux:accordion.item expanded>
                    <flux:accordion.heading>Statistik</flux:accordion.heading>
                    <flux:accordion.content>
                        @php
                            $max   = !empty($chartData) ? max(array_column($chartData, 'count')) : 0;
                            $total = !empty($chartData) ? array_sum(array_column($chartData, 'count')) : 0;
                            $colorFor = function($slug) {
                                return match($slug) {
                                    'whitelist' => 'bg-green-500/80',
                                    'blacklist' => 'bg-red-500/80',
                                    default => [
                                        'bg-amber-500/80','bg-indigo-500/80','bg-teal-500/80','bg-fuchsia-500/80',
                                        'bg-emerald-500/80','bg-orange-500/80','bg-sky-500/80','bg-rose-500/80'
                                    ][crc32($slug) % 8]
                                };
                            };
                        @endphp

                        @if(!empty($chartData) && $max > 0)
                            <div class="rounded-xl border bg-white/90 dark:bg-zinc-900/80 p-4 shadow-sm space-y-4">
                                <div class="space-y-2">
                                    @foreach($chartData as $row)
                                        @php $pct = $max > 0 ? round(($row['count'] / $max) * 100, 2) : 0; @endphp
                                        <div class="grid grid-cols-6 items-center gap-3">
                                            <div class="col-span-2 truncate text-sm text-zinc-700 dark:text-zinc-300">
                                                {{ $row['slug'] }}
                                            </div>
                                            <div class="col-span-3">
                                                <div class="h-3 w-full rounded-md bg-zinc-100 dark:bg-zinc-800 border border-zinc-200/60 dark:border-zinc-700/60 overflow-hidden">
                                                    <div class="h-full rounded-md {{ $colorFor($row['slug']) }}" style="width: {{ $pct }}%;" aria-hidden="true"></div>
                                                </div>
                                            </div>
                                            <div class="col-span-1 text-right tabular-nums text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ $row['count'] }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="text-xs text-zinc-600 dark:text-zinc-400">
                                    Gesamt: <span class="font-medium">{{ $total }}</span> Domains
                                    @if(isset($lastSyncAt) && $lastSyncAt)
                                        <span class="ml-2">• Stand: {{ $lastSyncAt->format('Y-m-d H:i') }} ({{ $lastSyncAt->diffForHumans() }})</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="rounded-xl border border-dashed p-8 text-sm text-zinc-500 text-center">
                                Keine Daten für die Statistik.
                            </div>
                        @endif
                    </flux:accordion.content>
                </flux:accordion.item>
            </flux:accordion>
        </div>
    </form>
</div>
