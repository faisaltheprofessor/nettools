<!-- CREATE MODAL -->
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

        <!-- Icon selection (default = hero; link->'link', folder->'folder') -->
        <div class="space-y-4">
            <flux:label>Symbol</flux:label>
            <div class="flex gap-4 items-center">
                <label class="flex items-center gap-2">
                    <input type="radio" value="hero" wire:model.live="iconChoice">
                    <span>Icon auswählen</span>
                </label>

                @if ($newBookmarkType === 'link')
                    <label class="flex items-center gap-2">
                        <input type="radio" value="favicon" wire:model.live="iconChoice">
                        <span>Favicon der Seite</span>
                    </label>
                @endif
            </div>

            @if ($iconChoice === 'hero')
                <flux:field>
                    <flux:label>Icon</flux:label>

                    <flux:select variant="listbox" wire:model="iconName" searchable placeholder="— bitte wählen —">
                        @foreach (config("icons.hero") as $icon)
                            <flux:select.option value="{{ $icon }}">
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
                    <flux:text class="text-xs text-gray-500 mt-1">
                        @if ($newBookmarkType === 'link')
                            Standard: <code>link</code>
                        @else
                            Standard: <code>folder</code>
                        @endif
                    </flux:text>
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
            $disableCreate = ($newBookmarkType === 'link') && is_null($newBookmarkParentId ?? $currentFolderId);
        @endphp

        <div class="flex justify-end">
            @if ($disableCreate)
                <flux:button variant="primary" disabled>
                    Erstellen
                </flux:button>
            @else
                <flux:button variant="primary" wire:click="createBookmark">
                    Erstellen
                </flux:button>
            @endif
        </div>
    </div>
</flux:modal>

<!-- EDIT MODAL -->
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

        <div class="space-y-4">
            <flux:label>Symbol</flux:label>
            <div class="flex gap-4 items-center">
                <label class="flex items-center gap-2">
                    <input type="radio" value="hero" wire:model.live="editIconChoice">
                    <span>Icon auswählen</span>
                </label>

                @if ($editType === 'link')
                    <label class="flex items-center gap-2">
                        <input type="radio" value="favicon" wire:model.live="editIconChoice">
                        <span>Favicon der Seite</span>
                    </label>
                @endif
            </div>

            @if ($editIconChoice === 'hero')
                <flux:field>
                    <flux:label>Icon</flux:label>
                    <flux:select variant="listbox" wire:model="editIconName" searchable placeholder="— bitte wählen —">
                        @foreach (config("icons.hero") as $icon)
                            <flux:select.option value="{{ $icon }}">
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
