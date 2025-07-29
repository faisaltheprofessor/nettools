<?php

namespace App\Livewire;

use Flux\Flux;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class DNS extends Component
{
    public array $servers = ['vs002', 'vs003', 'vs004'];

    public ?string $dnsStatus = null;

    public ?string $runningServer = null;

    public bool $loading = false;

    public bool $beingRestarted = false;

    public ?string $selectedServer = null;

    public function render()
    {
        return view('livewire.dns');
    }

    public function mount()
    {
        $this->getDnsStatus();
    }

    public function getDnsStatus(): void
    {
        if ($this->loading || $this->beingRestarted) {
            return;
        }

        $this->loading = true;

        try {
            $status = Cache::get('dns:status');

            if (!$status) {
                throw new \Exception('Kein Status im Cache gefunden.');
            }

            $this->runningServer = $status['running_server'] ?? null;
            $raw = $status['status'] ?? 'error';

            $this->dnsStatus = match ($raw) {
                'Running' => 'running',
                'Offline' => 'offline',
                'Loading' => 'loading',
                'Unloading' => 'unloading',
                default => 'error',
            };

            if ($this->dnsStatus !== 'running') {
                Flux::toast(
                    text: "DNS ist aktuell im Status: {$this->dnsStatus}.",
                    heading: 'DNS-Status',
                    variant: $this->dnsStatus === 'offline' ? 'danger' : 'warning'
                );
            }
        } catch (\Throwable $e) {
            $this->dnsStatus = 'error';
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

    public function startDns(): void
    {
        if (!$this->selectedServer) {
            Flux::toast(
                text: 'Bitte einen Server auswählen.',
                heading: 'Keine Auswahl',
                variant: 'warning'
            );
            return;
        }

        if ($this->loading || $this->dnsStatus === 'running') {
            Flux::toast(
                text: 'DNS ist bereits aktiv oder wird geladen.',
                heading: 'Start blockiert',
                variant: 'info'
            );
            return;
        }

        try {
            Artisan::queue('dns:start-service', [
                'server' => $this->selectedServer,
            ]);

            Flux::toast(
                text: "Start des DNS-Dienstes auf {$this->selectedServer} wurde eingeleitet.",
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

    public function restartDns(): void
    {
        if ($this->beingRestarted || $this->loading) {
            return;
        }

        if (Cache::get('dns:restart:queued')) {
            Flux::toast(
                text: 'Ein Neustart läuft bereits oder ist geplant.',
                heading: 'Bereits in Warteschlange',
                variant: 'warning'
            );

            return;
        }

        $this->beingRestarted = true;

        try {
            Artisan::queue('dns:restart-service');

            Flux::toast(
                text: 'Neustart wurde gestartet. Bitte prüfen Sie den Status in Kürze.',
                heading: 'Neustart läuft',
                variant: 'success'
            );

        } catch (\Throwable $e) {
            Flux::toast(
                text: $e->getMessage(),
                heading: 'Neustart-Fehler',
                variant: 'danger'
            );
        } finally {
            $this->beingRestarted = false;
            Flux::modals()->close();
        }
    }


    public function migrateDns(string $node): void
{
    if ($this->loading) {
        return;
    }

    try {
        if (Cache::lock('dns_migrate_lock', 30)->get() === false) {
            Flux::toast(
                text: 'Eine andere Migration ist gerade aktiv.',
                heading: 'Migration blockiert',
                variant: 'warning'
            );

            return;
        }

        Artisan::queue('dns:migrate-service', [
            'targetNode' => $node,
        ]);

        Flux::toast(
            text: "Migration nach {$node} gestartet.",
            heading: 'DNS Migration',
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


    public function getButtonColorProperty(): string
    {
        return match ($this->dnsStatus) {
            'running' => 'text-emerald-500',
            'offline' => 'text-red-500',
            'loading', 'unloading' => 'text-yellow-500',
            'error' => 'text-red-600',
            default => 'text-gray-500',
        };
    }

    public function getButtonIconProperty(): string
    {
        return match ($this->dnsStatus) {
            'running' => 'check-circle',
            'offline' => 'x-circle',
            'loading', 'unloading' => 'clock',
            'error' => 'exclamation-circle',
            default => 'question-mark-circle',
        };
    }

    public function pollRestartStatus(): void
    {
        $status = Cache::get('dns:restart:status');

        match (true) {
            $status === 'running' => $this->dnsStatus = 'loading',
            str_starts_with($status, 'error') => Flux::toast(
                text: $status,
                heading: 'Restart fehlgeschlagen',
                variant: 'danger'
            ),
            $status === 'success' => Flux::toast(
                text: 'DNS wurde erfolgreich neugestartet.',
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
}

