<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Facades\RemoteSSH;
use Illuminate\Support\Facades\Cache;

class PollDnsStatus extends Command
{
    protected $signature = 'dns:poll-status';
    protected $description = 'Poll DNS service status and cache it';

    public function handle()
    {
        try {
            $sshUser = config('remote.dhcp.user');
            $sshPass = config('remote.dhcp.password');
            $clusterHost = config('remote.dhcp.host');

            RemoteSSH::connect($clusterHost, $sshUser, $sshPass);
            RemoteSSH::execute("cluster status DNS_SERVER | grep Running | awk '{print \$3}'");
            $runningServer = trim(RemoteSSH::getOutput());

            RemoteSSH::execute("cluster status DNS_SERVER | grep Lives | awk '{print \$1}'");
            $dhcpStatusRaw = trim(RemoteSSH::getOutput());

            Cache::put('dns:status', [
                'running_server' => $runningServer,
                'status' => $dhcpStatusRaw,
                'updated_at' => now()->toIso8601String()
            ], 30); // cache for 30 seconds

            $this->info("DNS status updated: {$dhcpStatusRaw} on {$runningServer}");
        } catch (\Throwable $e) {
            Cache::put('dns:status', [
                'status' => 'error',
                'running_server' => null,
                'updated_at' => now()->toIso8601String(),
                'error' => $e->getMessage()
            ], 30);

            $this->error("Polling failed: " . $e->getMessage());
        }
    }
}
