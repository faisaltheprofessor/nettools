{{-- resources/views/errors/503.blade.php --}}
<x-layouts.app>
    @php
        $maintenanceImage = asset('storage/undraw/maintenance.svg');
    @endphp

    <div class="relative flex items-center justify-center h-[80vh] overscroll-none">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 w-full text-center">
            {{-- Illustration --}}
            <div class="mt-8 w-72 mx-auto">
                <img src="{{ $maintenanceImage }}" alt="Wartungsarbeiten Illustration" class="w-full h-auto">
            </div>

            {{-- Titel --}}
            <h1 class="mt-10 text-3xl font-bold text-gray-800 dark:text-gray-200">
                Wartungsmodus
            </h1>

            {{-- Nachricht --}}
            <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
                Die Anwendung befindet sich derzeit im Wartungsmodus.
                Bitte später erneut versuchen.
            </p>
        </div>
    </div>
</x-layouts.app>
