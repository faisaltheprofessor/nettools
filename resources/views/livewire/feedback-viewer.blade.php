<flux:card class="mt-4 space-y-4">

    {{-- Filters (Accordion) --}}
    <flux:accordion class="rounded-2xl border border-zinc-200 dark:border-zinc-700 p-4">
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

    {{-- Table --}}
    <div class="overflow-auto" style="min-height: 300px;">
        <flux:table :paginate="$feedbacks">
            <flux:table.columns>
                <flux:table.column
                    wire:click="sortBy('type')"
                    sortable
                    :direction="$sortField === 'type' ? $sortDirection : null"
                    class="px-2">Type</flux:table.column>

                <flux:table.column
                    wire:click="sortBy('user.name')"
                    sortable
                    :direction="$sortField === 'user.name' ? $sortDirection : null">User</flux:table.column>

                <flux:table.column>Status</flux:table.column>

                <flux:table.column
                    wire:click="sortBy('created_at')"
                    sortable
                    :direction="$sortField === 'created_at' ? $sortDirection : null">Date</flux:table.column>
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

    {{-- Forum / Discussion --}}
    @if ($selectedFeedback)
        <flux:card>
            <div class="mb-3 text-sm space-y-1">
                <div><span class="font-semibold">Type:</span> {{ ucfirst($selectedFeedback->type) }}</div>
                <div><span class="font-semibold">User:</span> {{ $selectedFeedback->user->name ?? 'Unknown' }}</div>
                <div><span class="font-semibold">Date:</span> {{ $selectedFeedback->created_at->format('Y-m-d H:i') }}</div>

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

            <h2 class="text-xl font-bold mb-1">{{ $selectedFeedback->title }}</h2>
            <p class="mb-4">{{ $selectedFeedback->description }}</p>

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

            <div class="space-y-4">
                <flux:heading size="md">Diskussion</flux:heading>

                <div class="flex items-start gap-2">
                    <flux:textarea class="flex-1" rows="3" wire:model.defer="newComment" placeholder="Schreibe eine Antwort..."></flux:textarea>
                    <flux:button variant="primary" color="green" class="self-start cursor-pointer" wire:click="addComment">Posten</flux:button>
                </div>

                <div class="space-y-3">
                    @forelse ($selectedFeedback->comments as $c)
                        <div class="rounded-lg border p-3 bg-white/50 dark:bg-zinc-900/30">
                            <div class="text-xs text-zinc-500 mb-1">
                                <span class="font-medium">{{ $c->user->name ?? 'Unknown' }}</span>
                                • {{ $c->created_at->diffForHumans() }}
                            </div>
                            <div class="whitespace-pre-wrap text-sm">{{ $c->body }}</div>
                        </div>
                    @empty
                        <p class="text-gray-500 italic">Noch keine Kommentare.</p>
                    @endforelse
                </div>
            </div>
        </flux:card>
    @endif
</flux:card>
