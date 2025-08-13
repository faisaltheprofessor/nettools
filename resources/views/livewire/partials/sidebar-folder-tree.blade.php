@php
    $children = $byParent[$parentId] ?? collect();
@endphp

@foreach ($children as $node)
    @php
        $icon    = $node->icon_name ?: 'folder';
        $hasKids = isset($byParent[$node->id]) && $byParent[$node->id]->isNotEmpty();
        $href    = url('/bookmarks').'?folder='.$node->id;
    @endphp

    @if ($hasKids)
        <flux:navlist.group expandable icon="{{ $icon }}" heading="{{ $node->name }}" class="grid">
            <flux:navlist.item icon="folder-open" href="{{ $href }}" wire:navigate>
                Alle
            </flux:navlist.item>

            {{-- Recurse into children --}}
            @include('livewire.partials.sidebar-folder-tree', [
                'byParent' => $byParent,
                'parentId' => $node->id
            ])
        </flux:navlist.group>
    @else
        <flux:navlist.item icon="{{ $icon }}" href="{{ $href }}" wire:navigate>
            {{ $node->name }}
        </flux:navlist.item>
    @endif
@endforeach
