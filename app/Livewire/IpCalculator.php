<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;

class IpCalculator extends Component
{
    public $ip = '';
    public $subnet = '';

    public $results = null;

    public function render()
    {
        return view('livewire.ip-calculator');
    }

    public function calculate()
    {
        $ip = trim($this->ip);
        $subnet = trim($this->subnet);

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->results = ['error' => 'Invalid IP address.'];
            return;
        }

        if (Str::contains($subnet, '.')) {
            $cidr = $this->maskToCidr($subnet);
            if ($cidr === null) {
                $this->results = ['error' => 'Invalid subnet mask.'];
                return;
            }
        } else {
            if (!is_numeric($subnet)) {
                $this->results = ['error' => 'Subnet mask must be a CIDR number or dotted mask.'];
                return;
            }
            $cidr = (int)$subnet;
            if ($cidr < 1 || $cidr > 32) {
                $this->results = ['error' => 'CIDR must be between 1 and 32.'];
                return;
            }
        }

        $subnetMaskLong = (-1 << (32 - $cidr)) & 0xFFFFFFFF;
        $subnetMask = long2ip($subnetMaskLong);
        $wildcardMaskLong = (~$subnetMaskLong) & 0xFFFFFFFF;
        $wildcardMask = long2ip($wildcardMaskLong);

        $ipLong = ip2long($ip) & 0xFFFFFFFF;
        $networkLong = $ipLong & $subnetMaskLong;
        $broadcastLong = $networkLong | $wildcardMaskLong;

        if ($cidr == 31) {
            $hostMin = $networkLong;
            $hostMax = $broadcastLong;
            $hostCount = 2;
        } elseif ($cidr == 32) {
            $hostMin = $hostMax = $ipLong;
            $hostCount = 1;
        } else {
            $hostMin = $networkLong + 1;
            $hostMax = $broadcastLong - 1;
            $hostCount = max(0, $hostMax - $hostMin + 1);
        }

        $ipClass = $this->getClass($ip);
        $ipType = $this->getIpType($ip);

        $this->results = [
            'Address' => $ip,
            'Subnet Mask' => $subnetMask,
            'Wildcard Mask' => $wildcardMask,
            'Network Address' => long2ip($networkLong),
            'HostMin' => long2ip($hostMin),
            'HostMax' => long2ip($hostMax),
            'Broadcast' => long2ip($broadcastLong),
            'Hosts/Net' => $hostCount,
            'Class' => $ipClass,
            'Type' => $ipType,
            'Binary' => [
                'Address' => $this->ipToBinary($ip),
                'Subnet Mask' => $this->ipToBinary($subnetMask),
                'Wildcard Mask' => $this->ipToBinary($wildcardMask),
                'Network Address' => $this->ipToBinary(long2ip($networkLong)),
                'HostMin' => $this->ipToBinary(long2ip($hostMin)),
                'HostMax' => $this->ipToBinary(long2ip($hostMax)),
                'Broadcast' => $this->ipToBinary(long2ip($broadcastLong)),
            ],
        ];
    }

    private function maskToCidr($mask)
    {
        if (!filter_var($mask, FILTER_VALIDATE_IP)) {
            return null;
        }
        $long = ip2long($mask);
        if ($long === false) {
            return null;
        }
        $bin = decbin($long);
        $ones = strpos($bin, '0');
        if ($ones === false) {
            $ones = 32;
        }
        if (substr_count($bin, '1') !== $ones) {
            return null;
        }
        return $ones;
    }

    private function ipToBinary($ip)
    {
        return collect(explode('.', $ip))
            ->map(fn($octet) => str_pad(decbin((int)$octet), 8, '0', STR_PAD_LEFT))
            ->implode('.');
    }

    private function getClass($ip)
    {
        $firstOctet = (int)explode('.', $ip)[0];
        if ($firstOctet >= 1 && $firstOctet <= 126) return 'A';
        if ($firstOctet >= 128 && $firstOctet <= 191) return 'B';
        if ($firstOctet >= 192 && $firstOctet <= 223) return 'C';
        if ($firstOctet >= 224 && $firstOctet <= 239) return 'D (Multicast)';
        if ($firstOctet >= 240) return 'E (Experimental)';
        return 'Unknown';
    }

    private function getIpType($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return 'Public';
        }
        return 'Private or Reserved';
    }
}
