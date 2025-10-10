<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Str;

class ServiceCard extends Component
{
    /** 'dns' | 'dhcp' */
    public string $service = 'dns';

    /** Display title (e.g., “DNS-Cluster”) */
    public string $name = 'Service';

    /** Derived modal key (stable per instance) */
    public string $modalKey = '';

    /** Current status mapped to: running|offline|loading|unloading|error */
    public string $status = 'offline';

    /** Optional description */
    public ?string $description = null;

    /** Cache key for poller */
    protected string $pollCacheKey = '';

    public function mount(): void
    {
        $this->service = strtolower($this->service) === 'dhcp' ? 'dhcp' : 'dns';
        $this->pollCacheKey = "{$this->service}:status";
        $this->modalKey = 'svc-' . $this->service . '-' . Str::slug($this->name) . '-action';

        $this->readStatusFromCache();
    }

    public function render()
    {
        // Precompute classes & modal text (no Blade conditionals inside flux tags)
        $badgeClasses = $this->badgeClasses();
        $modalTitle   = $this->status === 'offline' ? 'Dienst starten?' : 'Dienst neu starten?';
        $modalBody    = $this->status === 'offline'
            ? "Der Dienst {$this->service} ist aktuell offline. Aktion jetzt anstoßen?"
            : "Der Dienst {$this->service} läuft. Möchten Sie einen Neustart anstoßen?";
        $confirmLabel = $this->status === 'offline' ? 'Start anfragen' : 'Neustart anfragen';
        $confirmIcon  = $this->status === 'offline' ? 'play' : 'pencil';

        return view('livewire.service-card', compact(
            'badgeClasses', 'modalTitle', 'modalBody', 'confirmLabel', 'confirmIcon'
        ));
    }

    /** Poll the cache every few seconds */
    public function refreshStatus(): void
    {
        $this->readStatusFromCache();
    }

    #[On('serviceStatusUpdated')]
    public function onExternalStatusUpdated(string $service, string $status): void
    {
        if (strtolower($service) !== $this->service) {
            return;
        }
        $this->status = $this->mapStatus($status);
    }

    /** Modal confirm -> just emit event; dedicated components do the real work */
    public function confirmAction(): void
    {
        if ($this->status === 'offline') {
            // Let your DHCP/DNS components open their own “start” flows (server select, etc.)
            $this->dispatch('service:start', service: $this->service);
        } else {
            $this->dispatch('service:restart', service: $this->service);
        }
        // Modal will be closed by <flux:modal.close> wrapper on the button in the blade
    }

    /** Helpers */
    protected function readStatusFromCache(): void
    {
        $payload = Cache::get($this->pollCacheKey);
        $raw = is_array($payload) ? (string)($payload['status'] ?? 'error') : 'error';
        $this->status = $this->mapStatus($raw);
    }

    protected function mapStatus(string $raw): string
    {
        return match (strtolower($raw)) {
            'running'    => 'running',
            'offline'    => 'offline',
            'loading'    => 'loading',
            'unloading'  => 'unloading',
            default      => 'error',
        };
    }

    protected function badgeClasses(): string
    {
        return match ($this->status) {
            'running'      => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 ring-1 ring-green-200/60 dark:ring-green-800',
            'loading',
            'unloading'    => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 ring-1 ring-amber-200/60 dark:ring-amber-800',
            'offline'      => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 ring-1 ring-red-200/60 dark:ring-red-800',
            default        => 'bg-zinc-100 dark:bg-zinc-800/50 text-zinc-700 dark:text-zinc-300 ring-1 ring-zinc-200/60 dark:ring-zinc-700',
        };
    }
}
