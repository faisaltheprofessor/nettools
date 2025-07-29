<?php

namespace App\Console\Commands;

use App\Facades\RemoteSSH;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DhcpStartCommand extends Command
{
    protected $signature = 'dhcp:start-service {server : The cluster node to start DHCP on}';
    protected $description = 'Start the DHCP service on a given server via SSH';

    public function handle()
    {
        $server = $this->argument('server');
        $service = 'DHCP_SERVER';
        $cacheLockName = "dhcp_start_lock_{$server}";
        $cacheKey = "dhcp:start:status:{$server}";
        $lock = Cache::lock($cacheLockName, 30);

        if (!$lock->get()) {
            $this->warn("Ein anderer Startvorgang läuft bereits für {$server}.");
            Cache::put($cacheKey, 'locked', 60);
            return 1;
        }

        Cache::put($cacheKey, 'running', 60);

        try {
            $this->info("Versuche, DHCP auf {$server} zu starten...");

            $sshUser = config('remote.dhcp.user');
            $sshPass = config('remote.dhcp.password');
            $clusterHost = config('remote.dhcp.host');

            // Connect to cluster host to check DHCP service status
            RemoteSSH::connect($clusterHost, $sshUser, $sshPass);

            RemoteSSH::execute("cluster status {$service} | grep Lives");
            $fullOutput = trim(RemoteSSH::getOutput());

            $this->info("Full DHCP status line: '{$fullOutput}'");

            // Parse output parts (expect something like: Running Lives vs002 ...)
            $parts = preg_split('/\s+/', $fullOutput);

            if (count($parts) < 3) {
                throw new \Exception("Unbekanntes Ausgabeformat vom DHCP Status: '{$fullOutput}'");
            }

            $dhcpStatus = $parts[0];     // e.g. Running, Offline
            $runningServer = $parts[2];  // e.g. vs002, vs003

            $this->info("Parsed DHCP Status: '{$dhcpStatus}', Running Server: '{$runningServer}'");

            if ($dhcpStatus === 'Running') {
                $this->error("DHCP kann nicht gestartet werden. DHCP läuft bereits auf {$runningServer}.");
                Cache::put($cacheKey, "error: DHCP already running on {$runningServer}", 60);
                return 1;
            }

            if ($dhcpStatus === 'Offline') {
                // Start DHCP on the specified server
                RemoteSSH::execute("cluster online {$service} {$server}");
                $this->info("DHCP Start auf {$server} durchgeführt.");
                Cache::put($cacheKey, 'success', 60);
                return 0;
            }

            $this->error("DHCP kann nicht gestartet werden. Der DHCP-Status ist unbekannt: '{$dhcpStatus}'. Bitte später erneut versuchen.");
            Cache::put($cacheKey, 'error: unknown DHCP status', 60);
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

