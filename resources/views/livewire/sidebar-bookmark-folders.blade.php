<div>
    {{-- Root entry --}}
    <flux:navlist.item icon="home" href="{{ url('/bookmarks') }}" wire:navigate>
        Hauptseite
    </flux:navlist.item>

    {{-- Top-level folders (parent_id = null) --}}
    @include('livewire.partials.sidebar-folder-tree', [
        'byParent' => $byParent,
        'parentId' => 0
    ])
</div>
