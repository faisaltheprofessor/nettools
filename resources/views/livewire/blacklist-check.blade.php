{{-- resources/views/livewire/blacklist-check.blade.php --}}
<div>
    {{-- Suchformular --}}
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

        {{-- Ergebnisse --}}
        <div class="max-w-5xl mx-auto px-4">
            <div class="min-h-[28rem]">
                @php
                    // Sortier-Helper: kleinere Zahl = höhere Priorität; 0 zählt als niedrigste (kommt nach allen >0)
                    $sortByPriorityField = fn($c) => ((int)($c->priority ?? 0)) > 0 ? (int)$c->priority : PHP_INT_MAX;

                    $palette = ['amber','indigo','teal','fuchsia','emerald','orange','sky','rose','violet','cyan','lime','blue','purple','pink'];

                    $bgMap = [
                        'amber'   => 'bg-amber-500/80',
                        'indigo'  => 'bg-indigo-500/80',
                        'teal'    => 'bg-teal-500/80',
                        'fuchsia' => 'bg-fuchsia-500/80',
                        'emerald' => 'bg-emerald-500/80',
                        'orange'  => 'bg-orange-500/80',
                        'sky'     => 'bg-sky-500/80',
                        'rose'    => 'bg-rose-500/80',
                        'violet'  => 'bg-violet-500/80',
                        'cyan'    => 'bg-cyan-500/80',
                        'lime'    => 'bg-lime-500/80',
                        'blue'    => 'bg-blue-500/80',
                        'purple'  => 'bg-purple-500/80',
                        'pink'    => 'bg-pink-500/80',
                        'green'   => 'bg-green-500/80',
                        'red'     => 'bg-red-500/80',
                    ];

                    $colorMap = function(string $slug) use ($palette, $bgMap) {
                        if ($slug === 'whitelist') return ['badge' => 'green', 'bar' => $bgMap['green']];
                        if ($slug === 'blacklist') return ['badge' => 'red',   'bar' => $bgMap['red']];
                        $base = $palette[crc32($slug) % count($palette)];
                        return ['badge' => $base, 'bar' => $bgMap[$base]];
                    };
                @endphp

                @if($hasSearched)
                    <flux:card class="space-y-6 h-full">
                        @if($selected && count($results) === 1)
                            <div class="flex items-center justify-between">
                                <flux:button
                                    icon="arrow-left"
                                    wire:click="backToList"
                                    class="cursor-pointer"
                                    size="sm"
                                >
                                    Zurück zur Ergebnisliste
                                </flux:button>
                                <div class="text-xs text-zinc-500">
                                    {{ $selected->categories?->count() ?? 0 }} Kategorien
                                </div>
                            </div>

                            @php $cats = $selected->categories->sortBy($sortByPriorityField)->values(); @endphp
                            @foreach($cats as $i => $cat)
                                @php $col = $colorMap($cat->slug); @endphp
                                <div class="rounded-xl border p-6 grid gap-4 bg-white/60 dark:bg-zinc-900/40 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs text-zinc-500">Domain</div>
                                            <div class="text-2xl font-semibold font-mono tracking-tight">{{ $selected->host }}</div>
                                        </div>
                                        <flux:badge color="{{ $col['badge'] }}">
                                            {{ $cat->slug }} • Priorität {{ $i + 1 }}
                                        </flux:badge>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div class="rounded-lg border p-3">
                                            <div class="text-xs text-zinc-500">Kategorie</div>
                                            <div class="text-sm font-medium">{{ $cat->slug }}</div>
                                        </div>
                                        <div class="rounded-lg border p-3">
                                            <div class="text-xs text-zinc-500">Erst gesehen</div>
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
                                        <th class="text-left p-3 font-semibold">Erst gesehen</th>
                                    </tr>
                                    </thead>

                                    {{-- Tooltip-Logik basiert ausschließlich auf Priorität (keine "neutral"-Konzeption) --}}
                                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                    @php
                                        $tieRuleLabel = config('domains.tie_breaker_label', 'Systemregel');
                                        $prioVal = function ($p) { return ($p && (int)$p > 0) ? (int)$p : PHP_INT_MAX; }; // 0 = niedrigste
                                    @endphp

                                    @foreach($results as $d)
                                        @php
                                            $cats = $d->categories ? $d->categories->sortBy(fn($c) => $prioVal($c->priority))->values() : collect();
                                            $minVal = $cats->isEmpty() ? PHP_INT_MAX : $cats->map(fn($c) => $prioVal($c->priority))->min();
                                            $tieSet = $cats->filter(fn($c) => $prioVal($c->priority) === $minVal)->values();
                                            $isTie  = $tieSet->count() > 1;
                                            $winner = $isTie ? null : $cats->first(); // kleinstes Prio
                                        @endphp

                                        @foreach($cats as $i => $cat)
                                            @php
                                                $col = $colorMap($cat->slug);

                                                $others = $d->categories?->filter(fn($c) => $c->id !== $cat->id)->values() ?? collect();
                                                $othersList = $others->pluck('slug')->join(', ');

                                                $currentVal = $prioVal($cat->priority);
                                                $wins   = !$isTie && $winner && $winner->id === $cat->id;
                                                $loses  = !$isTie && $currentVal > $minVal;
                                                $equal  =  $isTie && $tieSet->contains(fn($t) => $t->id === $cat->id);

                                                $tooltip = '';
                                                if ($others->isNotEmpty()) {
                                                    if ($wins) {
                                                        $tooltip = "Auch gelistet in: <span class='text-amber-500'>{$othersList}</span>. <p><span class='text-lime-500'>{$cat->slug}</span> hat hier die höhere Priorität.<p> <p class=' text-teal-500 font-bold'> Diese Kategorie ist wirksam.";
                                                    } elseif ($loses) {
                                                        // Eine andere Liste hat eine höhere Priorität
                                                        $winningSlug = $winner?->slug;
                                                        if ($winningSlug) {
                                                            $tooltip = "Ebenfalls in: <span class='text-amber-500'>{$othersList}</span>. <p><span class='text-lime-500'>{$winningSlug}</span> hat die höhere Priorität.</p> <p class=' text-teal-500'> Die Entscheidung richtet sich nicht nach dieser Kategorie.";
                                                        } else {
                                                            $tooltip = "Ebenfalls in: {$othersList}. Eine andere Liste hat die höhere Priorität. <p class=' text-teal-500'> Die Entscheidung richtet sich nicht nach dieser Kategorie.";
                                                        }
                                                    }
                                                }
                                            @endphp

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
                                                    <div class="flex items-center gap-2">
                                                        <flux:badge color="{{ $col['badge'] }}">
                                                            {{ $cat->slug }}
                                                        </flux:badge>
                                                    </div>
                                                </td>

                                                {{-- Tooltip NACH der Prioritätsnummer, nur wenn mehrere Listen existieren --}}
                                                <td class="p-3">
                                                    <div class="flex items-center gap-2">
                                                        <span>#{{ $i + 1 }}</span>
                                                        @if($others->isNotEmpty() && !empty($tooltip))
                                                            <flux:tooltip>
                                                                <flux:icon.information-circle size="sm" variant="solid" class="text-emerald-500 dark:text-emerald-300" />
                                                                <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                                                    {!! $tooltip !!}
                                                                </flux:tooltip.content>
                                                            </flux:tooltip>
                                                        @endif
                                                    </div>
                                                </td>

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
                    <div
                        class="h-full rounded-xl border border-dashed p-10 text-center text-sm text-zinc-500 flex items-center justify-center">
                        Ergebnisse erscheinen hier nach der Suche.
                    </div>
                @endif
            </div>
        </div>

        {{-- Statistik --}}
        <div class="max-w-5xl mx-auto px-4 mt-6 mb-16">
            <flux:accordion>
                <flux:accordion.item expanded>
                    <flux:accordion.heading>Statistik</flux:accordion.heading>
                    <flux:accordion.content>
                        @php
                            $max   = !empty($chartData) ? max(array_column($chartData, 'count')) : 0;
                            $total = !empty($chartData) ? array_sum(array_column($chartData, 'count')) : 0;
                        @endphp

                        @if(!empty($chartData) && $max > 0)
                            <div class="rounded-xl border bg-white/90 dark:bg-zinc-900/80 p-4 shadow-sm space-y-4">
                                <div class="space-y-2">
                                    @foreach($chartData as $row)
                                        @php
                                            $pct = $max > 0 ? round(($row['count'] / $max) * 100, 2) : 0;
                                            $col = $colorMap($row['slug']);
                                        @endphp
                                        <div class="grid grid-cols-6 items-center gap-3 group">
                                            <button
                                                type="button"
                                                wire:click="openCategory('{{ $row['slug'] }}')"
                                                class="col-span-2 text-left truncate text-sm text-zinc-700 dark:text-zinc-300 transition hover:text-blue-600 dark:hover:text-blue-400 cursor-pointer focus:outline-none"
                                                title="Alle Einträge in {{ $row['slug'] }} anzeigen"
                                            >
                                                {{ $row['slug'] }}
                                            </button>
                                            <div class="col-span-3">
                                                <div
                                                    class="h-3 w-full rounded-md bg-zinc-100 dark:bg-zinc-800 border border-zinc-200/60 dark:border-zinc-700/60 overflow-hidden">
                                                    <div
                                                        class="h-full rounded-md {{ $col['bar'] }} transition-all duration-300 group-hover:opacity-90"
                                                        style="width: {{ $pct }}%;"
                                                        aria-hidden="true"
                                                    ></div>
                                                </div>
                                            </div>
                                            <div
                                                class="col-span-1 text-right tabular-nums text-sm text-zinc-600 dark:text-zinc-400">
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

    {{-- Kategorien-Modal (ohne Tooltip in den Zeilen) --}}
    <flux:modal :dismissible="false" name="category-modal" wire:model.self="showCategoryModal" class="min-w-[48rem]">
        <div class="space-y-4">
            @php
                $palette_modal = ['amber','indigo','teal','fuchsia','emerald','orange','sky','rose','violet','cyan','lime','blue','purple','pink'];
                $colorMapBadge = function(?string $slug) use ($palette_modal) {
                    if (!$slug) return 'indigo';
                    if ($slug === 'whitelist') return 'green';
                    if ($slug === 'blacklist') return 'red';
                    $base = $palette_modal[crc32($slug) % count($palette_modal)];
                    return $base;
                };
            @endphp

            <div class="flex items-center justify-between">
                <div class="font-medium flex items-center gap-2">
                    <span>Kategorie:</span>
                    @if($categorySlug)
                        <flux:badge color="{{ $colorMapBadge($categorySlug) }}">{{ $categorySlug }}</flux:badge>
                    @else
                        —
                    @endif
                </div>
                <div class="flex gap-2">
                    <flux:input
                        wire:model.live.debounce.300ms="categorySearch"
                        placeholder="Suchbegriff…"
                        class="w-96 mr-8"
                    />
                </div>
            </div>

            @if($categoryPage instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <flux:table :paginate="$categoryPage" class="table-fixed">
                    <flux:table.columns>
                        <flux:table.column class="w-1/2">Domain</flux:table.column>
                        <flux:table.column class="w-1/2">In anderen Listen</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($categoryPage as $d)
                            @php
                                $main      = $d->categories->firstWhere('slug', $categorySlug);
                                $mainPrio  = $main?->priority ?? 0;
                                $others    = $d->categories->filter(fn($c) => $c->slug !== $categorySlug)->values();

                                $relText = function($c) use ($mainPrio) {
                                    $cp = (int)$c->priority;
                                    return $cp < (int)$mainPrio ? 'höhere Priorität' : ($cp > (int)$mainPrio ? 'niedrigere Priorität' : 'gleiche Priorität');
                                };
                            @endphp

                            <flux:table.row :key="$d->id">
                                <flux:table.cell class="font-mono whitespace-normal break-words">
                                    {{ $d->host }}
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-normal break-words text-sm leading-relaxed">
                                    @if($others->isEmpty())
                                        <span class="block ml-5"><flux:icon.minus /></span>
                                    @else
                                        <div class="space-y-1">
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($others->sortBy('priority') as $c)
                                                    <div class="flex items-center gap-2">
                                                        <flux:badge color="{{ $colorMapBadge($c->slug) }}">{{ $c->slug }}</flux:badge>
                                                        <span class="text-zinc-500">({{ $relText($c) }})</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <div class="p-8 text-center text-sm text-zinc-500 border rounded-lg">
                    Keine Daten verfügbar.
                </div>
            @endif
        </div>
    </flux:modal>
</div>
