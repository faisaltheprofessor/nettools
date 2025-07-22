<div class="container mx-auto p-4 md:p-8">
    <header class="text-center mb-8">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white">Bookmarks</h1>
        <p class="text-lg text-gray-600 dark:text-gray-400 mt-2">Die All-in-One Seite für alle wichtigen Bookmarks.</p>
    </header>

    <div class="max-w-6xl mx-auto space-y-6">
        <!-- Search -->
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i data-feather="search" class="text-gray-400"></i>
            </div>
            <input
                type="text"
                wire:model.live="search"
                placeholder="Search for any bookmark..."
                class="w-full p-3 pl-10 text-md border-2 border-gray-300 dark:border-gray-700 rounded-full focus:ring-4 focus:ring-blue-300 focus:border-blue-500 dark:bg-gray-800 dark:text-white transition duration-300"
            />
        </div>

        <!-- Breadcrumbs -->
        <flux:breadcrumbs>
            @foreach ($breadcrumbs as $index => $crumb)
                <flux:breadcrumbs.item href="#" icon="{{ $index === 0 ? 'home' : null }}">
                    <a href="#" wire:click.prevent="goBackTo({{ $index }})" class="cursor-pointer">
                        {{ $index !== 0 ? $crumb['name'] : '' }}
                    </a>
                </flux:breadcrumbs.item>
            @endforeach
        </flux:breadcrumbs>

        <!-- Bookmarks Grid -->
        @if ($filteredItems->isEmpty())
            <p class="text-center text-gray-500 dark:text-gray-400 text-xl">No bookmarks found.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach ($filteredItems as $item)
                    @php
                        $color = $item->type === 'folder' ? 'bg-yellow-100 dark:bg-yellow-700' : 'bg-blue-100 dark:bg-blue-700';
                        $favicon = $item->icon ?? 'https://www.google.com/s2/favicons?domain=' . parse_url($item->url ?? '', PHP_URL_HOST) . '&sz=32';
                    @endphp

                    @if ($item->type === 'folder')
                        <div wire:click="openFolder({{ $item->id }})" class="cursor-pointer">
                            <flux:card class="p-4 {{ $color }} text-center rounded-lg space-y-2 h-40 flex flex-col justify-center">
                                <div class="flex justify-center">
                                    <flux:icon.folder />
                                </div>
                                <flux:heading size="sm" class="truncate dark:text-white">{{ $item->name }}</flux:heading>
                                <flux:text class="text-xs text-gray-500 dark:text-gray-300">{{ $item->children()->count() }} items</flux:text>
                            </flux:card>
                        </div>
                    @else
                        <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer">
                            <flux:card class="p-4 {{ $color }} text-center rounded-lg space-y-2 h-40 flex flex-col justify-center">
                                <div class="flex justify-center items-center">
                                    <img
                                        src="{{ $favicon }}"
                                        class="w-5 h-5 mr-1"
                                        alt="Favicon"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                    />
                                    <i data-feather="link-2" class="text-gray-400" style="display: none;"></i>
                                </div>
                                <flux:heading size="sm" class="truncate dark:text-white">{{ $item->name }}</flux:heading>
                                <flux:text class="text-xs text-gray-500 dark:text-gray-300 truncate">{{ $item->url }}</flux:text>
                            </flux:card>
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:load', () => {
            feather.replace();
        });
    </script>
</div>

