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
                                @if (!empty($entry['date']))
                                    <span class="text-xs text-zinc-500 font-normal">· {{ $entry['date'] }}</span>
                                @endif
                            </div>
                        </div>

                        @php
                            $html = \App\Support\Markdown::convert($entry['raw'] ?? '');
                        @endphp

                        @if (empty($html))
                            <flux:text class="mt-2 text-sm">Keine Inhalte gefunden.</flux:text>
                        @else
                            <div
                                class="mt-3 max-w-none prose dark:prose-invert md:prose-lg
                                       [&_h1]:text-3xl [&_h2]:text-2xl [&_h3]:text-xl
                                       [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6
                                       [&_li>ul]:mt-1 [&_li>ol]:mt-1
                                       [&_ul_ul]:pl-6 [&_ul_ol]:pl-6 [&_ol_ul]:pl-6 [&_ol_ol]:pl-6
                                       [&_li]:leading-relaxed"
                            >
                                {!! $html !!}
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
