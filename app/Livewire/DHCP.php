<?php

namespace App\Livewire;

use Flux\Flux;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class DHCP extends Component
{
    public array $servers = ['vs002', 'vs003', 'vs004'];

    public ?string $dhcpStatus = null;
    public ?string $runningServer = null;

    public bool $loading = false;
    public bool $beingRestarted = false;

    public ?string $selectedServer = null;

    public function render()
    {
        return view('livewire.dhcp');
    }

    public function mount()
    {
        $this->getDhcpStatus();
    }

    public function getDhcpStatus(): void
    {
        if ($this->loading || $this->beingRestarted) {
            return;
        }

        $this->loading = true;

        try {
            $status = Cache::get('dhcp:status');

            if (!$status) {
                throw new \Exception('Kein Status im Cache gefunden.');
            }

            $this->runningServer = $status['running_server'] ?? null;
            $raw = $status['status'] ?? 'error';

            $this->dhcpStatus = match ($raw) {
                'Running' => 'running',
                'Offline' => 'offline',
                'Loading' => 'loading',
                'Unloading' => 'unloading',
                default => 'error',
            };

            if ($this->dhcpStatus !== 'running') {
                Flux::toast(
                    text: "DHCP ist aktuell im Status: {$this->dhcpStatus}.",
                    heading: 'DHCP-Status',
                    variant: $this->dhcpStatus === 'offline' ? 'danger' : 'warning'
                );
            }
        } catch (\Throwable $e) {
            $this->dhcpStatus = 'error';
            $this->runningServer = null;

            Flux::toast(
                text: $e->getMessage(),
                heading: 'Fehler beim Statusabruf',
                variant: 'danger'
            );
        } finally {
            $this->loading = false;
        }
    }

    public function restartDhcp(): void
    {
        if ($this->beingRestarted || $this->loading) {
            return;
        }

        if (Cache::get('dhcp:restart:queued')) {
            Flux::toast(
                text: 'Ein Neustart läuft bereits oder ist geplant.',
                heading: 'Bereits in Warteschlange',
                variant: 'warning'
            );

            return;
        }

        $this->beingRestarted = true;

        try {
            Artisan::queue('dhcp:restart-service');

            Flux::toast(
                text: 'Neustart wurde gestartet. Bitte prüfen Sie den Status in Kürze.',
                heading: 'DHCP Neustart',
                variant: 'success'
            );

            Flux::modals()->close();
        } catch (\Throwable $e) {
            Flux::toast(
                text: $e->getMessage(),
                heading: 'Neustart-Fehler',
                variant: 'danger'
            );
        } finally {
            $this->beingRestarted = false;
        }
    }

    public function pollRestartStatus(): void
    {
        $status = Cache::get('dhcp:restart:status');

        match (true) {
            $status === 'running' => $this->dhcpStatus = 'loading',
            str_starts_with($status, 'error') => Flux::toast(
                text: $status,
                heading: 'Restart fehlgeschlagen',
                variant: 'danger'
            ),
            $status === 'success' => Flux::toast(
                text: 'DHCP wurde erfolgreich neugestartet.',
                heading: 'Erfolg',
                variant: 'success'
            ),
            $status === 'locked' => Flux::toast(
                text: 'Ein anderer Benutzer führt gerade einen Neustart durch.',
                heading: 'Locked',
                variant: 'warning'
            ),
            default => null,
        };
    }

    public function migrateDhcp(string $node): void
    {
        if ($this->loading) {
            return;
        }

        try {
            if (Cache::lock('dhcp_migrate_lock', 30)->get() === false) {
                Flux::toast(
                    text: 'Eine andere Migration ist gerade aktiv.',
                    heading: 'Migration blockiert',
                    variant: 'warning'
                );
                return;
            }

            Artisan::queue('dhcp:migrate-service', [
                'targetNode' => $node,
            ]);

            Flux::toast(
                text: "Migration nach {$node} gestartet.",
                heading: 'DHCP Migration',
                variant: 'success'
            );
        } catch (\Throwable $e) {
            Flux::toast(
                text: $e->getMessage(),
                heading: 'Migrationsfehler',
                variant: 'danger'
            );
        } finally {
            Flux::modals()->close();
        }
    }

    public function startDhcp(): void
    {
        if (!$this->selectedServer) {
            Flux::toast(
                text: 'Bitte einen Server auswählen.',
                heading: 'Keine Auswahl',
                variant: 'warning'
            );
            return;
        }

        if ($this->loading || $this->dhcpStatus === 'running') {
            Flux::toast(
                text: 'DHCP ist bereits aktiv oder wird geladen.',
                heading: 'Start blockiert',
                variant: 'info'
            );
            return;
        }

        try {
            Artisan::queue('dhcp:start-service', [
                'server' => $this->selectedServer,
            ]);

            Flux::toast(
                text: "Start des DHCP-Dienstes auf {$this->selectedServer} wurde eingeleitet.",
                heading: 'Start gestartet',
                variant: 'success'
            );

            $this->selectedServer = null;
            Flux::modals()->close();
        } catch (\Throwable $e) {
            Flux::toast(
                text: $e->getMessage(),
                heading: 'Fehler beim Start',
                variant: 'danger'
            );
        }
    }

    public function getButtonColorProperty(): string
    {
        return match ($this->dhcpStatus) {
            'running' => 'text-emerald-500',
            'offline' => 'text-red-500',
            'loading', 'unloading' => 'text-yellow-500',
            'error' => 'text-red-600',
            default => 'text-gray-500',
        };
    }

    public function getButtonIconProperty(): string
    {
        return match ($this->dhcpStatus) {
            'running' => 'check-circle',
            'offline' => 'x-circle',
            'loading', 'unloading' => 'clock',
            'error' => 'exclamation-circle',
            default => 'question-mark-circle',
        };
    }
}
