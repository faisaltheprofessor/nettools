<?php

namespace App\Livewire;

use App\Models\Feedback as FeedbackModel;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Feedback extends Component
{
    use WithFileUploads;

    public string $type = 'feature';

    public ?string $title = null;

    public ?string $description = null;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $attachments = [];

    protected $rules = [
        'type' => 'required|in:feature,bug,feedback',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'attachments.*' => 'image|max:5120', // only images, 5MB each
    ];

    protected $messages = [
        'attachments.*.image' => 'Nur Bilddateien sind erlaubt.',
        'attachments.*.max' => 'Jede Datei darf maximal 5 MB groß sein.',
    ];

    public function removeAttachment(int $index): void
    {
        if (isset($this->attachments[$index])) {
            unset($this->attachments[$index]);
            // reindex for clean loop indices
            $this->attachments = array_values($this->attachments);
        }
    }

    public function submit(): void
    {
        $this->validate();

        $storedPaths = [];
        foreach ($this->attachments as $file) {
            $storedPaths[] = $file->store('feedback_attachments', 'public');
        }

        FeedbackModel::create([
            'user_guid' => Auth::user()->guid,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'attachments' => $storedPaths,
            'status' => 'open',
        ]);

        $this->reset(['type', 'title', 'description', 'attachments']);
        $this->type = 'feature';

        $this->dispatch('feedback-submitted');
        Flux::modal('feedback-submitted')->show();
        $this->js('confetti()');
    }

    public function render()
    {
        return view('livewire.feedback');
    }
}
