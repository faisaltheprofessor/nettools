<div
    wire:poll.6s="refreshStatus"
    class="relative flex-1 rounded-2xl p-6 bg-gradient-to-br from-zinc-100 to-zinc-50 dark:from-zinc-800 dark:to-zinc-700 border border-zinc-200 dark:border-zinc-600 shadow-sm transition-all hover:shadow-md hover:-translate-y-0.5"
>
    <div class="flex items-start justify-between">
        <div>
            <flux:subheading class="text-zinc-500 dark:text-zinc-300 tracking-wide uppercase text-xs mb-1">
                Service
            </flux:subheading>

            <flux:heading size="2xl" class="font-semibold text-zinc-800 dark:text-zinc-100">
                {{ $name }}
            </flux:heading>
        </div>

        <!-- Proper Flux trigger: wraps your button; modal name bound via :name -->
        <flux:modal.trigger :name="$modalKey">
            <button
                type="button"
                class="flex items-center justify-center w-10 h-10 rounded-full shadow-inner cursor-pointer {{ $badgeClasses }}"
                title="Aktion für {{ strtoupper($service) }}"
                aria-label="Service-Aktion"
            >
                <flux:icon icon="circle-power" class="w-6 h-6" />
            </button>
        </flux:modal.trigger>
    </div>

    @if($description)
        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">
            {{ $description }}
        </p>
    @endif

    <!-- The actual modal -->
    <flux:modal :name="$modalKey" class="min-w-[28rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $modalTitle }}</flux:heading>
                <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                    {{ $modalBody }}
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>

                <flux:modal.close>
                    <flux:button wire:click="confirmAction" icon="{{ $confirmIcon }}">
                        {{ $confirmLabel }}
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
