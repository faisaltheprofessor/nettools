<div class="flex items-center justify-center min-h-screen">
  <blockquote class="w-2/3 text-center italic font-semibold text-gray-500 dark:text-gray-400 text-xl relative">

    <!-- Small grayed-out watermark above the quote icon -->
    <img
      src="/pankow.svg"
      alt="Watermark"
      class="mx-auto mb-2 w-64 h-64 opacity-60 select-none pointer-events-none"
      aria-hidden="true"
    />

    <!-- Quote icon -->
    <svg class="mx-auto w-8 h-8 mb-4 text-gray-400 dark:text-gray-600 relative z-10" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 14">
      <path d="M6 0H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h4v1a3 3 0 0 1-3 3H2a1 1 0 0 0 0 2h1a5.006 5.006 0 0 0 5-5V2a2 2 0 0 0-2-2Zm10 0h-4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h4v1a3 3 0 0 1-3 3h-1a1 1 0 0 0 0 2h1a5.006 5.006 0 0 0 5-5V2a2 2 0 0 0-2-2Z"/>
    </svg>

    <!-- The quote text -->
    <p class="relative z-10">{{ $quote }}</p>
  </blockquote>
</div>
