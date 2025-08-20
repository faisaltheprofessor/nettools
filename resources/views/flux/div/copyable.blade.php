@php
    $attributes = $attributes->merge([
        'variant' => 'subtle',
        'class'   => '-me-1',
        'square'  => true,
        'size'    => null,
    ]);
@endphp

<flux:button
    :$attributes
    :size="$size === 'sm' || $size === 'xs' ? 'xs' : 'sm'"
    x-data="{ copied: false }"
    x-on:click="
        copied = true;

        const container =
            $el.closest('[data-flux-div]') ??
            $el.closest('[data-flux-badge]') ??
            document;

        // 1) Prefer explicitly marked copy sources (badge2 should mark its visible text with data-copy-source)
        const sources = container.querySelectorAll('[data-copy-source]');
        let text = '';

        if (sources.length) {
            text = Array.from(sources)
                .map(n => n.textContent.trim())
                .filter(Boolean)
                .join('\n');
        } else {
            // 2) Fallback: clone container and strip all 'no copy' / chrome
            const clone = container.cloneNode(true);

            // remove anything explicitly marked as not copyable
            clone.querySelectorAll('[data-no-copy], [data-nocopy]').forEach(el => el.remove());

            // remove UI chrome and trailing icons
            clone.querySelectorAll(
                '[data-flux-badge-icon],' +
                '[data-flux-badge-icon\\:trailing],' +
                '[data-copy-trigger]'
            ).forEach(el => el.remove());

            // hide x-cloak/hidden nodes from copy
            clone.querySelectorAll('[x-cloak],[hidden]').forEach(el => el.remove());

            // read cleaned text
            text = (clone.textContent || '')
                .split('\n')
                .map(s => s.trim())
                .filter(Boolean)
                .join('\n');
        }

        navigator.clipboard?.writeText(text);
        setTimeout(() => copied = false, 2000);
    "
    x-bind:data-copyable-copied="copied"
    aria-label="{{ __('Copy to clipboard') }}"
>
    <flux:icon.clipboard-document-check variant="mini" class="hidden [[data-copyable-copied]>&]:block"/>
    <flux:icon.clipboard-document variant="mini" class="block [[data-copyable-copied]>&]:hidden"/>
</flux:button>
