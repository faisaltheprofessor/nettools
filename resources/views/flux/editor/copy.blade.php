<flux:tooltip content="{{ __('Copy to clipboard') }}" class="contents">
    <flux:editor.button x-on:click="
        (() => {
            let editorEl = $el.closest('[data-flux-editor]');
            if (!editorEl) return;
            let html = editorEl.value || '';

            // Convert <p> and </p> to newlines, remove other tags:
            let text = html
                .replace(/<\/p>/gi, '\n')    // close p tags -> newline
                .replace(/<p[^>]*>/gi, '')   // open p tags -> remove
                .replace(/<br\s*\/?>/gi, '\n') // br tags -> newline
                .replace(/<\/?[^>]+(>|$)/g, '') // remove any other tags

            navigator.clipboard.writeText(text.trim());

            $el.setAttribute('data-copied', '');
            setTimeout(() => $el.removeAttribute('data-copied'), 2000);
        })()
    ">
        <flux:icon.clipboard variant="outline" class="[[data-copied]_&]:hidden size-5!" />
        <flux:icon.clipboard-document-check variant="outline" class="hidden [[data-copied]_&]:block size-5!" />
    </flux:editor.button>
</flux:tooltip>

