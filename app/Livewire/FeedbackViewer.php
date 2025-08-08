<?php

namespace App\Livewire;

use App\Models\Feedback;
use Livewire\Component;
use Livewire\WithPagination;

class FeedbackViewer extends Component
{
    use WithPagination;

    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $selectedFeedback = null;

    protected $paginationTheme = 'tailwind';

    public function updatingSortField()
    {
        $this->resetPage();
    }

    public function updatingSortDirection()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function selectFeedback($id)
    {
        $this->selectedFeedback = Feedback::with('user')->find($id);
    }

    public function render()
    {
        $query = Feedback::with('user');

        if ($this->sortField === 'user.name') {
            // Join users table for sorting by user name
            $query = $query->join('users', 'feedback.user_guid', '=', 'users.guid')
                ->orderBy('users.name', $this->sortDirection)
                ->select('feedback.*');
        } else {
            $query = $query->orderBy($this->sortField, $this->sortDirection);
        }

        $feedbacks = $query->paginate(5);

        return view('livewire.feedback-viewer', [
            'feedbacks' => $feedbacks,
        ]);
    }
}
