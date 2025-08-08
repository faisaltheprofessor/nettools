<?php

namespace App\Livewire;

use App\Models\Feedback as FeedbackModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Feedback extends Component
{
    public $type = 'feature';
    public $title;
    public $description;
    public $attachments = [];

    protected $rules = [
        'type' => 'required|in:feature,bug,feedback',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
    ];

    protected $listeners = ['filesUploaded' => 'updateAttachments'];

    public function updateAttachments($files)
    {
        $this->attachments = $files;
    }

    public function submit()
    {
        $this->validate();

        FeedbackModel::create([
            'user_guid'   => Auth::user()->guid, // make sure this column exists in DB
            'type'        => $this->type,
            'title'       => $this->title,
            'description' => $this->description,
            'attachments' => $this->attachments,
        ]);

        // Reset all form fields
        $this->reset(['type', 'title', 'description', 'attachments']);
        $this->type = 'feature'; // keep default

        // Tell FileUploader to reset itself
        $this->dispatch('reset-file-uploader');

        session()->flash('message', 'Feedback erfolgreich gesendet!');
    }

    public function render()
    {
        return view('livewire.feedback');
    }
}
