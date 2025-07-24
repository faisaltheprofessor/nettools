<?php

namespace App\Console\Commands;

use App\Facades\RemoteSSH;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class DhcpMigrateCommand extends Command implements ShouldQueue
{
    protected $signature = 'dhcp:migrate-service {targetNode}';

    protected $description = 'Migriert den laufenden DHCP-Dienst zu einem anderen Cluster-Knoten';

    public function handle()
    {
        \Log::info('Migration Started');
        $cacheKey = 'dhcp:migrate:status';
        $lock = Cache::lock('dhcp_migrate_lock', 30);

        Cache::put($cacheKey, 'running', 60);

        try {
            $targetNode = $this->argument('targetNode');

            $sshUser = config('remote.dhcp.user');
            $sshPass = config('remote.dhcp.password');
            $clusterHost = config('remote.dhcp.host');

            \Log::info("Verbinde mit Cluster-Host {$clusterHost}...");

            RemoteSSH::connect($clusterHost, $sshUser, $sshPass);

            RemoteSSH::execute("cluster status DHCP_SERVER | grep Lives | awk '{print \$1}'");
            $status = trim(RemoteSSH::getOutput());

            RemoteSSH::execute("cluster status DHCP_SERVER | grep Lives | awk '{print \$3}'");
            $currentNode = trim(RemoteSSH::getOutput());

            if ($status !== 'Running') {
                Cache::put($cacheKey, 'offline', 60);
                $this->error('DHCP ist nicht aktiv. Bitte zuerst starten.');

                return 1;
            }

            \Log::info("DHCP läuft aktuell auf {$currentNode}. Migriere nach {$targetNode}...");

            RemoteSSH::connect($clusterHost, $sshUser, $sshPass); // Optional reconnect
            RemoteSSH::execute("cluster migrate DHCP_SERVER {$targetNode}");

            Cache::put('dhcp:status', [
                'running_server' => $targetNode,
                'status' => 'Running',
            ], 60);

            Cache::put($cacheKey, 'success', 60);

            \Log::info("DHCP wurde erfolgreich nach {$targetNode} migriert.");

            return 0;

        } catch (\Throwable $e) {
            Cache::put($cacheKey, 'error: ' . $e->getMessage(), 60);
            \Log::error('Fehler bei der Migration: ' . $e->getMessage());

            return 1;
        } finally {
            $lock->release();
        }
    }
}
