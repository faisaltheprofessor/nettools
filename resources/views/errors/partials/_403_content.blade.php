<div class="relative flex items-center justify-center h-[70vh] overscroll-none">
    <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
        <div class="flex flex-col items-center pt-8 sm:pt-0 text-center">
            {{-- Code & Text (Laravel default style) --}}
            <div class="flex items-center">
                <div class="px-4 text-lg text-gray-500 border-r border-gray-400 tracking-wider">403</div>
                <div class="ml-4 text-lg text-gray-500 uppercase tracking-wider">Nicht Berechtigt</div>
            </div>

            {{-- SVG Illustration --}}
            <div class="mt-8 w-64">
                <img src="{{ asset('storage/undraw/unauthorized.svg') }}"
                     alt="Unauthorized Illustration"
                     class="w-full h-auto">
            </div>

            {{-- Homepage button --}}
            <div class="mt-8">
                @if (Route::has('dashboard'))
                    <flux:button href="{{ route('dashboard') }}" icon:trailing="home">
                        Zur Startseite
                    </flux:button>
                @elseif (Route::has('home'))
                    <flux:button href="{{ route('home') }}" icon:trailing="home">
                        Zur Startseite
                    </flux:button>
                @else
                    <flux:button href="/" icon:trailing="home">
                        Zur Startseite
                    </flux:button>
                @endif
            </div>
        </div>
    </div>
</div>
