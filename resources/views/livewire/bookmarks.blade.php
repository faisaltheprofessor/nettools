<div class="container mx-auto p-4 md:p-8">
    <header class="mb-6">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">Lesezeichen</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Die All-in-One Seite für alle wichtigen Lesezeichen.</p>
    </header>

    <div class="max-w-7xl mx-auto space-y-5"
         x-data="{
            init(){
                window.addEventListener('keydown', e => {
                    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); this.$refs.searchInput.focus(); }
                });
            }
         }"
    >
        <!-- Row A: Breadcrumbs (left) + Actions (right) -->
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <nav aria-label="Breadcrumb" class="min-w-0">
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

            <div class="flex items-center gap-2">
                <!-- View toggle (plain buttons) -->
                <div class="flex items-center gap-1 rounded-md border px-1 py-1 dark:border-zinc-700">
                    <button type="button"
                            class="px-2 py-1 rounded text-sm inline-flex items-center gap-1 {{ $viewMode==='grid' ? 'bg-blue-600 text-white' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                            wire:click="setViewMode('grid')" title="Rasteransicht">
                        <flux:icon.layout-grid class="w-4 h-4" />
                    </button>
                    <button type="button"
                            class="px-2 py-1 rounded text-sm inline-flex items-center gap-1 {{ $viewMode==='list' ? 'bg-blue-600 text-white' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                            wire:click="setViewMode('list')" title="Listenansicht">
                        <flux:icon.list-bullet class="w-4 h-4" />
                    </button>
                </div>

                <flux:button icon="plus" variant="primary" color="green" class="cursor-pointer"
                             wire:click="$set('showModal', true)">
                    Neu
                </flux:button>
            </div>
        </div>

        <!-- Row B: Search + scope -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 min-w-0 flex-1">
                <flux:input x-ref="searchInput" kbd="Strg k" icon="magnifying-glass" type="text"
                            class="min-w-[220px] w-full"
                            wire:model.live="search"
                            placeholder="Suchen… " />
            </div>

            <div class="flex items-center gap-2">
                <flux:field variant="inline" class="flex items-center gap-2">
                    <flux:label class="whitespace-nowrap">Unterordner einbeziehen</flux:label>
                    <flux:switch wire:model.live="includeChildren"/>
                </flux:field>
            </div>
        </div>

        <!-- Content -->
        @if ($filteredItems->isEmpty())
            <p class="text-center text-gray-500 dark:text-gray-400 text-xl">Keine Lesezeichen gefunden.</p>
        @else
            @if ($viewMode === 'list')
                <!-- LIST VIEW -->
                <div class="overflow-x-auto rounded-lg border dark:border-zinc-700">
                    <table class="min-w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/40 text-zinc-600 dark:text-zinc-300">
                            <tr>
                                <th class="px-3 py-2 w-8"></th>
                                <th class="px-3 py-2 text-left">
                                    <div class="inline-flex items-center gap-2">
                                        <span wire:click="toggleNameSort" class="cursor-pointer">Name</span>
                                        <button type="button" class="px-1 text-xs hover:underline"
                                                wire:click="toggleNameSort" title="Sortierung umkehren">
                                            @if($nameSortDir==='asc') ↑ @else ↓ @endif
                                        </button>
                                    </div>
                                </th>
                                <th class="px-3 py-2 text-left hidden md:table-cell">URL / Domain</th>
                                <th class="px-3 py-2 text-left hidden lg:table-cell">Typ</th>
                                <th class="px-3 py-2 text-right">Aktionen</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y dark:divide-zinc-800">
                        @foreach ($filteredItems as $item)
                            @php
                                $imgUpload = $item->icon
                                    ? (filter_var($item->icon, FILTER_VALIDATE_URL) ? $item->icon : asset('storage/' . $item->icon))
                                    : null;
                                $favicon = $imgUpload ?: ($item->favicon && $item->favicon !== '__none__' ? $item->favicon : null);
                                $canManage = ($isAdmin || ($userGuid !== null && $item->user_guid === $userGuid));
                                $isFolder = $item->type === 'folder';
                                $displayUrl = $isFolder ? null : (parse_url($item->url, PHP_URL_HOST) ?: $item->url);
                            @endphp

                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-900/40"
                                wire:key="bm-row-{{ $item->id }}-{{ $item->updated_at?->timestamp ?? 0 }}">
                                <td class="px-3 py-2">
                                    <div class="w-6 h-6">
                                        @if ($isFolder)
                                            @if($item->icon_name)
                                                <flux:icon :name="$item->icon_name" class="w-6 h-6 text-zinc-500 dark:text-zinc-300" />
                                            @else
                                                <flux:icon.folder class="w-6 h-6 text-zinc-500 dark:text-zinc-300" />
                                            @endif
                                        @else
                                            @if ($favicon)
                                                <img src="{{ $favicon }}" class="w-6 h-6 rounded" alt="" role="presentation"
                                                     onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('hidden');" />
                                            @endif
                                            <div class="{{ $favicon ? 'hidden' : '' }}">
                                                @if($item->icon_name)
                                                    <flux:icon :name="$item->icon_name" class="w-6 h-6 text-zinc-500 dark:text-zinc-300" />
                                                @else
                                                    <flux:icon.link class="w-6 h-6 text-zinc-500 dark:text-zinc-300" />
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        @if ($isFolder)
                                            <button class="text-blue-600 hover:underline" wire:click="openFolder({{ $item->id }})">
                                                {{ $item->name }}
                                            </button>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $item->children()->count() }} Einträge
                                            </div>
                                        @else
                                            <a class="text-blue-600 hover:underline" href="{{ $item->url }}" target="_blank" rel="noopener">
                                                {{ $item->name }}
                                            </a>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-3 py-2 hidden md:table-cell">
                                    @if(!$isFolder)
                                        <flux:badge2 class="truncate max-w-[22rem]">{{ $item->url }}</flux:badge2>
                                    @endif
                                </td>

                                <td class="px-3 py-2 hidden lg:table-cell">
                                    @if ($isFolder)
                                        <flux:icon.folder />
                                    @else
                                        <flux:icon.link />
                                    @endif
                                </td>

                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    @if ($canManage)
                                        <flux:button size="xs" variant="ghost" wire:click.stop.prevent="startEdit({{ $item->id }})" title="Bearbeiten">
                                            <flux:icon.pencil-square class="w-4 h-4" />
                                        </flux:button>

                                        <flux:modal.trigger name="delete-bookmark-{{ $item->id }}">
                                            <flux:button size="xs" variant="danger" title="Löschen">
                                                <flux:icon.trash class="w-4 h-4" />
                                            </flux:button>
                                        </flux:modal.trigger>
                                    @endif
                                </td>
                            </tr>

                            <!-- Delete confirmation modal -->
                            <flux:modal name="delete-bookmark-{{ $item->id }}" class="min-w-[22rem]">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Lesezeichen löschen?</flux:heading>
                                        <flux:text class="mt-2">
                                            <p>Du bist dabei, <strong>{{ $item->name }}</strong> zu löschen.</p>
                                            <p>Diese Aktion kann nicht rückgängig gemacht werden.</p>
                                        </flux:text>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:spacer />
                                        <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                                        <flux:modal.close>
                                            <flux:button variant="danger" wire:click.stop.prevent="deleteBookmark({{ $item->id }})">Endgültig löschen</flux:button>
                                        </flux:modal.close>
                                    </div>
                                </div>
                            </flux:modal>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- GRID VIEW -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach ($filteredItems as $item)
                        @php
                            $imgUpload = $item->icon
                                ? (filter_var($item->icon, FILTER_VALIDATE_URL) ? $item->icon : asset('storage/' . $item->icon))
                                : null;
                            $favicon = $imgUpload ?: ($item->favicon && $item->favicon !== '__none__' ? $item->favicon : null);
                            $canManage = ($isAdmin || ($userGuid !== null && $item->user_guid === $userGuid));
                            $isFolder = $item->type === 'folder';
                            $displayUrl = $isFolder ? null : (parse_url($item->url, PHP_URL_HOST) ?: $item->url);
                        @endphp

                        <div class="relative" wire:key="bm-card-{{ $item->id }}-{{ $item->updated_at?->timestamp ?? 0 }}">
                            @if ($isFolder)
                                <div wire:click="openFolder({{ $item->id }})" class="cursor-pointer">
                                    <flux:card class="p-3 md:p-4 rounded-lg group hover:shadow-sm transition">
                                        <div class="flex items-center gap-3">
                                            <div class="shrink-0">
                                                @if($item->icon_name)
                                                    <flux:icon :name="$item->icon_name" class="w-10 h-10 text-zinc-500 dark:text-zinc-300" />
                                                @else
                                                    <flux:icon.folder class="w-10 h-10 text-zinc-500 dark:text-zinc-300" />
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <flux:heading size="sm" class="truncate dark:text-white">{{ $item->name }}</flux:heading>
                                                <flux:text class="text-xs text-gray-500 dark:text-gray-300">{{ $item->children()->count() }} Einträge</flux:text>
                                            </div>
                                        </div>
                                    </flux:card>
                                </div>
                            @else
                                <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer" class="block">
                                    <flux:card class="p-3 md:p-4 rounded-lg group hover:shadow-sm transition">
                                        <div class="flex items-center gap-3">
                                            <div class="shrink-0 relative">
                                                @if ($favicon)
                                                    <img src="{{ $favicon }}" class="w-8 h-8 rounded-md" alt="" role="presentation"
                                                         onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('hidden');" />
                                                @endif
                                                <div class="{{ $favicon ? 'hidden' : '' }}">
                                                    @if($item->icon_name)
                                                        <flux:icon :name="$item->icon_name" class="w-8 h-8 text-zinc-500 dark:text-zinc-300" />
                                                    @else
                                                        <flux:icon.link class="w-8 h-8 text-zinc-500 dark:text-zinc-300" />
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <flux:heading size="sm" class="truncate dark:text-white">{{ $item->name }}</flux:heading>
                                                <flux:text class="text-xs text-gray-500 dark:text-gray-300 truncate">{{ $displayUrl }}</flux:text>
                                            </div>
                                        </div>
                                    </flux:card>
                                </a>
                            @endif

                            @if ($canManage)
                                <div class="absolute top-2 right-2 z-10 flex gap-1" @click.stop>
                                    <flux:button size="xs" variant="ghost" wire:click.stop.prevent="startEdit({{ $item->id }})" title="Bearbeiten">
                                        <flux:icon.pencil-square class="w-4 h-4" />
                                    </flux:button>
                                    <div @click.stop>
                                        <flux:modal.trigger name="delete-bookmark-{{ $item->id }}">
                                            <flux:button size="xs" variant="danger" title="Löschen">
                                                <flux:icon.trash class="w-4 h-4" />
                                            </flux:button>
                                        </flux:modal.trigger>
                                    </div>
                                </div>

                                <flux:modal name="delete-bookmark-{{ $item->id }}" class="min-w-[22rem]">
                                    <div class="space-y-6">
                                        <div>
                                            <flux:heading size="lg">Lesezeichen löschen?</flux:heading>
                                            <flux:text class="mt-2">
                                                <p>Du bist dabei, <strong>{{ $item->name }}</strong> zu löschen.</p>
                                                <p>Diese Aktion kann nicht rückgängig gemacht werden.</p>
                                            </flux:text>
                                        </div>
                                        <div class="flex gap-2">
                                            <flux:spacer />
                                            <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                                            <flux:modal.close>
                                                <flux:button variant="danger" wire:click.stop.prevent="deleteBookmark({{ $item->id }})">Endgültig löschen</flux:button>
                                            </flux:modal.close>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    @include('livewire.partials.bookmarks-modals')
</div>
