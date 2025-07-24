<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Facades\RemoteSSH;

class DhcpRestartCommand extends Command
{
    protected $signature = 'dhcp:restart-service';
    protected $description = 'Restart the DHCP service via SSH';

    public function handle()
    {
        $cacheKey = 'dhcp:restart:status';
        $queuedKey = 'dhcp:restart:queued';
        $lock = Cache::lock('dhcp_restart_lock', 30);

        if (!$lock->get()) {
            $this->warn('Ein anderer Neustart läuft bereits.');
            Cache::put($cacheKey, 'locked', 60);
            return 1;
        }

        Cache::put($cacheKey, 'running', 60);
        Cache::put($queuedKey, true, 180); // 3-minute UI flag

        try {
            $this->info('Starte Neustart...');

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

            Cache::put($cacheKey, 'success', 60);
            $this->info("DHCP wurde erfolgreich auf {$runningServer} neugestartet.");
            return 0;

        } catch (\Throwable $e) {
            Cache::put($cacheKey, 'error: ' . $e->getMessage(), 60);
            $this->error("Fehler beim Neustart: " . $e->getMessage());
            return 1;
        } finally {
            $lock->release();
            Cache::forget($queuedKey);
        }
    }
}
