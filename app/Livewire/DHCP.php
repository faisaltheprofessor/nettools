<?php

namespace App\Livewire;

use App\Support\LogsServiceActions;
use Exception;
use Flux\Flux;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Throwable;

class DHCP extends Component
{
    use LogsServiceActions;

    public array $servers = ['vs002', 'vs003', 'vs004'];

    public ?string $dhcpStatus = null;
    public ?string $runningServer = null;

    public bool $loading = false;
    public bool $beingRestarted = false;

    public ?string $selectedServer = null;

    /**
     * Selected services in the restart modal: values are 'dhcp' and/or 'dns'.
     * Bound to <flux:checkbox.group wire:model="restartServices">.
     */
    public array $restartServices = ["dhcp", "dns"]; // default: none selected

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
                throw new Exception('Kein Status im Cache gefunden.');
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
        } catch (Throwable $e) {
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

    /**
     * Legacy single-service restart for DHCP (kept as-is for other entry points).
     */
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

            // log restart
            $this->logAction('dhcp', 'restart', $this->runningServer, ['queued' => true]);

            Flux::toast(
                text: 'Neustart wurde gestartet. Bitte prüfen Sie den Status in Kürze.',
                heading: 'DHCP Neustart',
                variant: 'success'
            );

            Flux::modals()->close();
        } catch (Throwable $e) {
            Flux::toast(
                text: $e->getMessage(),
                heading: 'Neustart-Fehler',
                variant: 'danger'
            );
        } finally {
            $this->beingRestarted = false;
        }
    }

    /**
     * New: restart the services chosen in the confirmation dialog.
     * Supported values: 'dhcp', 'dns' (or both).
     */
    public function restartSelectedServices(): void
    {
        // Normalize + guard
        $services = array_values(array_unique(array_map('strtolower', $this->restartServices)));
        $services = array_intersect($services, ['dhcp', 'dns']);

        if (empty($services)) {
            Flux::toast(
                text: 'Bitte mindestens einen Dienst auswählen (DHCP, DNS oder Beide).',
                heading: 'Keine Auswahl',
                variant: 'warning'
            );
            return;
        }

        if ($this->beingRestarted) {
            return;
        }

        $this->beingRestarted = true;

        try {
            $queued = [];

            // Queue DHCP restart if selected
            if (in_array('dhcp', $services, true)) {
                if (Cache::get('dhcp:restart:queued')) {
                    Flux::toast(
                        text: 'DHCP-Neustart ist bereits in der Warteschlange.',
                        heading: 'DHCP bereits geplant',
                        variant: 'info'
                    );
                } else {
                    Artisan::queue('dhcp:restart-service');
                    $queued[] = 'DHCP';
                    $this->logAction('dhcp', 'restart', $this->runningServer, ['queued' => true]);
                }
            }

            // Queue DNS restart if selected
            if (in_array('dns', $services, true)) {
                // If you have a similar cache/lock for DNS, check it here analogously.
                Artisan::queue('dns:restart-service');
                $queued[] = 'DNS';
                $this->logAction('dns', 'restart', null, ['queued' => true]);
            }

            if (!empty($queued)) {
                $list = implode(' & ', $queued);
                Flux::toast(
                    text: "Neustart gestartet für: {$list}.",
                    heading: 'Neustart eingeleitet',
                    variant: 'success'
                );
            }

            // Reset selection and close modal
            $this->restartServices = [];
            Flux::modals()->close();

        } catch (Throwable $e) {
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
            is_string($status) && str_starts_with($status, 'error') => Flux::toast(
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

            // log migrate
            $this->logAction('dhcp', 'migrate', $node);

            Flux::toast(
                text: "Migration nach {$node} gestartet.",
                heading: 'DHCP Migration',
                variant: 'success'
            );
        } catch (Throwable $e) {
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

            // log start
            $this->logAction('dhcp', 'start', $this->selectedServer);

            Flux::toast(
                text: "Start des DHCP-Dienstes auf {$this->selectedServer} wurde eingeleitet.",
                heading: 'Start gestartet',
                variant: 'success'
            );

            $this->selectedServer = null;
            Flux::modals()->close();
        } catch (Throwable $e) {
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
