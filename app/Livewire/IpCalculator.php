<?php

namespace App\Livewire;

use Illuminate\Support\Str;
use Livewire\Component;

class IpCalculator extends Component
{
    public $ip = '';
    public $subnet = '';
    public $results = null;
    public bool $showResultsModal = false;

    public function rules()
    {
        return [
            'ip' => ['required', 'ip'],
            'subnet' => ['required', 'regex:/^\d{1,2}$|^\d{1,3}(\.\d{1,3}){3}$/'],
        ];
    }

    public function messages()
    {
        return [
            'ip.required' => 'Die IP-Adresse ist erforderlich.',
            'ip.ip' => 'Bitte geben Sie eine gültige IPv4-Adresse ein.',
            'subnet.required' => 'Die Subnetzmaske ist erforderlich.',
            'subnet.regex' => 'Bitte geben Sie eine gültige Subnetzmaske ein (z.B. 24 oder 255.255.255.0).',
        ];
    }

    public function render()
    {
        return view('livewire.ip-calculator');
    }

    public function calculate()
    {
        $this->reset('results', 'showResultsModal');
        $this->validate();

        $ip = trim($this->ip);
        $subnet = trim($this->subnet);

        if (Str::contains($subnet, '.')) {
            $cidr = $this->maskToCidr($subnet);
            if ($cidr === null) {
                $this->results = ['error' => 'Ungültige Subnetzmaske.'];
                return;
            }
        } else {
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
        ];

        $this->showResultsModal = true;
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
}

