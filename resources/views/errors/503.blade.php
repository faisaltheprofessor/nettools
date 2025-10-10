@extends('errors.minimal')

@section('title', 'Wartungsmodus')

@section('image')
<div class="group relative inline-block">
    <img
        src="{{ asset(\Illuminate\Support\Arr::random(['storage/undraw/programming.svg'])) }}"
        alt="Wartungsarbeiten Illustration"
        class="mx-auto w-64 sm:w-72 md:w-80 lg:w-[28rem]
               transition-all duration-500 ease-out
               filter
               saturate-90 contrast-105 brightness-105 opacity-95
               group-hover:saturate-125 group-hover:contrast-110 group-hover:brightness-110 group-hover:opacity-100 group-hover:hue-rotate-15
               dark:saturate-110 dark:contrast-110 dark:brightness-[1.15] dark:opacity-100"
    />
</div>
@endsection

@section('code', '503')

@section('message')
<p>Die Anwendung befindet sich derzeit im Wartungsmodus. Bitte später erneut versuchen.</p>
@endsection
