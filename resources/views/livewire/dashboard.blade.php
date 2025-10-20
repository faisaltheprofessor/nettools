<div class="relative">

<div class="relative z-10 mt-8 flex flex-wrap gap-2 md:gap-4 justify-start items-start">
        <livewire:service-status-indicator service="dns" display="card"/>
        <livewire:service-status-indicator service="dhcp" display="card"/>
</div>


    <div class="fixed inset-0 flex flex-col items-center justify-center pointer-events-none select-none">
        <img
            src="/pankow.svg"
            alt="Watermark"
            class="w-72 h-72 opacity-10 dark:opacity-20 mb-6"
            aria-hidden="true"
        />
        <blockquote class="text-center italic font-semibold text-gray-500 dark:text-gray-400 text-xl max-w-xl">
            <svg class="mx-auto w-8 h-8 mb-4 text-gray-400 dark:text-gray-600" aria-hidden="true"
                 xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 14">
                <path
                    d="M6 0H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h4v1a3 3 0 0 1-3 3H2a1 1 0 0 0 0 2h1a5.006 5.006 0 0 0 5-5V2a2 2 0 0 0-2-2Zm10 0h-4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h4v1a3 3 0 0 1-3 3h-1a1 1 0 0 0 0 2h1a5.006 5.006 0 0 0 5-5V2a2 2 0 0 0-2-2Z"/>
            </svg>
            <p>{{ $quote }}</p>
        </blockquote>
    </div>
</div>
