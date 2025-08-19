<div wire:poll.15s="refreshData" class="relative">
    <flux:dropdown align="end">
        {{-- Bell button with badge --}}
        <flux:button variant="subtle" square class="relative group" aria-label="Benachrichtigungen">
            <flux:icon.bell variant="mini" class="text-zinc-600 dark:text-zinc-200"/>
            @if($unreadCount > 0)
                <span class="absolute -top-1 -right-1 text-[10px] leading-none font-semibold
                             bg-red-600 text-white rounded-full px-1.5 py-0.5">
                    {{ $unreadCount }}
                </span>
            @endif
        </flux:button>

        {{-- Dropdown list --}}
        <flux:menu class="min-w-80">
            <div class="px-3 py-2 text-xs text-zinc-500 flex items-center justify-between">
                <span>Benachrichtigungen</span>
                @if($unreadCount > 0)
                    <flux:menu.item class="!p-0">
                        <button class="text-xs text-blue-600 hover:underline px-2 py-1"
                                wire:click.prevent="markAllAsRead">Alle als gelesen markieren</button>
                    </flux:menu.item>
                @endif
            </div>
            <flux:menu.separator/>

            @forelse($items as $n)
                <flux:menu.item class="!py-2">
                    <div class="w-full">
                        <a href="{{ $n->url ?? '#' }}"
                           wire:navigate
                           class="block"
                           @if(!$n->read_at) @class(['font-medium']) @endif
                           wire:click.stop="markAsRead({{ $n->id }})">
                            <div class="flex items-start gap-2">
                                {{-- dot --}}
                                <span class="mt-1 w-2 h-2 rounded-full
                                    {{ $n->read_at ? 'bg-zinc-300 dark:bg-zinc-700' : 'bg-blue-500' }}"></span>
                                <div class="flex-1">
                                    <div class="text-sm">{{ $n->title }}</div>
                                    @if($n->body)
                                        <div class="text-xs text-zinc-500 line-clamp-2">{{ $n->body }}</div>
                                    @endif
                                    <div class="mt-1">
                                        @if(!$n->read_at)
                                            <flux:badge size="xs" color="blue">Neu</flux:badge>
                                        @endif
                                        @if($n->type !== 'info')
                                            <flux:badge size="xs" color="zinc">{{ $n->type }}</flux:badge>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </flux:menu.item>
            @empty
                <div class="px-3 py-6 text-center text-sm text-zinc-500">
                    Keine Benachrichtigungen
                </div>
            @endforelse
        </flux:menu>
    </flux:dropdown>
</div>
