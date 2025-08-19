<?php

namespace App\Livewire;

use App\Models\Feedback;
use App\Models\FeedbackComment;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class FeedbackViewer extends Component
{
    use WithPagination;

    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $selectedFeedback = null;

    // filters
    public $filterType = '';
    public $filterUser = '';
    public $filterStatus = '';
    public $search = '';

    // discussion
    public $newComment = '';

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'filterType',
        'filterUser',
        'filterStatus',
        'search',
        'sortField',
        'sortDirection',
    ];

    public function updatedFilterType(){ $this->resetPage(); }
    public function updatedFilterUser(){ $this->resetPage(); }
    public function updatedFilterStatus(){ $this->resetPage(); }
    public function updatedSearch(){ $this->resetPage(); }
    public function updatingSortField(){ $this->resetPage(); }
    public function updatingSortDirection(){ $this->resetPage(); }

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
        $this->selectedFeedback = Feedback::with(['user','comments.user'])->find($id);
        $this->newComment = '';
    }

    public function setStatus($status)
    {
        if (!$this->selectedFeedback) return;
        if (!in_array($status, Feedback::statuses(), true)) return;

        $this->selectedFeedback->update(['status' => $status]);
        $this->selectedFeedback->refresh()->load(['user','comments.user']);
    }

    public function addComment()
    {
        $body = trim($this->newComment);
        if (!$this->selectedFeedback || $body === '') return;

        FeedbackComment::create([
            'feedback_id' => $this->selectedFeedback->id,
            'user_guid'   => Auth::user()->guid,
            'body'        => $body,
        ]);

        $this->newComment = '';
        $this->selectedFeedback->refresh()->load(['user','comments.user']);
    }

    public function clearFilters(): void
    {
        $this->reset(['filterType','filterStatus','filterUser','search']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Feedback::with('user');

        if ($this->filterType !== '')   $query->where('type', $this->filterType);
        if ($this->filterStatus !== '') $query->where('status', $this->filterStatus);
        if ($this->filterUser !== '') {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', '%'.$this->filterUser.'%'));
        }
        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where(function($q) use ($s){
                $q->where('title','like',$s)->orWhere('description','like',$s);
            });
        }

        if ($this->sortField === 'user.name') {
            $query = $query->join('users', 'feedback.user_guid', '=', 'users.guid')
                ->orderBy('users.name', $this->sortDirection)
                ->select('feedback.*');
        } else {
            $query = $query->orderBy($this->sortField, $this->sortDirection);
        }

        $feedbacks = $query->paginate(10);

        return view('livewire.feedback-viewer', [
            'feedbacks'   => $feedbacks,
            'allStatuses' => Feedback::statuses(),
        ]);
    }
}
