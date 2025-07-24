<?php

namespace App\Livewire;

use Livewire\Component;

class OVirtSerialNumberGenerator extends Component
{
    public $servername = '';

    public $mac = '';

    public $serial = '';

    public $error = '';

    public $showResultsModal = false;

    public function generateSerial()
    {
        $this->error = '';
        $this->serial = '';

        $servername = strtolower(trim($this->servername));
        $mac = strtolower(trim($this->mac));

        // Clean encoding and remove invalid characters

        // Validate MAC address
        if (!preg_match('/^(?:[0-9a-f]{2}:){5}[0-9a-f]{2}$/', $mac)) {
            $this->error = "? Die Eingabe '{$mac}' ist fehlerhaft!";
            $this->showResultsModal = true;

            return;
        }

        $macPlain = str_replace(':', '', $mac);
        $servernameHex = bin2hex($servername);

        // Validate the generated hex string
        if (!mb_check_encoding($servernameHex, 'UTF-8')) {
            $this->error = '? Fehler bei der Hex-Kodierung des Servernamens.';
            $this->showResultsModal = true;

            return;
        }

        $serial = substr_replace($servernameHex, '-', 8, 0);
        $this->serial = "{$serial}00-0000-0000-{$macPlain}";

    }

    public function render()
    {
        return view('livewire.o-virt-serial-number-generator');
    }
}
