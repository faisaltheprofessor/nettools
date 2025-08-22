<flux:card class="mt-4 space-y-4">

    {{-- Filters --}}
    <flux:accordion class="rounded-2xl border border-zinc-200 dark:border-zinc-700">
        <flux:accordion.item open>
            <flux:accordion.heading>Filter</flux:accordion.heading>
            <flux:accordion.content>
                <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
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

                    <flux:input wire:model.live="filterUser" placeholder="User (Name)"/>
                    <div class="md:col-span-2">
                        <flux:input wire:model.live="search" placeholder="Suche Titel/Beschreibung"/>
                    </div>

                    <div class="flex items-center">
                        <flux:button variant="outline" size="sm" class="cursor-pointer" wire:click="clearFilters">
                            Zurücksetzen
                        </flux:button>
                    </div>
                </div>
            </flux:accordion.content>
        </flux:accordion.item>
    </flux:accordion>

    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($feedbacks as $fb)
            @php
                $statusBadge = [
                    'open'        => ['gray','Open'],
                    'in_progress' => ['blue','In Progress'],
                    'resolved'    => ['green','Resolved'],
                    'closed'      => ['zinc','Closed'],
                    'wontfix'     => ['orange','Won’t Fix'],
                ][$fb->status] ?? ['zinc',$fb->status];
            @endphp

            <flux:card class="transition hover:shadow-lg cursor-pointer"
                       wire:click="selectFeedback({{ $fb->id }})" wire:key="card-{{ $fb->id }}">
                <div class="flex items-center justify-between mb-2">
                    <flux:badge size="sm" color="{{ $fb->type === 'bug' ? 'red' : ($fb->type === 'feature' ? 'blue' : 'zinc') }}">
                        {{ ucfirst($fb->type) }}
                    </flux:badge>
                    <flux:badge size="sm" color="{{ $statusBadge[0] }}">{{ $statusBadge[1] }}</flux:badge>
                </div>

                <div class="font-semibold">{{ $fb->title }}</div>

                <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300 line-clamp-3">
                    {{ $fb->description }}
                </div>

                <div class="mt-3 text-xs text-zinc-500 flex items-center justify-between">
                    <span>{{ $fb->user->name ?? 'Unknown' }}</span>
                    <span>{{ $fb->created_at->format('Y-m-d H:i') }}</span>
                </div>
            </flux:card>
        @endforeach
    </div>

    <div>
        {{ $feedbacks->links() }}
    </div>

    {{-- Detail Panel --}}
    @if ($selectedFeedback)
        <flux:card class="mt-4 space-y-4">
            {{-- Header --}}
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-3">
                    <flux:badge size="sm" color="{{ $selectedFeedback->type === 'bug' ? 'red' : ($selectedFeedback->type === 'feature' ? 'blue' : 'zinc') }}">
                        {{ ucfirst($selectedFeedback->type) }}
                    </flux:badge>

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
                </div>

                <div class="text-xs text-zinc-500">
                    {{ $selectedFeedback->user->name ?? 'Unknown' }} • {{ $selectedFeedback->created_at->format('Y-m-d H:i') }}
                </div>
            </div>

            {{-- Title & Description --}}
            <h2 class="text-xl font-bold">{{ $selectedFeedback->title }}</h2>
            <p class="whitespace-pre-wrap">{{ $selectedFeedback->description }}</p>

            {{-- Attachments --}}
            @php
                $attachments = $selectedFeedback->attachments;
                if (!is_array($attachments)) {
                    $attachments = json_decode($attachments ?? '[]', true) ?: [];
                }
            @endphp
            @if (count($attachments) > 0)
                <div class="flex flex-wrap gap-3">
                    @foreach ($attachments as $path)
                        <img src="{{ Storage::url($path) }}" class="w-40 h-40 object-cover rounded-lg border" alt="Attachment">
                    @endforeach
                </div>
            @endif

            {{-- Status change --}}
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium">Status ändern:</span>
                <flux:dropdown>
                    <flux:button variant="outline" size="sm">Auswählen</flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="setStatus('open')">Open</flux:menu.item>
                        <flux:menu.item wire:click="setStatus('in_progress')">In Progress</flux:menu.item>
                        <flux:menu.item wire:click="setStatus('resolved')">Resolved</flux:menu.item>
                        <flux:menu.item wire:click="setStatus('closed')">Closed</flux:menu.item>
                        <flux:menu.item wire:click="setStatus('wontfix')">Won’t Fix</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>

            <flux:separator />

            {{-- Discussion --}}
            <flux:heading size="md">Diskussion</flux:heading>

            {{-- New top-level comment (with @mentions) --}}
            <div
                x-data="mentionBox({
                    fetch: (q) => $wire.searchMentions(q),
                    pick: (name) => $wire.replyWithMention(name, null) // prepend to top-level draft
                })"
                class="relative"
            >
                <flux:textarea
                    x-ref="ta"
                    class="w-full"
                    rows="4"
                    wire:model.defer="newComment"
                    placeholder="Schreibe eine Antwort… (mit @Name erwähnen)"
                    @input.debounce.200ms="onInput"
                    @keydown.down.prevent="move(1)"
                    @keydown.up.prevent="move(-1)"
                    @keydown.enter.prevent="confirm()"
                    @click="onInput"
                ></flux:textarea>

                {{-- Mentions dropdown (names only, padded) --}}
                <div
                    x-show="$wire.mentionResults.length > 0 && open"
                    x-transition
                    class="absolute z-20 mt-1 w-full max-w-md rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg overflow-hidden"
                >
                    <template x-for="(u, idx) in $wire.mentionResults" :key="u.guid">
                        <button
                            type="button"
                            class="flex w-full items-center px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
                            :class="idx === active ? 'bg-zinc-100 dark:bg-zinc-800' : ''"
                            @click="choose(idx)"
                        >
                            <span class="truncate" x-text="u.name"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="mt-2">
                <flux:button variant="primary" color="green" class="cursor-pointer" wire:click="addComment">
                    Posten
                </flux:button>
            </div>

            {{-- Comments + one-level nested replies --}}
            <div class="mt-4 space-y-3">
                @php
                    $all = $selectedFeedback->comments->sortBy('created_at');
                    $roots = $all->whereNull('parent_id');
                @endphp

                @foreach ($roots as $c)
                    @php
                        $name = $c->user->name ?? 'Unknown';
                        $safe = e($c->body);
                        $highlighted = preg_replace('/(^|\\s)@([\\p{L}0-9._-]+)/u', '$1<span class="text-sky-600">@${2}</span>', $safe);
                        $children = $all->where('parent_id', $c->id);
                    @endphp

                    {{-- Root comment --}}
                    <div id="comment-{{ $c->id }}" class="rounded-lg border p-3 bg-white/50 dark:bg-zinc-900/30" wire:key="c-{{ $c->id }}">
                        <div class="text-xs text-zinc-500 mb-1">
                            <span class="font-medium">{{ $name }}</span>
                            • {{ $c->created_at->diffForHumans() }}
                        </div>

                        <div class="prose dark:prose-invert max-w-none whitespace-pre-wrap text-sm">{!! $highlighted !!}</div>

                        <div class="mt-2 flex items-center gap-3">
                            {{-- Inline reply toggler --}}
                            <button type="button" class="text-xs text-sky-600 hover:underline"
                                    wire:click="openReply({{ $c->id }})">
                                Antworten
                            </button>

                            {{-- Reactions (unchanged behavior) --}}
                            @php $available = ['👍','❤️','😂','🚀']; @endphp
                            <div class="flex items-center gap-2">
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
                                                            $rname = optional($reaction->user)->name ?? 'Unknown';
                                                        @endphp
                                                        <li class="flex items-center gap-2">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                                                            <span>
                                                                {{ $rname }}
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

                        {{-- Inline reply box (shows when active) --}}
                        @if ($activeReplyFor === $c->id)
                            <div class="mt-3 pl-3 border-l">
                                <div
                                    x-data="mentionBox({
                                        fetch: (q) => $wire.searchMentions(q),
                                        pick: (name) => $wire.replyWithMention(name, {{ $c->id }})
                                    })"
                                    class="relative"
                                >
                                    <flux:textarea
                                        x-ref="ta"
                                        class="w-full"
                                        rows="3"
                                        wire:model.defer="replyBoxes.{{ $c->id }}"
                                        placeholder="Antwort verfassen… (mit @Name erwähnen)"
                                        @input.debounce.200ms="onInput"
                                        @keydown.down.prevent="move(1)"
                                        @keydown.up.prevent="move(-1)"
                                        @keydown.enter.prevent="confirm()"
                                        @click="onInput"
                                    ></flux:textarea>

                                    {{-- Mentions dropdown: clean, padded, name only --}}
                                    <div
                                        x-show="$wire.mentionResults.length > 0 && open"
                                        x-transition
                                        class="absolute z-20 mt-1 w-full max-w-md rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg overflow-hidden"
                                    >
                                        <template x-for="(u, idx) in $wire.mentionResults" :key="u.guid">
                                            <button
                                                type="button"
                                                class="flex w-full items-center px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
                                                :class="idx === active ? 'bg-zinc-100 dark:bg-zinc-800' : ''"
                                                @click="choose(idx)"
                                            >
                                                <span class="truncate" x-text="u.name"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div class="mt-2 flex items-center gap-2">
                                    <flux:button size="sm" variant="primary" color="green" class="cursor-pointer" wire:click="addReply({{ $c->id }})">
                                        Antworten posten
                                    </flux:button>
                                    <flux:button size="sm" variant="outline" class="cursor-pointer" wire:click="$set('activeReplyFor', null)">
                                        Abbrechen
                                    </flux:button>
                                </div>
                            </div>
                        @endif

                        {{-- Children --}}
                        @if ($children->count())
                            <div class="mt-3 space-y-3">
                                @foreach ($children as $r)
                                    @php
                                        $rname = $r->user->name ?? 'Unknown';
                                        $rsafe = e($r->body);
                                        $rhl   = preg_replace('/(^|\\s)@([\\p{L}0-9._-]+)/u', '$1<span class="text-sky-600">@${2}</span>', $rsafe);
                                    @endphp

                                    <div id="comment-{{ $r->id }}" class="ml-4 rounded-lg border p-3 bg-white/50 dark:bg-zinc-900/30" wire:key="c-{{ $r->id }}">
                                        <div class="text-xs text-zinc-500 mb-1">
                                            <span class="font-medium">{{ $rname }}</span>
                                            • {{ $r->created_at->diffForHumans() }}
                                        </div>
                                        <div class="prose dark:prose-invert max-w-none whitespace-pre-wrap text-sm">{!! $rhl !!}</div>

                                        <div class="mt-2 flex items-center gap-2">
                                            {{-- Reactions (same as root) --}}
                                            @php $available = ['👍','❤️','😂','🚀']; @endphp
                                            @foreach ($available as $emoji)
                                                @php
                                                    $list   = $r->reactions->where('emoji', $emoji);
                                                    $count  = $list->count();
                                                    $active = $list->where('user_guid', auth()->user()->guid)->isNotEmpty();
                                                @endphp

                                                <div class="relative inline-block"
                                                     x-data="{t:null, show:false}"
                                                     x-on:mouseenter="
                                                        t=setTimeout(() => {
                                                            $wire.refreshReactions({{ $r->id }}).then(() => { show = true })
                                                        }, 1200)
                                                     "
                                                     x-on:mouseleave="clearTimeout(t); show=false">

                                                    <button type="button"
                                                            class="cursor-pointer px-2 py-1 text-sm rounded
                                                                   {{ $active
                                                                        ? 'bg-green-100 focus:bg-green-100 hover:bg-green-100 dark:bg-green-900/30'
                                                                        : 'bg-gray-100 hover:bg-gray-100 dark:bg-zinc-800' }}"
                                                            x-on:click.stop="$wire.toggleReaction({{ $r->id }}, '{{ $emoji }}')">
                                                        {{ $emoji }}
                                                        @if($count > 0)
                                                            <span class="ml-1 text-xs">{{ $count }}</span>
                                                        @endif
                                                    </button>

                                                    <div x-show="show" x-transition.opacity
                                                         class="absolute -top-2 translate-y-[-100%] left-0 z-20 min-w-56 rounded-lg border border-zinc-200 bg-white p-3 shadow-lg dark:bg-zinc-900 dark:border-zinc-700"
                                                         style="pointer-events:none">
                                                        <div class="text-xs text-zinc-500 mb-2">
                                                            {{ $emoji }} Reaktionen • {{ $count }}
                                                        </div>
                                                        @php $freshList = $r->reactions->where('emoji', $emoji); @endphp
                                                        @if ($freshList->count() > 0)
                                                            <ul class="text-sm space-y-1">
                                                                @foreach ($freshList as $reaction)
                                                                    @php
                                                                        $isYou = $reaction->user_guid === auth()->user()->guid;
                                                                        $rrname = optional($reaction->user)->name ?? 'Unknown';
                                                                    @endphp
                                                                    <li class="flex items-center gap-2">
                                                                        <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                                                                        <span>
                                                                            {{ $rrname }}
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
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif
</flux:card>

