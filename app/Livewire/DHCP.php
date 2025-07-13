<?php

namespace App\Livewire;
use DivineOmega\SSHConnection\SSHConnection;
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
        $connection = (new SSHConnection())
                    -> to(env("DHCP_SERVER"))
                    -> as(env("DHCP_USER"))
                    -> withPassword(env("DHCP_PASSWORD"))
                    -> connect();

        $command = $connection->run('sudo systemctl is-active sshd');
        if (($command->getOutput()) === 'active')
        {
            Flux::toast(
                heading: 'Erfolgreich',
                text: $command->getOutput(),
                variant: 'success'
            );
        }

        else
        {

            Flux::toast(
                heading: 'Etwas ist schief gelaufen',
                text: $command->getOutput(),
                variant: 'danger'
            );
        }
    }
}
