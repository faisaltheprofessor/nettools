<?php

namespace App\Console\Commands;

use App\Facades\RemoteSSH;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DnsStartCommand extends Command
{
    protected $signature = 'dns:start-service {server : The cluster node to start DNS on}';
    protected $description = 'Start the DNS service on a given server via SSH';

    public function handle()
    {
        $server = $this->argument('server');
        $service = 'DNS_SERVER';
        $cacheLockName = "dns_start_lock_{$server}";
        $cacheKey = "dns:start:status:{$server}";
        $lock = Cache::lock($cacheLockName, 30);

        if (!$lock->get()) {
            $this->warn("Ein anderer Startvorgang läuft bereits für {$server}.");
            Cache::put($cacheKey, 'locked', 60);
            return 1;
        }

        Cache::put($cacheKey, 'running', 60);

        try {
            $this->info("Versuche, DNS auf {$server} zu starten...");

            $sshUser = config('remote.dhcp.user');  // assuming same config keys as DHCP
            $sshPass = config('remote.dhcp.password');
            $clusterHost = config('remote.dhcp.cluster.ip');

            RemoteSSH::connect($clusterHost, $sshUser, $sshPass);

            RemoteSSH::execute("cluster status {$service} | grep Lives");
            $fullOutput = trim(RemoteSSH::getOutput());

            $this->info("Full DNS status line: '{$fullOutput}'");

            $parts = preg_split('/\s+/', $fullOutput);

            if (count($parts) < 3) {
                throw new \Exception("Unbekanntes Ausgabeformat vom DNS Status: '{$fullOutput}'");
            }

            $dnsStatus = $parts[0];     // e.g. Running, Offline
            $runningServer = $parts[2]; // e.g. vs002, vs003

            $this->info("Parsed DNS Status: '{$dnsStatus}', Running Server: '{$runningServer}'");

            if ($dnsStatus === 'Running') {
                $this->error("DNS kann nicht gestartet werden. DNS läuft bereits auf {$runningServer}.");
                Cache::put($cacheKey, "error: DNS already running on {$runningServer}", 60);
                return 1;
            }

            if ($dnsStatus === 'Offline') {
                RemoteSSH::execute("cluster online {$service} {$server}");
                $this->info("DNS Start auf {$server} durchgeführt.");
                Cache::put($cacheKey, 'success', 60);
                return 0;
            }

            $this->error("DNS kann nicht gestartet werden. Der DNS-Status ist unbekannt: '{$dnsStatus}'. Bitte später erneut versuchen.");
            Cache::put($cacheKey, 'error: unknown DNS status', 60);
            return 1;

        } catch (\Throwable $e) {
            Cache::put($cacheKey, 'error: ' . $e->getMessage(), 60);
            $this->error('Fehler beim Starten: ' . $e->getMessage());
            return 1;
        } finally {
            $lock->release();
        }
    }
}

