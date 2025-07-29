<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class ServiceStatusIndicator extends Component
{
    public string $service;    // e.g. 'dhcp' or 'dns'

    public string $display = 'text'; // default to text if not provided

    public function render()
    {
        $statusData = Cache::get("{$this->service}:status");
        $status = $statusData['status'] ?? null;
        $statusLower = strtolower($status ?? '');

        $color = match ($statusLower) {
            'running' => 'text-green-500',
            'offline' => 'text-red-500',
            'loading', 'unloading' => 'text-yellow-500 animate-pulse',
            default => 'text-gray-400',
        };

        $label = match ($statusLower) {
            'running' => 'Läuft',
            'offline' => 'Offline',
            'loading', 'unloading' => 'Wird geladen',
            default => 'Fehler',
        };

        return view('livewire.service-status-indicator', [
            'color' => $color,
            'label' => $label,
            'display' => $this->display,
        ]);
    }
}
