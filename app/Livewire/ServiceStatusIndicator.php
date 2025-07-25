<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class ServiceStatusIndicator extends Component
{
    public string $service; // e.g. 'dhcp' or 'dns'

    public function render()
    {
        $status = Cache::get("{$this->service}:status")['status'] ?? null;

        $color = match ($status) {
            'running', 'Running' => 'text-green-500',
            'offline', 'Offline' => 'text-red-500',
            'loading', 'Unloading', 'Loading', 'Unloading' => 'text-yellow-500 animate-pulse',
            default => 'text-gray-400',
        };

        return view('livewire.service-status-indicator', [
            'color' => $color,
        ]);
    }
}
