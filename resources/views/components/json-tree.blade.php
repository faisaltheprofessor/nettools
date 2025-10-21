@props(['data', 'key' => null, 'level' => 0])

@php
    $isAssoc = is_array($data) && array_keys($data) !== range(0, count($data) - 1);
    $indent = max(0, (int) $level);
@endphp

{{-- Top toggle control (only once at root) --}}
@if($level === 0)
    <div
        x-data="{ allOpen: true }"
        class="mb-2 flex justify-end"
    >
        <flux:button variant="primary" size="xs" color="emerald"
            @click="
                allOpen = !allOpen;
                window.dispatchEvent(new CustomEvent(allOpen ? 'json-tree-expand-all' : 'json-tree-collapse-all'));
            ">
            <span x-text="allOpen ? 'Alle zuklappen' : 'Alle aufklappen'"></span>
        </flux:button>
    </div>
@endif

<div
    x-data="{ open: {{ $level < 1 ? 'true' : 'false' }} }"
    x-on:json-tree-expand-all.window="open = true"
    x-on:json-tree-collapse-all.window="open = false"
    class="ml-{{ $indent * 2 }}"
>
    @if(!is_array($data))
        <div class="flex gap-2">
            @if(!is_null($key))
                <span class="font-mono text-gray-700">{{ $key }}:</span>
            @endif
            <span class="font-mono break-all">
                {{ is_bool($data) ? ($data ? 'true' : 'false') : (is_null($data) ? 'null' : (string) $data) }}
            </span>
        </div>
    @else
        <div class="flex items-center gap-2">
            <button type="button" @click="open = !open" class="px-1 py-0.5 border rounded text-xs">
                <span x-show="!open">▶</span>
                <span x-show="open">▼</span>
            </button>
            @if(!is_null($key))
                <span class="font-mono text-gray-700">{{ $key }}:</span>
            @endif
            <span class="font-mono text-gray-500">
                {{ $isAssoc ? '{… ' . count($data) . '}' : '[… ' . count($data) . ']' }}
            </span>
        </div>
        <div x-show="open" class="mt-1 pl-4 border-l">
            @foreach($data as $k => $v)
                <x-json-tree :data="$v" :key="$k" :level="$level + 1"/>
            @endforeach
        </div>
    @endif
</div>
