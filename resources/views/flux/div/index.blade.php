@props([
    'size' => null,
    'copyable' => null,
])

@php
$classes = Flux::classes()
    ->add('relative')

    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-div>
    {{ $slot }}
    <div class="absolute top-0 bottom-0 flex items-start gap-x-1.5 pe-3 end-0 text-xs text-zinc-400">


        <?php if ($copyable): ?>
        <flux:div.copyable inset="left right" :$size/>
        <?php endif; ?>
    </div>
</div>


