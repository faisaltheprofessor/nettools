<?php

namespace App\Livewire;

use App\Facades\RemoteSSH;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class DHCP extends Component
{
    public array $servers = ['vs002', 'vs003', 'vs004'];

    public ?string $dhcpStatus = null;
    public ?string $runningServer = null;
    public bool $loading = false;
    public bool $beingRestarted = false;

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

        $lock = Cache::lock('dhcp_status_lock', 10);

        if (!$lock->get()) {
            Flux::toast(
                text: 'Diese Funktion wird aktuell durch einen anderen Benutzer genutzt. Bitte in wenigen Sekunden noch einmal probieren.',
                heading: 'Locked',
                variant: 'warning'
            );
            $this->loading = false;
            return;
        }

        try {
            $sshUser = config('remote.dhcp.user');
            $sshPass = config('remote.dhcp.password');
            $clusterHost = config('remote.dhcp.host');

            RemoteSSH::connect($clusterHost, $sshUser, $sshPass);
            RemoteSSH::execute("cluster status DHCP_SERVER | grep Running | awk '{print \$3}'");
            $this->runningServer = trim(RemoteSSH::getOutput());

            RemoteSSH::execute("cluster status DHCP_SERVER | grep Lives | awk '{print \$1}'");
            $dhcpStatusRaw = trim(RemoteSSH::getOutput());

            if (str_starts_with($this->runningServer, 'vs')) {
                RemoteSSH::connect($this->runningServer, $sshUser, $sshPass);

                switch ($dhcpStatusRaw) {
                    case 'Running':
                        $this->dhcpStatus = 'running';
                        break;
                    case 'Offline':
                        $this->dhcpStatus = 'offline';
                        Flux::toast(text: "DHCP läuft nicht und ist offline.", heading: 'Fehler', variant: 'danger');
                        break;
                    case 'Loading':
                        $this->dhcpStatus = 'loading';
                        Flux::toast(text: "DHCP fährt gerade hoch. Bitte in wenigen Sekunden erneut versuchen.", heading: 'Wartezeit', variant: 'warning');
                        break;
                    case 'Unloading':
                        $this->dhcpStatus = 'unloading';
                        Flux::toast(text: "DHCP fährt gerade runter. Bitte in wenigen Sekunden erneut versuchen.", heading: 'Wartezeit', variant: 'warning');
                        break;
                    default:
                        $this->dhcpStatus = 'error';
                        Flux::toast(text: "Status derzeit nicht ermittelbar. Bitte in wenigen Sekunden erneut versuchen.", heading: 'Unbekannter Status', variant: 'danger');
                        break;
                }
            } else {
                $this->dhcpStatus = 'error';
                $this->runningServer = null;
                Flux::toast(text: "Status derzeit nicht ermittelbar. Bitte in wenigen Sekunden erneut versuchen.", heading: 'Unbekannter Server', variant: 'danger');
            }
        } catch (\Throwable $e) {
            $this->dhcpStatus = 'error';
            $this->runningServer = null;
            Flux::toast(text: $e->getMessage(), heading: 'Verbindungsfehler', variant: 'danger');
        } finally {
            $lock->release();
            $this->loading = false;
        }
    }

    public function restartDhcp(): void
    {
        if ($this->beingRestarted || $this->loading) {
            return;
        }

        $this->beingRestarted = true;

        $lock = Cache::lock('dhcp_restart_lock', 30);

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
            $sshUser = config('remote.dhcp.user');
            $sshPass = config('remote.dhcp.password');
            $clusterHost = config('remote.dhcp.host');
            $tmpFile = '/tmp/dhcprestart.sh';

            RemoteSSH::connect($clusterHost, $sshUser, $sshPass);
            RemoteSSH::execute("cluster status DHCP_SERVER | grep Lives | awk '{print \$3}'");
            $runningServer = trim(RemoteSSH::getOutput());

            if (!str_starts_with($runningServer, 'vs')) {
                throw new \Exception("DHCP läuft derzeit auf keinem bekannten Server.");
            }

            RemoteSSH::connect($runningServer, $sshUser, $sshPass);

            $script = <<<'BASH'
#!/bin/bash
service=DHCP_SERVER
server="$1"
log="/tmp/dhcp_restart.log"

echo "Restarting DHCP on $server at $(date)" >> $log
cluster offline $service $server
sleep 2
cluster online $service $server

for i in {1..10}; do
    status=$(cluster status $service | grep Lives | awk '{print $1}')
    echo "Attempt $i: $status at $(date)" >> $log
    if [[ "$status" == "Running" ]]; then
        echo "Success at $(date)" >> $log
        exit 0
    fi
    sleep 3
done

echo "Failed after 10 attempts at $(date)" >> $log
exit 1
BASH;

            RemoteSSH::execute("echo " . escapeshellarg($script) . " > {$tmpFile}");
            RemoteSSH::execute("chmod +x {$tmpFile}");

            RemoteSSH::execute("{$tmpFile} {$runningServer}");

            RemoteSSH::execute("rm -f {$tmpFile}");

            Flux::toast(
                text: "DHCP Neustart auf {$runningServer} wurde durchgeführt. Bitte prüfen Sie den aktuellen Status.",
                heading: 'Neustart erfolgreich',
                variant: 'success'
            );

            Flux::modal()->close();

        } catch (\Throwable $e) {
            $this->dhcpStatus = 'error';
            $this->runningServer = null;
            Flux::toast(
                text: $e->getMessage(),
                heading: 'Neustart-Fehler',
                variant: 'danger'
            );
        } finally {
            $lock->release();
            $this->beingRestarted = false;
            $this->getDhcpStatus();
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

