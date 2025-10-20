<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use Livewire\Component;

class ServiceStatusIndicator extends Component
{
    public string $service;
    public string $display = 'text';

    public function render()
    {
        $statusData = Cache::get("{$this->service}:status", []);

        $statusRaw = $statusData['status'] ?? $statusData['state'] ?? null;
        $statusLower = strtolower((string)$statusRaw);

        // Translated + color-matched display
        $label = match ($statusLower) {
            'running' => 'Läuft',
            'offline' => 'Offline',
            'loading', 'unloading' => 'Wird geladen',
            default => 'Unbekannt',
        };

        $color = match ($statusLower) {
            'running' => 'text-green-300',
            'offline' => 'text-red-400',
            'loading', 'unloading' => 'text-yellow-300 animate-pulse',
            default => 'text-gray-300',
        };

        $server = $this->pickFirst($statusData, [
            'server', 'host', 'node', 'running_server', 'runningServer'
        ]) ?? null;

        return view('livewire.service-status-indicator', [
            'color'      => $color,
            'label'      => $label,
            'display'    => $this->display,
            'status'     => $statusLower ?: null,
            'service'    => $this->service,
            'server'     => $server,
            'statusText' => $label,
        ]);
    }

    protected function pickFirst(array $data, array $keys): mixed
    {
        foreach ($keys as $k) {
            if (Arr::has($data, $k) && !empty($data[$k])) {
                return $data[$k];
            }
        }
        return null;
    }
}
