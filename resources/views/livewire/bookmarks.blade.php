<div class="container mx-auto p-4 md:p-8">
    <header class="text-center mb-8">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white">Bookmarks</h1>
        <p class="text-lg text-gray-600 dark:text-gray-400 mt-2">Die All-in-One Seite für alle wichtigen Bookmarks.</p>
    </header>

    <div class="max-w-6xl mx-auto space-y-6">
        <!-- Search and Global Toggle -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i data-feather="search" class="text-gray-400"></i>
            </div>
            <input
                type="text"
                wire:model.live="search"
                placeholder="Search for any bookmark..."
                class="w-full p-3 pl-10 text-md border-2 border-gray-300 dark:border-gray-700 rounded-full focus:ring-4 focus:ring-blue-300 focus:border-blue-500 dark:bg-gray-800 dark:text-white transition duration-300"
            />

            <div class="flex items-center gap-2">
                <flux:field variant="inline" class="flex items-center gap-2">
                    <flux:label class="whitespace-nowrap">Global</flux:label>
                    <flux:switch wire:model.live="globalSearch" />
                </flux:field>

            </div>
        </div>

        <!-- Breadcrumbs -->
        <nav aria-label="Breadcrumb" class="mb-4">
            <flux:breadcrumbs>
                @foreach ($breadcrumbs as $index => $breadcrumb)
                    <flux:breadcrumbs.item
                        href="#"
                        icon="{{ $breadcrumb['id'] === null ? 'home' : null }}"
                        wire:click.prevent="goBackTo({{ $index }})"
                        class="cursor-pointer text-blue-600 hover:underline"
                    >
                        {{ $breadcrumb['name'] }}
                    </flux:breadcrumbs.item>
                @endforeach
            </flux:breadcrumbs>
        </nav>

        <!-- Bookmarks Grid -->
        @if ($filteredItems->isEmpty())
            <p class="text-center text-gray-500 dark:text-gray-400 text-xl">No bookmarks found.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach ($filteredItems as $item)
                    @php
                        $favicon = $item->icon
                            ? (filter_var($item->icon, FILTER_VALIDATE_URL) ? $item->icon : asset('storage/' . $item->icon))
                            : 'https://www.google.com/s2/favicons?domain=' . parse_url($item->url ?? '', PHP_URL_HOST) . '&sz=32';
                    @endphp

                    @if ($item->type === 'folder')
                        <div wire:click="openFolder({{ $item->id }})" class="cursor-pointer">
                            <flux:card class="p-4 text-center rounded-lg space-y-2 h-40 flex flex-col justify-center">
                                <div class="flex justify-center">
                                    <flux:icon.folder />
                                </div>
                                <flux:heading size="sm" class="truncate dark:text-white">{{ $item->name }}</flux:heading>
                                <flux:text class="text-xs text-gray-500 dark:text-gray-300">{{ $item->children()->count() }} items</flux:text>
                            </flux:card>
                        </div>
                    @else
                        <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer">
                            <flux:card class="p-4 text-center rounded-lg space-y-2 h-40 flex flex-col justify-center">
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

    <!-- Add Bookmark Modal -->
    <flux:modal name="add-bookmark" class="w-full md:max-w-xl" wire:model="showModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add Bookmark</flux:heading>
                <flux:text class="mt-2">Create a new bookmark or folder.</flux:text>
            </div>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model.defer="newBookmarkName" />
                <flux:error name="newBookmarkName" />
            </flux:field>

            <flux:field>
                <flux:label>Type</flux:label>
                <select wire:model.live="newBookmarkType" class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:text-white">
                    <option value="link">Link</option>
                    <option value="folder">Folder</option>
                </select>
                <flux:error name="newBookmarkType" />
            </flux:field>

            @if ($newBookmarkType === 'link')
                <flux:field>
                    <flux:label>URL</flux:label>
                    <flux:input wire:model.defer="newBookmarkUrl" placeholder="https://..." />
                    <flux:error name="newBookmarkUrl" />
                </flux:field>

                <flux:input type="file" wire:model="newBookmarkIcon" label="Icon" />
                <flux:error name="newBookmarkIcon" />
            @endif

            <flux:field>
                <flux:label>Parent Folder (optional)</flux:label>
                <select wire:model="newBookmarkParentId" class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:text-white">
                    <option value="">Root</option>
                    @foreach ($allFolders as $folder)
                        <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                    @endforeach
                </select>
                <flux:error name="newBookmarkParentId" />
            </flux:field>

            <div class="flex justify-end">
                <flux:button wire:click="createBookmark" variant="primary">Create</flux:button>
            </div>
        </div>
    </flux:modal>

    <script>
        document.addEventListener('livewire:load', () => {
            feather.replace();
        });
    </script>
    <div class="fixed bottom-0 right-0 p-4">

                <flux:button icon="plus" variant="primary" color="green" class="cursor-pointer" wire:click="$set('showModal', true)">Neue</flux:button>
    </div>
</div>

