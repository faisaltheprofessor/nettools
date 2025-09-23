<?php

namespace App\Livewire;

use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class FileUploader extends Component
{
    use WithFileUploads;

    #[Validate(['newFiles.*' => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:10240'])]
    public $newFiles = [];

    public $allFiles = []; // ['path' => ..., 'name' => ...]

    protected $listeners = [
        'reset-file-uploader' => 'resetUploader',
    ];

    public function updatedNewFiles()
    {
        $this->validate();

        foreach ($this->newFiles as $file) {
            $timestampedName = time().'_'.$file->getClientOriginalName();
            $storedPath = $file->storeAs('feedback', $timestampedName, 'public');
            $this->allFiles[] = [
                'path' => $storedPath,
                'name' => $file->getClientOriginalName(),
            ];
        }

        // Pass paths only to parent component
        $this->dispatch('filesUploaded', collect($this->allFiles)->pluck('path')->toArray());

        $this->reset('newFiles');
    }

    public function removeFile($index)
    {
        unset($this->allFiles[$index]);
        $this->allFiles = array_values($this->allFiles);
        $this->dispatch('filesUploaded', collect($this->allFiles)->pluck('path')->toArray());
    }

    public function resetUploader()
    {
        $this->reset(['newFiles', 'allFiles']);
    }

    public function render()
    {
        return view('livewire.file-uploader');
    }
}
