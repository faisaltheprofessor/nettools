<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
    @php
        $allWallpapers = glob(public_path('wallpapers/*.{jpg,jpeg,png,webp}'), GLOB_BRACE);

        $portraitWallpapers = collect($allWallpapers)->filter(function ($path) {
            [$width, $height] = @getimagesize($path);
            return $height > $width;
        })->values()->all();

        $randomWallpaper = count($portraitWallpapers)
            ? asset('wallpapers/' . basename($portraitWallpapers[array_rand($portraitWallpapers)]))
            : null;

        [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
    @endphp

    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <div class="relative hidden h-full flex-col p-10 text-white lg:flex dark:border-e dark:border-neutral-800 overflow-hidden">

            @if ($randomWallpaper)
                <img src="{{ $randomWallpaper }}"
                     alt="Wallpaper"
                     class="absolute inset-0 h-full w-full object-cover z-0" />
                <div class="absolute inset-0 z-10 bg-gradient-to-t from-black/70 via-black/40 to-transparent"></div>
            @else
                <div class="absolute inset-0 bg-neutral-900"></div>
            @endif

            <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-medium" wire:navigate>
                <span class="flex h-10 w-10 items-center justify-center rounded-md">
                    <img src="{{ asset('logo.png') }}" alt="logo" class="mr-2 h-7 fill-current text-white">
                </span>
                {{ config('app.name', 'Laravel') }}
            </a>

            <div class="relative z-20 mt-auto max-w-md rounded-md bg-black/70 p-6">
                <blockquote class="space-y-2 font-bold text-white drop-shadow-lg" style="color: #fff !important;">
                    <flux:heading size="lg" style="color: #fff !important;">&ldquo;{{ trim($message) }}&rdquo;</flux:heading>
                    <footer><flux:heading style="color: #fff !important;">{{ trim($author) }}</flux:heading></footer>
                </blockquote>
            </div>
        </div>

        <div class="w-full lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                    <span class="flex h-9 w-9 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                {{ $slot }}
            </div>
        </div>
    </div>
    @fluxScripts
</body>
</html>

