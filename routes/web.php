<?php

use App\Livewire\Dashboard;
use App\Livewire\DHCP;
use App\Livewire\PasswordGenerator;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect("/dashboard");
})->name('home');

// Dashboard
Route::get('dashboard', Dashboard::class)
    ->name('dashboard');

     // DHCP
Route::get('dhcp', DHCP::class)
    ->name('dhcp.index');

    // Password Generator
Route::get('password-generator', PasswordGenerator::class)
    ->name('password.generator');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
