<flux:modal name="changelog" class="min-w-[28rem] max-w-4xl">
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <flux:heading size="lg">
                Changelog
            </flux:heading>
        </div>

        @if (empty($entries))
            <div class="text-sm text-zinc-600 dark:text-zinc-300">
                Keine <code>CHANGELOG.md</code> gefunden oder Datei ist leer.
            </div>
        @else
            <div class="max-h-[70vh] overflow-y-auto pr-1">
                @foreach ($this->filteredEntries as $entry)
                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 p-4 mb-3">
                        <div class="flex items-center justify-between">
                            <div class="font-semibold">
                                {{ $entry['version'] }}
                                @if ($entry['date'])
                                    <span class="text-xs text-zinc-500 font-normal">· {{ $entry['date'] }}</span>
                                @endif
                            </div>
                        </div>

                        @if (empty($entry['sections']))
                            <flux:text class="mt-2 text-sm">Keine Sektionen gefunden.</flux:text>
                        @else
                            <div class="mt-3 space-y-3">
                                @foreach ($entry['sections'] as $section)
                                    <div>
                                        <div class="text-xs uppercase tracking-wide text-zinc-500 mb-1">
                                            {{ $section['title'] }}
                                        </div>
                                        @if (empty($section['items']))
                                            <div class="text-sm text-zinc-500">–</div>
                                        @else
                                            <ul class="list-disc pl-5 space-y-1">
                                                @foreach ($section['items'] as $item)
                                                    <li class="text-sm leading-relaxed">
                                                        {{ $item }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button variant="primary" class="cursor-pointer">Schließen</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>
