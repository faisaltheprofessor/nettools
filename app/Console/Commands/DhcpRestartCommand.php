<?php

namespace App\Console\Commands;

use App\Facades\RemoteSSH;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Log;
use Throwable;

class DhcpRestartCommand extends Command
{
    protected $signature = 'dhcp:restart-service';

    protected $description = 'Restart the DHCP service on the current cluster node or start it if it\'s offline';

    public function handle()
    {
        $cacheKey = 'dhcp:restart:status';
        $queuedKey = 'dhcp:restart:queued';
        $lock = Cache::lock('dhcp_restart_lock', 30);

        if (! $lock->get()) {
            $this->warn('Ein anderer Neustart läuft bereits.');
            Cache::put($cacheKey, 'locked', 60);

            return 1;
        }

        Cache::put($cacheKey, 'running', 60);
        Cache::put($queuedKey, true, 180); // 3-minute UI flag

        try {
            Log::info('Starte Neustart...');

            $this->info('Starte Neustart...');

            $sshUser = config('remote.dhcp.user');
            $sshPass = config('remote.dhcp.password');
            $clusterHost = config('remote.dhcp.cluster.hostname');
            $tmpFile = '/tmp/dhcprestart.sh';

            Log::info('Connecting');
            RemoteSSH::connect($clusterHost, $sshUser, $sshPass);

            Log::info('Executing');
            RemoteSSH::execute("cluster status DHCP_SERVER | grep Lives | awk '{print \$3}'");
            $runningServer = trim(RemoteSSH::getOutput());

            RemoteSSH::execute("cluster status DHCP_SERVER | grep Lives | awk '{print \$1}'");
            $status = trim(RemoteSSH::getOutput());

            // Handle Comatose status
            if (stripos($status, 'Comatose') !== false) {
                preg_match('/Running on (\S+)/', $status, $matches);
                $runningServer = $matches[1] ?? null;

                if ($runningServer) {
                    Log::info("DHCP service is Comatose on server {$runningServer}");
                    // Store the status in cache
                    Cache::put('dhcp:comatose:server', $runningServer, 60); // Store for 1 minute

                    // Take the service offline and online
                    RemoteSSH::connect($runningServer, $sshUser, $sshPass);

                    Log::info("Attempting to restart DHCP service on {$runningServer}");

                    // Offline and online commands
                    RemoteSSH::execute("CLUSTER OFFLINE DHCP_SERVER");
                    sleep(2); // Wait for 2 seconds
                    RemoteSSH::execute("CLUSTER ONLINE DHCP_SERVER");

                    $this->info("DHCP service has been restarted on {$runningServer}.");
                } else {
                    $this->warn("Could not determine which server is Comatose.");
                }
                return 0;
            }

            if ($status === 'Offline') {
                Log::info('Offline');
                $this->warn('DHCP ist offline. Starte stattdessen den Dienst.');
                $startCommand = app(DhcpStartCommand::class);

                return $startCommand->handle();
            }

            if (! str_starts_with($runningServer, 'vs')) {
                throw new Exception('DHCP läuft derzeit auf keinem bekannten Server.');
            }

            RemoteSSH::connect($runningServer, $sshUser, $sshPass);

            Log::info('Generating Script');
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

            Log::info('Executing Scripts');
            RemoteSSH::execute('echo '.escapeshellarg($script)." > {$tmpFile}");
            RemoteSSH::execute("chmod +x {$tmpFile}");
            RemoteSSH::execute("{$tmpFile} {$runningServer}");
            RemoteSSH::execute("rm -f {$tmpFile}");

            Cache::put($cacheKey, 'success', 60);
            $this->info("DHCP wurde erfolgreich auf {$runningServer} neugestartet.");

            return 0;

        } catch (Throwable $e) {
            Cache::put($cacheKey, 'error: '.$e->getMessage(), 60);
            $this->error('Fehler beim Neustart: '.$e->getMessage());

            return 1;

        } finally {
            $lock->release();
            Cache::forget($queuedKey);
        }
    }
}
