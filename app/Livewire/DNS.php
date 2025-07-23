<?php

namespace App\Livewire;

use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Illuminate\Support\Facades\Artisan;

class DNS extends Component
{
    public array $servers = ['vs002', 'vs003', 'vs004'];

    public ?string $dnsStatus = null;
    public ?string $runningServer = null;
    public bool $loading = false;
    public bool $beingRestarted = false;

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
                throw new \Exception('Kein Status im Cache gefunden. Bitte warten Sie einen Moment und versuchen Sie es erneut.');
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

            // Optional toast if not running
            if ($this->dnsStatus !== 'running') {
                Flux::toast(
                    text: "dns ist aktuell im Status: {$this->dnsStatus}.",
                    heading: 'dns-Status',
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

    public function restartDns(): void
    {
           if ($this->beingRestarted || $this->loading) {
        return;
    }

    $this->beingRestarted = true;

    $lock = Cache::lock('dns_restart_lock', 30);

    if (!$lock->get()) {
        Flux::toast(
            text: 'Diese Funktion wird aktuell durch einen anderen Benutzer genutzt. Bitte in wenigen Sekunden noch einmal probieren.',
            heading: 'Locked',
            variant: 'warning'
        );
        $this->beingRestarted = false;
        return;
    }

    try {
        // Dispatch the Artisan command asynchronously, so UI stays responsive
        Artisan::queue('dns:restart-service');

        Flux::toast(
            text: 'Neustart wurde initiiert. Bitte prüfen Sie in wenigen Momenten den Status erneut.',
            heading: 'Neustart initiiert',
            variant: 'success'
        );


    } catch (\Throwable $e) {
        Flux::toast(
            text: $e->getMessage(),
            heading: 'Neustart-Fehler',
            variant: 'danger'
        );
    } finally {
        $lock->release();
        $this->beingRestarted = false;
        $this->getDnsStatus();
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
}
