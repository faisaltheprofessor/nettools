<div class="w-[80%] md:w-[70%] mx-auto">
    <flux:card>
        <div class="flex flex-col items-center gap-2">
            <flux:icon.list-ordered class="size-12"/>
            <p>10 User PID Lücken</p>
            @if ($error)
                <p class="text-red-600">{{ $error }}</p>
            @endif

            <flux:button
                variant="primary"
                color="green"
                wire:click="getUserIdGap"
                type="button"
                class="cursor-pointer"
            >
                PIDs abrufen
            </flux:button>
        </div>
    </flux:card>

    @if ($pIdsInTextEditor)
        <div x-data="{ copied: false }" class="relative group mt-4" data-flux-input="">

            <div class="absolute top-0 right-0 flex items-center gap-x-1.5 pe-3 end-0 text-xs text-zinc-400">
                <button type="button"
                        class="relative items-center font-medium justify-center gap-2 whitespace-nowrap disabled:opacity-75 dark:disabled:opacity-75 disabled:cursor-default disabled:pointer-events-none h-8 text-sm rounded-md w-8 inline-flex -ms-1.5 -me-1.5 bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white      -me-1"
                        data-flux-button="data-flux-button" x-data="{ copied: false }"
                        x-on:click="copied = ! copied; navigator.clipboard &amp;&amp; navigator.clipboard.writeText($el.closest('[data-flux-input]').querySelector('textarea').value); setTimeout(() => copied = false, 2000)"
                        x-bind:data-copyable-copied="copied" aria-label="Copy to clipboard"
                        data-has-alpine-state="true">
                    <svg class="shrink-0 [:where(&amp;)]:size-5 hidden [[data-copyable-copied]>&amp;]:block"
                         data-flux-icon="" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                         aria-hidden="true" data-slot="icon">
                        <path fill-rule="evenodd"
                              d="M18 5.25a2.25 2.25 0 0 0-2.012-2.238A2.25 2.25 0 0 0 13.75 1h-1.5a2.25 2.25 0 0 0-2.238 2.012c-.875.092-1.6.686-1.884 1.488H11A2.5 2.5 0 0 1 13.5 7v7h2.25A2.25 2.25 0 0 0 18 11.75v-6.5ZM12.25 2.5a.75.75 0 0 0-.75.75v.25h3v-.25a.75.75 0 0 0-.75-.75h-1.5Z"
                              clip-rule="evenodd"></path>
                        <path fill-rule="evenodd"
                              d="M3 6a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H3Zm6.874 4.166a.75.75 0 1 0-1.248-.832l-2.493 3.739-.853-.853a.75.75 0 0 0-1.06 1.06l1.5 1.5a.75.75 0 0 0 1.154-.114l3-4.5Z"
                              clip-rule="evenodd"></path>
                    </svg>

                    <svg class="shrink-0 [:where(&amp;)]:size-5 block [[data-copyable-copied]>&amp;]:hidden"
                         data-flux-icon="" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                         aria-hidden="true" data-slot="icon">
                        <path fill-rule="evenodd"
                              d="M15.988 3.012A2.25 2.25 0 0 1 18 5.25v6.5A2.25 2.25 0 0 1 15.75 14H13.5v-3.379a3 3 0 0 0-.879-2.121l-3.12-3.121a3 3 0 0 0-1.402-.791 2.252 2.252 0 0 1 1.913-1.576A2.25 2.25 0 0 1 12.25 1h1.5a2.25 2.25 0 0 1 2.238 2.012ZM11.5 3.25a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 .75.75v.25h-3v-.25Z"
                              clip-rule="evenodd"></path>
                        <path
                            d="M3.5 6A1.5 1.5 0 0 0 2 7.5v9A1.5 1.5 0 0 0 3.5 18h7a1.5 1.5 0 0 0 1.5-1.5v-5.879a1.5 1.5 0 0 0-.44-1.06L8.44 6.439A1.5 1.5 0 0 0 7.378 6H3.5Z"></path>
                    </svg>
                </button>
            </div>

            <textarea
                readonly
                class="w-full text-sm font-mono whitespace-pre rounded-xl bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 px-4 py-3 pr-12"
                rows="10"
                x-ref="copyTarget"
            >{{ $pIdsInTextEditor }}</textarea>


            @endif

        </div>
