<div class="container mx-auto p-4 md:p-8">
    <header class="text-center mb-8">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white">Lesezeichen</h1>
        <p class="text-lg text-gray-600 dark:text-gray-400 mt-2">Die All-in-One Seite für alle wichtigen Lesezeichen.</p>
    </header>

    <div class="max-w-6xl mx-auto space-y-6"
         x-data
         x-init="
            window.addEventListener('keydown', e => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); $refs.searchInput.focus(); }
            });
         "
    >
        <!-- Suche + Global-Toggle -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <flux:input x-ref="searchInput" kbd="Strg k" icon="magnifying-glass" type="text"
                        wire:model.live="search" placeholder="Nach Lesezeichen suchen..." />
            <div class="flex items-center gap-2">
                <flux:field variant="inline" class="flex items-center gap-2">
                    <flux:label class="whitespace-nowrap">In allen Ordnern suchen</flux:label>
                    <flux:switch wire:model.live="globalSearch"/>
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
            <p class="text-center text-gray-500 dark:text-gray-400 text-xl">Keine Lesezeichen gefunden.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach ($filteredItems as $item)
                    @php
                        $imgUpload = $item->icon
                            ? (filter_var($item->icon, FILTER_VALIDATE_URL) ? $item->icon : asset('storage/' . $item->icon))
                            : null;
                        $favicon = $imgUpload ?: ($item->favicon ?: null);
                        $canManage = ($isAdmin || ($userGuid !== null && $item->user_guid === $userGuid));
                        $isFolder = $item->type === 'folder';
                        $displayUrl = $isFolder ? null : (parse_url($item->url, PHP_URL_HOST) ?: $item->url);
                    @endphp

                    <div class="relative">
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
                                            <flux:heading size="sm" class="truncate dark:text-white">
                                                {{ $item->name }}
                                            </flux:heading>
                                            <flux:text class="text-xs text-gray-500 dark:text-gray-300">
                                                {{ $item->children()->count() }} Einträge
                                            </flux:text>
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
                                                <img
                                                    src="{{ $favicon }}"
                                                    class="w-8 h-8 rounded-md"
                                                    alt="Favicon"
                                                    onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('hidden');"
                                                />
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
                                            <flux:heading size="sm" class="truncate dark:text-white">
                                                {{ $item->name }}
                                            </flux:heading>
                                            <flux:text class="text-xs text-gray-500 dark:text-gray-300 truncate">
                                                {{ $displayUrl }}
                                            </flux:text>
                                        </div>
                                    </div>
                                </flux:card>
                            </a>
                        @endif

                        @if ($canManage)
                            <!-- Manage buttons (top-right); stop bubbling -->
                            <div class="absolute top-2 right-2 z-10 flex gap-1" @click.stop>
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    wire:click.stop.prevent="startEdit({{ $item->id }})"
                                    title="Bearbeiten"
                                >
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
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Abbrechen</flux:button>
                                        </flux:modal.close>

                                        <flux:modal.close>
                                            <flux:button
                                                variant="danger"
                                                wire:click.stop.prevent="deleteBookmark({{ $item->id }})"
                                            >
                                                Endgültig löschen
                                            </flux:button>
                                        </flux:modal.close>
                                    </div>
                                </div>
                            </flux:modal>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Create Modal -->
    <flux:modal name="add-bookmark" class="w-full md:max-w-xl" wire:model="showModal">
        <div class="space-y-6">
            <div class="flex items-center gap-2">
                <flux:heading size="lg">Lesezeichen hinzufügen</flux:heading>

                <flux:tooltip toggleable>
                    <flux:button icon="information-circle" size="sm" variant="ghost" />
                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                        <p>Hinweis: <strong>Links dürfen nicht im Root</strong> angelegt werden.</p>
                        <p>Lege zuerst einen Ordner an oder wähle einen vorhandenen Ordner aus.</p>
                    </flux:tooltip.content>
                </flux:tooltip>
            </div>

            <flux:text class="mt-2">Erstelle ein neues Lesezeichen oder einen Ordner.</flux:text>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model.defer="newBookmarkName"/>
                <flux:error name="newBookmarkName"/>
            </flux:field>

            <flux:field>
                <flux:label>Typ</flux:label>
                <select wire:model.live="newBookmarkType"
                        class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:text-white">
                    <option value="link">Link</option>
                    <option value="folder">Ordner</option>
                </select>
                <flux:error name="newBookmarkType"/>
            </flux:field>

            @if ($newBookmarkType === 'link')
                <flux:field>
                    <flux:label>URL</flux:label>
                    <flux:input wire:model.defer="newBookmarkUrl" placeholder="https://..."/>
                    <flux:error name="newBookmarkUrl"/>
                </flux:field>
            @endif

            <!-- Icon selection (favicon or hero only) -->
            <div class="space-y-4">
                <flux:label>Symbol</flux:label>
                <div class="flex gap-4 items-center">
                    @if ($newBookmarkType === 'link')
                        <label class="flex items-center gap-2">
                            <input type="radio" value="favicon" wire:model.live="iconChoice">
                            <span>Favicon der Seite</span>
                        </label>
                    @endif

                    <label class="flex items-center gap-2">
                        <input type="radio" value="hero" wire:model.live="iconChoice">
                        <span>Icon auswählen</span>
                    </label>
                </div>

                @if ($iconChoice === 'hero')
                    <flux:field>
                        <flux:label>Icon</flux:label>

                        <flux:select variant="listbox" wire:model="iconName" searchable placeholder="— bitte wählen —">
                            @foreach (config("icons.hero") as $icon)
                                <flux:select.option>
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="{{ $icon }}" variant="mini" class="text-zinc-400" />
                                        {{ $icon }}
                                    </div>
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:error name="iconName"/>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Vorschau:</span>
                            @if($iconName)
                                <flux:icon :name="$iconName" />
                            @endif
                        </div>
                    </flux:field>
                @endif
            </div>

            <!-- Parent folder -->
            <flux:field>
                <div class="flex items-center gap-2">
                    <flux:label>Übergeordneter Ordner</flux:label>
                    <flux:tooltip toggleable>
                        <flux:button icon="information-circle" size="xs" variant="ghost" />
                        <flux:tooltip.content class="max-w-[18rem] space-y-2">
                            <p>Links dürfen <strong>nicht</strong> im Root angelegt werden.</p>
                            <p>Bitte einen Ordner auswählen.</p>
                        </flux:tooltip.content>
                    </flux:tooltip>
                </div>

                <select wire:model="newBookmarkParentId"
                        class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:text-white">
                    <option value="" @if($newBookmarkType==='link') disabled @endif>Root</option>
                    @foreach ($allFolders as $folder)
                        <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                    @endforeach
                </select>
                <flux:error name="newBookmarkParentId"/>
            </flux:field>

            @if ($isAdmin)
                <flux:field variant="inline" class="flex items-center gap-2">
                    <flux:label class="whitespace-nowrap">Global (für alle sichtbar)</flux:label>
                    <flux:switch wire:model="makeGlobal" />
                </flux:field>
            @endif

            @php
                // Strict null check so a non-empty string like "3" is accepted
                $disableCreate = ($newBookmarkType === 'link') && is_null($newBookmarkParentId ?? $currentFolderId);
            @endphp

            <div class="flex justify-end">
                @if ($disableCreate)
                    <flux:button wire:click="createBookmark" variant="primary" :disabled="true">
                        Erstellen
                    </flux:button>
                @else
                    <flux:button wire:click="createBookmark" variant="primary" :disabled="false">
                        Erstellen
                    </flux:button>
                @endif
            </div>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal class="w-full md:max-w-xl" wire:model="showEditModal" name="edit-bookmark">
        <div class="space-y-6">
            <flux:heading size="lg">Lesezeichen bearbeiten</flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model.defer="editName" />
                <flux:error name="editName" />
            </flux:field>

            <flux:field>
                <flux:label>Typ</flux:label>
                <flux:input value="{{ $editType }}" readonly />
                <flux:text class="text-xs text-gray-500 dark:text-gray-400">Der Typ kann nicht geändert werden.</flux:text>
            </flux:field>

            @if ($editType === 'link')
                <flux:field>
                    <flux:label>URL</flux:label>
                    <flux:input wire:model.defer="editUrl" placeholder="https://..." />
                    <flux:error name="editUrl" />
                </flux:field>
            @endif

            <!-- Icon selection for edit (favicon or hero only) -->
            <div class="space-y-4">
                <flux:label>Symbol</flux:label>
                <div class="flex gap-4 items-center">
                    @if ($editType === 'link')
                        <label class="flex items-center gap-2">
                            <input type="radio" value="favicon" wire:model.live="editIconChoice">
                            <span>Favicon der Seite</span>
                        </label>
                    @endif

                    <label class="flex items-center gap-2">
                        <input type="radio" value="hero" wire:model.live="editIconChoice">
                        <span>Icon auswählen</span>
                    </label>
                </div>

                @if ($editIconChoice === 'hero')
                    <flux:field>
                        <flux:label>Icon</flux:label>
                        <flux:select variant="listbox" wire:model="editIconName" searchable placeholder="— bitte wählen —">
                            @foreach (config("icons.hero") as $icon)
                                <flux:select.option>
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="{{ $icon }}" variant="mini" class="text-zinc-400" />
                                        {{ $icon }}
                                    </div>
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="editIconName"/>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Vorschau:</span>
                            @if($editIconName)
                                <flux:icon :name="$editIconName" />
                            @endif
                        </div>
                    </flux:field>
                @endif
            </div>

            <!-- Parent folder (move item) -->
            <flux:field>
                <div class="flex items-center gap-2">
                    <flux:label>Übergeordneter Ordner</flux:label>
                    @if ($editType === 'link')
                        <flux:tooltip toggleable>
                            <flux:button icon="information-circle" size="xs" variant="ghost" />
                            <flux:tooltip.content class="max-w-[18rem] space-y-2">
                                <p>Links dürfen <strong>nicht</strong> im Root angelegt werden.</p>
                                <p>Bitte einen Ordner auswählen.</p>
                            </flux:tooltip.content>
                        </flux:tooltip>
                    @endif
                </div>

                <select wire:model="editParentId"
                        class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:text-white">
                    <option value="" @if($editType==='link') disabled @endif>Root</option>
                    @foreach ($allFolders as $folder)
                        <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                    @endforeach
                </select>
                <flux:error name="editParentId"/>
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" wire:click="updateBookmark">Speichern</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Floating "Neu" button -->
    <div class="fixed bottom-0 right-0 p-4">
        <flux:button icon="plus" variant="primary" color="green" class="cursor-pointer"
                     wire:click="$set('showModal', true)">
            Neu
        </flux:button>
    </div>
</div>
