<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;

class IpCalculator extends Component
{
    public $ip = '';
    public $subnet = '';
    public $results = null;
    public bool $showResultsModal = false;

    public function render()
    {
        return view('livewire.ip-calculator');
    }

    public function calculate()
    {
        $this->reset('results', 'showResultsModal');

        $ip = trim($this->ip);
        $subnet = trim($this->subnet);

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->results = ['error' => 'Ungültige IP-Adresse.'];
            return;
        }

        if (Str::contains($subnet, '.')) {
            $cidr = $this->maskToCidr($subnet);
            if ($cidr === null) {
                $this->results = ['error' => 'Ungültige Subnetzmaske.'];
                return;
            }
        } else {
            if (!is_numeric($subnet)) {
                $this->results = ['error' => 'Die Subnetzmaske muss eine Zahl oder Punktnotation sein.'];
                return;
            }
            $cidr = (int)$subnet;
            if ($cidr < 1 || $cidr > 32) {
                $this->results = ['error' => 'CIDR muss zwischen 1 und 32 liegen.'];
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

        $this->showResultsModal = true;
    }

    private function maskToCidr($mask)
    {
        if (!filter_var($mask, FILTER_VALIDATE_IP)) return null;
        $long = ip2long($mask);
        if ($long === false) return null;

        $bin = decbin($long);
        $ones = strpos($bin, '0');
        if ($ones === false) $ones = 32;
        if (substr_count($bin, '1') !== $ones) return null;

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
        return match (true) {
            $firstOctet >= 1 && $firstOctet <= 126 => 'A',
            $firstOctet >= 128 && $firstOctet <= 191 => 'B',
            $firstOctet >= 192 && $firstOctet <= 223 => 'C',
            $firstOctet >= 224 && $firstOctet <= 239 => 'D (Multicast)',
            $firstOctet >= 240 => 'E (Experimental)',
            default => 'Unknown',
        };
    }

    private function getIpType($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            ? 'Public'
            : 'Private or Reserved';
    }
}
