{{-- resources/views/livewire/feedback-viewer.blade.php --}}
<flux:card class="mt-4 space-y-4">

    {{-- Filters (Accordion) --}}
    <flux:accordion class="rounded-2xl border border-zinc-200 dark:border-zinc-700">
        <flux:accordion.item open>
            <flux:accordion.heading>Filter</flux:accordion.heading>

            <flux:accordion.content>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <flux:select wire:model.live="filterType" placeholder="Typ">
                        <flux:select.option value="">Alle Typen</flux:select.option>
                        <flux:select.option value="feature">Feature</flux:select.option>
                        <flux:select.option value="bug">Bug</flux:select.option>
                        <flux:select.option value="feedback">Feedback</flux:select.option>
                    </flux:select>

                    <flux:select wire:model.live="filterStatus" placeholder="Status">
                        <flux:select.option value="">Alle Status</flux:select.option>
                        <flux:select.option value="open">Open</flux:select.option>
                        <flux:select.option value="in_progress">In Progress</flux:select.option>
                        <flux:select.option value="resolved">Resolved</flux:select.option>
                        <flux:select.option value="closed">Closed</flux:select.option>
                        <flux:select.option value="wontfix">Won’t Fix</flux:select.option>
                    </flux:select>

                    <flux:input wire:model.live="filterUser" placeholder="User filtern (Name)"/>
                    <div class="md:col-span-2">
                        <flux:input wire:model.live="search" placeholder="Suche Titel/Beschreibung"/>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2">
                    <flux:button variant="outline" size="sm" class="cursor-pointer" wire:click="clearFilters">
                        Zurücksetzen
                    </flux:button>
                </div>
            </flux:accordion.content>
        </flux:accordion.item>
    </flux:accordion>

    {{-- Tabelle --}}
    <div class="overflow-auto" style="min-height: 300px;">
        <flux:table :paginate="$feedbacks">
            <flux:table.columns>
                <flux:table.column
                    wire:click="sortBy('type')"
                    sortable
                    :direction="$sortField === 'type' ? $sortDirection : null"
                    class="px-2">Typ</flux:table.column>

                <flux:table.column
                    wire:click="sortBy('user.name')"
                    sortable
                    :direction="$sortField === 'user.name' ? $sortDirection : null">User</flux:table.column>

                <flux:table.column>Status</flux:table.column>

                <flux:table.column
                    wire:click="sortBy('created_at')"
                    sortable
                    :direction="$sortField === 'created_at' ? $sortDirection : null">Datum</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($feedbacks as $fb)
                    <flux:table.row
                        wire:click="selectFeedback({{ $fb->id }})"
                        class="cursor-pointer hover:bg-gray-100">
                        <flux:table.cell>{{ ucfirst($fb->type) }}</flux:table.cell>
                        <flux:table.cell>{{ $fb->user->name ?? 'Unknown' }}</flux:table.cell>
                        <flux:table.cell>
                            @php
                                $badge = [
                                    'open'        => ['gray','Open'],
                                    'in_progress' => ['blue','In Progress'],
                                    'resolved'    => ['green','Resolved'],
                                    'closed'      => ['zinc','Closed'],
                                    'wontfix'     => ['orange','Won’t Fix'],
                                ][$fb->status] ?? ['zinc',$fb->status];
                            @endphp
                            <flux:badge color="{{ $badge[0] }}" size="sm">{{ $badge[1] }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $fb->created_at->format('Y-m-d H:i') }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Forum / Diskussion --}}
    @if ($selectedFeedback)
        <flux:card>
            {{-- Meta --}}
            <div class="mb-3 text-sm space-y-1">
                <div><span class="font-semibold">Typ:</span> {{ ucfirst($selectedFeedback->type) }}</div>
                <div><span class="font-semibold">User:</span> {{ $selectedFeedback->user->name ?? 'Unknown' }}</div>
                <div><span class="font-semibold">Datum:</span> {{ $selectedFeedback->created_at->format('Y-m-d H:i') }}</div>

                <div class="flex items-center gap-2">
                    <span class="font-semibold">Status:</span>
                    @php
                        $badge = [
                            'open'        => ['gray','Open'],
                            'in_progress' => ['blue','In Progress'],
                            'resolved'    => ['green','Resolved'],
                            'closed'      => ['zinc','Closed'],
                            'wontfix'     => ['orange','Won’t Fix'],
                        ][$selectedFeedback->status] ?? ['zinc',$selectedFeedback->status];
                    @endphp
                    <flux:badge color="{{ $badge[0] }}" size="sm">{{ $badge[1] }}</flux:badge>

                    <flux:dropdown>
                        <flux:button variant="outline" size="sm">Status ändern</flux:button>
                        <flux:menu>
                            <flux:menu.item wire:click="setStatus('open')">Open</flux:menu.item>
                            <flux:menu.item wire:click="setStatus('in_progress')">In Progress</flux:menu.item>
                            <flux:menu.item wire:click="setStatus('resolved')">Resolved</flux:menu.item>
                            <flux:menu.item wire:click="setStatus('closed')">Closed</flux:menu.item>
                            <flux:menu.item wire:click="setStatus('wontfix')">Won’t Fix</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>

            {{-- Titel & Beschreibung --}}
            <h2 class="text-xl font-bold mb-1">{{ $selectedFeedback->title }}</h2>
            <p class="mb-4">{{ $selectedFeedback->description }}</p>

            {{-- Anhänge --}}
            @php
                $attachments = $selectedFeedback->attachments;
                if (!is_array($attachments)) {
                    $attachments = json_decode($attachments ?? '[]', true) ?: [];
                }
            @endphp
            @if (count($attachments) > 0)
                <div class="flex flex-wrap justify-center gap-4 mb-6">
                    @foreach ($attachments as $file)
                        <div class="p-2 bg-gray-50 border rounded shadow-sm">
                            <img
                                src="{{ asset('storage/feedback/' . basename($file)) }}"
                                alt="Attachment"
                                class="w-64 object-cover rounded-md border shadow-sm">
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Thread --}}
            <div class="space-y-3">
                <flux:heading size="md">Diskussion</flux:heading>

                {{-- Neuer Kommentar --}}
                <div class="flex items-start gap-2">
                    <flux:textarea class="flex-1" rows="3" wire:model.defer="newComment" placeholder="Schreibe eine Antwort..."></flux:textarea>
                    <flux:button variant="primary" color="green" class="self-start cursor-pointer" wire:click="addComment">Posten</flux:button>
                </div>

                {{-- Kommentare --}}
                <div class="space-y-3">
                    @forelse ($selectedFeedback->comments as $c)
                        <div id="comment-{{ $c->id }}" class="rounded-lg border p-3 bg-white/50 dark:bg-zinc-900/30" wire:key="comment-{{ $c->id }}">
                            <div class="text-xs text-zinc-500 mb-1">
                                <span class="font-medium">{{ $c->user->name ?? 'Unknown' }}</span>
                                • {{ $c->created_at->diffForHumans() }}
                            </div>

                            <div class="whitespace-pre-wrap text-sm">{{ $c->body }}</div>

                            {{-- Reactions: long-hover refreshes then shows tooltip; click toggles reaction --}}
                            <div class="mt-2 flex items-center gap-2">
                                @php $available = ['👍','❤️','😂','🚀']; @endphp

                                @foreach ($available as $emoji)
                                    @php
                                        $list   = $c->reactions->where('emoji', $emoji);
                                        $count  = $list->count();
                                        $active = $list->where('user_guid', auth()->user()->guid)->isNotEmpty();
                                    @endphp

                                    <div class="relative inline-block"
                                         x-data="{t:null, show:false}"
                                         x-on:mouseenter="
                                            t=setTimeout(() => {
                                                $wire.refreshReactions({{ $c->id }}).then(() => { show = true })
                                            }, 1200)
                                         "
                                         x-on:mouseleave="clearTimeout(t); show=false">

                                        {{-- Button: green if active, gray if not; click toggles --}}
                                        <button type="button"
                                                class="cursor-pointer px-2 py-1 text-sm rounded
                                                       {{ $active
                                                            ? 'bg-green-100 focus:bg-green-100 hover:bg-green-100 dark:bg-green-900/30'
                                                            : 'bg-gray-100 hover:bg-gray-100 dark:bg-zinc-800' }}"
                                                x-on:click.stop="$wire.toggleReaction({{ $c->id }}, '{{ $emoji }}')">
                                            {{ $emoji }}
                                            @if($count > 0)
                                                <span class="ml-1 text-xs">{{ $count }}</span>
                                            @endif
                                        </button>

                                        {{-- Hover tooltip (popover-like), top/start --}}
                                        <div x-show="show" x-transition.opacity
                                             class="absolute -top-2 translate-y-[-100%] left-0 z-20 min-w-56 rounded-lg border border-zinc-200 bg-white p-3 shadow-lg dark:bg-zinc-900 dark:border-zinc-700"
                                             style="pointer-events:none">
                                            <div class="text-xs text-zinc-500 mb-2">
                                                {{ $emoji }} Reaktionen • {{ $count }}
                                            </div>
                                            @php $freshList = $c->reactions->where('emoji', $emoji); @endphp
                                            @if ($freshList->count() > 0)
                                                <ul class="text-sm space-y-1">
                                                    @foreach ($freshList as $reaction)
                                                        @php
                                                            $isYou = $reaction->user_guid === auth()->user()->guid;
                                                            $name  = optional($reaction->user)->name ?? 'Unknown';
                                                        @endphp
                                                        <li class="flex items-center gap-2">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                                                            <span>
                                                                {{ $name }}
                                                                @if($isYou) <span class="text-zinc-500">(du)</span> @endif
                                                            </span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <div class="text-sm italic text-zinc-500">
                                                    Noch niemand hat mit {{ $emoji }} reagiert.
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 italic">Noch keine Kommentare.</p>
                    @endforelse
                </div>
            </div>
        </flux:card>
    @endif
</flux:card>
