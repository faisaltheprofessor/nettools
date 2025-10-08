<?php

use App\Livewire\Bookmarks;
use App\Livewire\Dashboard;
use App\Livewire\DHCP;
use App\Livewire\DNS;
use App\Livewire\IdTools;
use App\Livewire\IpCalculator;
use App\Livewire\Ldap\NextMailboxPid;
use App\Livewire\Ldap\NextUserPid;
use App\Livewire\OVirtSerialNumberGenerator;
use App\Livewire\PasswordGenerator;
use App\Livewire\Signature;
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
        //->middleware('ldap.right:dhcp')
        ->name('dhcp.index');

    Route::get('dns', DNS::class)
        ->middleware('ldap.right:dns')
        ->name('dns.index');

    Route::get('ip-calculator', IpCalculator::class)
        ->name('ip-calculator.index');

    Route::get('ovirt-serialnumber-generator', OVirtSerialNumberGenerator::class)
        ->name('ovirt-serialnumber-generator.index');

    // Password Generator
    Route::get('password-generator', PasswordGenerator::class)
        ->name('signature.generator');

    // Signature
    Route::get('signature-generator', Signature::class)
        ->name('password.generator');

    // ID Tools
    Route::get('ldap', IdTools::class)
        ->name('ldap.index');

    // ID Tools
    Route::get('next-mailbox-pid', NextMailboxPid::class)
        ->name('next-mailbox-pid.index');

    Route::get('next-user-pid', NextUserPid::class)
        ->name('next-user-pid.index');
    Route::get('next-user-pid', NextUserPid::class)
        ->name('next-user-pid.index');

    // Bookmarks
    Route::get('bookmarks', Bookmarks::class)
        ->name('bookmarks.index');

    Route::get('feedbacks', \App\Livewire\FeedbackViewer::class)
        ->name('feedbacks.index');

    Route::get('firewall-template', \App\Livewire\FirewallVorlagen::class)
        ->name('firewall.vorlage');

    Route::get('domain-analysis', \App\Livewire\DomainAnalysis::class)
        ->name('domain.analysis');

    // Test Routes
Route::get('/_test/maintenance', function () {
    config(['app.debug' => true]);
    return response()->view('errors.503', [], 503);
});
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});



require __DIR__.'/auth.php';
