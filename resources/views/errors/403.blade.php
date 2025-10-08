@php
    $isNavigate = request()->header('X-Livewire-Navigate') || request()->header('X-Livewire');
@endphp

@if ($isNavigate)
    @include('errors.partials._403_content')
@else
    <x-layouts.app>
        @include('errors.partials._403_content')
    </x-layouts.app>
@endif