{{-- Mentions JS (Alpine) --}}
<script>
    function mentionBox({ fetch, pick }) {
        return {
            open: false,
            active: 0,
            atPos: -1,
            onInput(e) {
                const ta = this.$refs.ta;
                const pos = ta.selectionStart;
                const text = ta.value;

                let start = text.lastIndexOf('@', pos - 1);
                if (start === -1) { this.open = false; return; }
                if (start > 0 && /\S/.test(text[start - 1])) { this.open = false; return; }

                const after = text.slice(start + 1, pos);
                if (/\s/.test(after) || after.length === 0) { this.open = false; return; }

                this.atPos = start;
                this.open = true;
                this.active = 0;
                fetch(after);
            },
            move(dir) {
                if (!this.open) return;
                const len = this.$wire.mentionResults.length;
                if (!len) return;
                this.active = (this.active + dir + len) % len;
            },
            choose(idx) {
                const list = this.$wire.mentionResults;
                if (!list || !list.length) return;
                const name = list[idx].name;

                const ta = this.$refs.ta;
                const pos = ta.selectionStart;
                const before = ta.value.slice(0, this.atPos);
                const after  = ta.value.slice(pos);
                const insert = '@' + name + ' ';

                ta.value = before + insert + after;
                ta.setSelectionRange(before.length + insert.length, before.length + insert.length);
                ta.dispatchEvent(new Event('input', { bubbles: true }));

                pick(name);
                this.open = false;
            },
            confirm() {
                if (!this.open) return;
                this.choose(this.active);
            }
        }
    }
</script>
