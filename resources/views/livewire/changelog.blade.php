<flux:modal name="changelog" class="min-w-[28rem] max-w-4xl">
  <div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
      <flux:heading size="lg" class="tracking-tight">Changelog</flux:heading>
    </div>

    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white/60 dark:bg-zinc-950/60">
      @if (empty($path) || !file_exists($path))
        <div class="p-4 text-sm text-zinc-600 dark:text-zinc-300">
          Keine <code>CHANGELOG.md</code> gefunden oder Datei ist leer.
        </div>
      @else
        <div class="max-h-[70vh] overflow-y-auto p-4">
          <div class="max-w-none prose dark:prose-invert md:prose-base
                      prose-p:my-2 prose-a:underline prose-a:underline-offset-2

                      /* Headings */
                      [&_h1]:text-3xl [&_h1]:font-extrabold [&_h1]:mt-6 [&_h1]:mb-4
                      [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:mt-5 [&_h2]:mb-3
                      [&_h3]:text-xl [&_h3]:font-semibold [&_h3]:mt-4 [&_h3]:mb-2
                      [&_h4]:text-lg [&_h4]:font-medium [&_h4]:mt-3 [&_h4]:mb-1
                      [&_h5]:text-base [&_h5]:font-medium
                      [&_h6]:text-sm [&_h6]:font-medium text-zinc-500

                      /* Lists */
                      [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:my-2
                      [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:my-2
                      [&_li]:leading-relaxed [&_li]:my-1

                      /* Code blocks */
                      prose-code:text-[0.85em] prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded
                      [&_pre]:rounded-xl [&_pre]:p-4 [&_pre]:overflow-x-auto

                      /* Tables */
                      [&_table]:w-full [&_th]:text-left [&_th]:font-semibold
                      [&_th]:border-b [&_td]:border-b
                      [&_th]:border-zinc-200 [&_td]:border-zinc-200
                      dark:[&_th]:border-zinc-700 dark:[&_td]:border-zinc-700
          ">
            <x-markdown>
              {{ file_get_contents($path) }}
            </x-markdown>
          </div>
        </div>
      @endif
    </div>

    <div class="flex justify-end">
      <flux:modal.close>
        <flux:button variant="primary" class="cursor-pointer">Schließen</flux:button>
      </flux:modal.close>
    </div>
  </div>
</flux:modal>
