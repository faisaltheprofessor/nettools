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
    @endif
</div>

