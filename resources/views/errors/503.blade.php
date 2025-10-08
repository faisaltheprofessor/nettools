@extends('errors.minimal') {{-- use your new layout name --}}

@section('title', 'Wartungsmodus')

@section('image')
    <img src="{{ asset('storage/undraw/maintenance.svg') }}"
         alt="Wartungsarbeiten Illustration"
         class="w-24 mx-auto" height="400"/>
@endsection

@section('code', '503')

@section('message')
    Die Anwendung befindet sich derzeit im Wartungsmodus.
    Bitte später erneut versuchen.
@endsection
