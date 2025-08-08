<div>
    <flux:card x-data="fileUpload()" class="mt-2">
        <div
            class="flex justify-center rounded-lg border border-dashed px-6 py-16 border-gray-900/25"
            x-bind:class="{ 'border-gray-900/50': dropping, 'border-gray-900/25': !dropping }"
            x-data="{ dropping: false }"
            x-on:dragover.prevent="dropping = true"
            x-on:dragleave.prevent="dropping = false"
            x-on:drop.prevent="
                dropping = false;
                if ($event.dataTransfer.files.length > 0) {
                    const files = $event.dataTransfer.files;
                    @this.uploadMultiple('newFiles', files);
                }
            "
        >
            <div class="text-center">
                <div class="flex text-sm/6 text-gray-600 dark:text-gray-400 items-center">
                    <label for="file-upload" class="relative cursor-pointer rounded-md bg-gray-200 dark:bg-gray-600 font-semibold text-indigo-600 hover:text-indigo-500 p-1 dark:text-gray-100 dark:hover:text-gray-200">
                        <span>Datei auswählen</span>
                        <input id="file-upload" name="file-upload" type="file" class="sr-only" wire:model="newFiles" multiple>
                    </label>
                    <p class="pl-1">oder drag and drop</p>
                </div>
                <p class="text-xs/5 text-gray-600 dark:text-gray-400">PNG, JPG, PDF bis 10MB</p>
            </div>
        </div>

        @if ($allFiles)
            <div class="mt-3 space-y-1">
                @foreach ($allFiles as $index => $file)
                    <div class="flex items-center justify-between text-sm ">
                        <span>{{ $file['name'] }}</span>
                        <button type="button" class="text-red-500 hover:underline" wire:click="removeFile({{ $index }})">
                            Entfernen
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>

    <script>
        function fileUpload() {
            return { dropping: false }
        }
    </script>
</div>
