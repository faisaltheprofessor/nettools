<?php

namespace App\Console\Commands;

use App\Facades\RemoteSSH;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Log;
use Throwable;

class DnsMigrateCommand extends Command implements ShouldQueue
{
    protected $signature = 'dns:migrate-service {targetNode}';

    protected $description = 'Migriert den laufenden DNS-Dienst zu einem anderen Cluster-Knoten';

    public function handle()
    {
        Log::info('DNS Migration gestartet');
        $cacheKey = 'dns:migrate:status';
        $lock = Cache::lock('dns_migrate_lock', 30);

        Cache::put($cacheKey, 'running', 60);

        try {
            $targetNode = $this->argument('targetNode');

            $sshUser = config('remote.dns.user');
            $sshPass = config('remote.dns.password');
            $clusterHost = config('remote.dns.host');

            Log::info("Verbinde mit Cluster-Host {$clusterHost}...");

            RemoteSSH::connect($clusterHost, $sshUser, $sshPass);

            RemoteSSH::execute("cluster status DNS_SERVER | grep Lives | awk '{print \$1}'");
            $status = trim(RemoteSSH::getOutput());

            RemoteSSH::execute("cluster status DNS_SERVER | grep Lives | awk '{print \$3}'");
            $currentNode = trim(RemoteSSH::getOutput());

            if ($status !== 'Running') {
                Cache::put($cacheKey, 'offline', 60);
                $this->error('DNS ist nicht aktiv. Bitte zuerst starten.');

                return 1;
            }

            Log::info("DNS läuft aktuell auf {$currentNode}. Migriere nach {$targetNode}...");

            RemoteSSH::connect($clusterHost, $sshUser, $sshPass); // Optional reconnect
            RemoteSSH::execute("cluster migrate DNS_SERVER {$targetNode}");

            Cache::put('dns:status', [
                'running_server' => $targetNode,
                'status' => 'Running',
            ], 60);

            Cache::put($cacheKey, 'success', 60);

            Log::info("DNS wurde erfolgreich nach {$targetNode} migriert.");

            return 0;

        } catch (Throwable $e) {
            Cache::put($cacheKey, 'error: '.$e->getMessage(), 60);
            Log::error('Fehler bei der DNS-Migration: '.$e->getMessage());

            return 1;
        } finally {
            $lock->release();
        }
    }
}
