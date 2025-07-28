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

    public function rules()
    {
        return [
            'servername' => 'required|min:2',
            'mac' => 'required|regex:/^(?:[0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}$/',
        ];
    }

    public function messages()
    {
        return [
            'servername.required' => 'Der Servername ist erforderlich.',
            'servername.min' => 'Der Servername muss mindestens 2 Zeichen lang sein.',
            'mac.required' => 'Die MAC-Adresse ist erforderlich.',
            'mac.regex' => 'Die MAC-Adresse muss aus sechs hexadezimalen Paaren bestehen, getrennt durch Doppelpunkte (z. B. 00:1A:2B:3C:4D:5E).',
        ];
    }

    public function generateSerial()
    {
        $this->validate();

        $this->error = '';
        $this->serial = '';

        $servername = strtolower(trim($this->servername));
        $mac = strtolower(trim($this->mac));
        $macPlain = str_replace(':', '', $mac);
        $servernameHex = bin2hex($servername);

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
