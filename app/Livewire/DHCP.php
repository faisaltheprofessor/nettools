<?php

namespace App\Livewire;

use App\Facades\RemoteSSH;
use Flux\Flux;
use Livewire\Component;

class DHCP extends Component
{
    public $servers = ['vs002', 'vs003', 'vs004'];

    public bool $dhcpStatus;

    public $output;

    public function render()
    {
        return view('livewire.dhcp');
    }

    public function getDhcpStatus()
    {
        try {
            RemoteSSH::connect(
                env('DHCP_SERVER'),
                env('DHCP_USER'),
                env('DHCP_PASSWORD')
            );

            RemoteSSH::execute('sudo systemctl is-active sshd');

            $output = trim(RemoteSSH::getOutput());

            if ($output === 'active') {
                Flux::toast(
                    text: $output,
                    heading: 'Erfolgreich',
                    variant: 'success'
                );
            } else {
                Flux::toast(
                    text: $output,
                    heading: 'Etwas ist schief gelaufen',
                    variant: 'danger'
                );
            }

        } catch (\Throwable $e) {
            Flux::toast(
                text: $e->getMessage(),
                heading: 'Verbindungsfehler',
                variant: 'danger'
            );
        }
    }
}
