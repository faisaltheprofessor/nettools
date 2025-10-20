<div wire:poll.5s>
    @if($display === 'icon')
        <span class="inline-flex items-center {{ $color }}">
            <svg class="w-2 h-2 mr-1 fill-current" viewBox="0 0 8 8">
                <circle cx="4" cy="4" r="4"/>
            </svg>
        </span>

    @elseif($display === 'text')
        <span class="{{ $color }}">
            {{ $label }}
        </span>

    @elseif($display === 'card')
        <div class="w-60 rounded-xl shadow-md p-4
            text-white bg-gradient-to-br
            @if($status === 'running') from-emerald-500 via-teal-500 to-cyan-500
            @elseif($status === 'offline') from-rose-500 via-red-500 to-orange-500
            @else from-amber-400 via-yellow-500 to-orange-500 @endif">

            <div class="flex items-center justify-between mb-2">
                <h2 class="text-base font-semibold truncate">{{ strtoupper($service) }}</h2>

                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-white/10 rounded-full">
                    <svg class="w-2 h-2 fill-current {{ $color }} drop-shadow-[0_0_3px_rgba(255,255,255,0.4)]" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="4" />
                    </svg>
                    <span>{{ $statusText }}</span>
                </span>
            </div>

            <div>
                <div class="text-[10px] uppercase tracking-wider text-white/70">Server</div>
                <div class="mt-0.5 text-lg font-bold leading-tight truncate">
                    {{ $server ?? 'Unbekannt' }}
                </div>
            </div>

            <div class="mt-4 flex justify-start">
                <flux:button variant="ghost"
                    href="/{{ strtolower($service) }}" wire:navigate
                    class="bg-white/10 hover:bg-white/30 !text-white text-xs font-medium px-3 py-1.5 rounded-lg transition">
                    Details
                </flux:button>
            </div>
        </div>
    @endif
</div>
