<?php

namespace App\Livewire;

use Livewire\Component;
use Spatie\Ssh\Ssh;

class DHCP extends Component
{
    public $servers = ['vs002', 'vs003', 'vs004'];
    public $result;
    public function render()
    {
        return view('livewire.dhcp');
    }

    public function getDhcpStatus()
    {
        $process = Ssh::create(env("SSH_USER"), "host")->usePassword(env('SSH_PASSWORD'))->execute("ls");
        $this->result = $process->isSuccessful();
    }
}
