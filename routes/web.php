<?php

use App\Livewire\Dashboard;
use App\Livewire\DHCP;
use App\Livewire\DNS;
use App\Livewire\Signature;
use App\Livewire\IpCalculator;
use App\Livewire\NextFreePid;
use App\Livewire\OVirtSerialNumberGenerator;
use App\Livewire\PasswordGenerator;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;


Route::middleware(['auth'])->group(function () {
Route::get('/', function () {
    return redirect('/dashboard');
})->name('home');
// Dashboard
Route::get('dashboard', Dashboard::class)
    ->name('dashboard');

// DHCP
Route::get('dhcp', DHCP::class)
    ->name('dhcp.index');

Route::get('dns', DNS::class)
    ->name('dns.index');

Route::get('ip-calculator', IpCalculator::class)
    ->name('ip-calculator.index');

Route::get('ovirt-serialnumber-generator', OVirtSerialNumberGenerator::class)
    ->name('ovirt-serialnumber-generator.index');

// Password Generator
Route::get('password-generator', PasswordGenerator::class)
    ->name('signature.generator');
//

// Signature
Route::get('signature-generator', Signature::class)
    ->name('password.generator');

// Next Free Mailbox pid
Route::get('next-free-pid', NextFreePid::class)
    ->name('next-free-pid');

});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';

