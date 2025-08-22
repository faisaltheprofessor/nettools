<?php

namespace App\Livewire;

use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class FeedbackViewer extends Component
{
    use WithPagination;

    // sorting + selection
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public ?Feedback $selectedFeedback = null;

    // filters
    public string $filterType = '';
    public string $filterUser = '';
    public string $filterStatus = '';
    public string $search = '';

    // new top-level comment
    public string $newComment = '';

    // reply boxes per comment id
    /** @var array<int,string> */
    public array $replyBoxes = []; // [commentId => draft text]
    /** @var null|int */
    public ?int $activeReplyFor = null;

    // mentions (live search)
    public bool $mentionOpen = false;        // global (Alpine holds per-box open state; LW holds results)
    public string $mentionQuery = '';
    /** @var array<int,array{guid:string,name:string}> */
    public array $mentionResults = [];
    public int $mentionLimit = 8;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'filterType',
        'filterUser',
        'filterStatus',
        'search',
        'sortField',
        'sortDirection',
    ];

    // reset pagination on filter change
    public function updatedFilterType(){ $this->resetPage(); }
    public function updatedFilterUser(){ $this->resetPage(); }
    public function updatedFilterStatus(){ $this->resetPage(); }
    public function updatedSearch(){ $this->resetPage(); }
    public function updatingSortField(){ $this->resetPage(); }
    public function updatingSortDirection(){ $this->resetPage(); }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function selectFeedback(int $id): void
    {
        $this->selectedFeedback = Feedback::with([
            'user',
            'comments.user',
            'comments.reactions.user',
        ])->find($id);

        $this->newComment = '';
        $this->replyBoxes = [];
        $this->activeReplyFor = null;
        $this->mentionResults = [];
        $this->mentionQuery = '';
        $this->mentionOpen = false;
    }

    public function setStatus(string $status): void
    {
        if (!$this->selectedFeedback) return;
        if (!in_array($status, Feedback::statuses(), true)) return;

        $this->selectedFeedback->update(['status' => $status]);
        $this->refreshThread();
    }

    public function addComment(): void
    {
        $body = trim($this->newComment);
        if (!$this->selectedFeedback || $body === '') return;

        FeedbackComment::create([
            'feedback_id' => $this->selectedFeedback->id,
            'user_guid'   => Auth::user()->guid,
            'body'        => $body,
            'parent_id'   => null,
        ]);

        $this->newComment = '';
        $this->refreshThread();
    }

    public function openReply(int $commentId): void
    {
        $this->activeReplyFor = $commentId;
        if (!isset($this->replyBoxes[$commentId])) {
            $name = optional(FeedbackComment::with('user')->find($commentId)->user)->name ?? null;
            $this->replyBoxes[$commentId] = $name ? '@'.$name.' ' : '';
        }
    }

    public function addReply(int $parentId): void
    {
        if (!$this->selectedFeedback) return;
        $body = trim($this->replyBoxes[$parentId] ?? '');
        if ($body === '') return;

        FeedbackComment::create([
            'feedback_id' => $this->selectedFeedback->id,
            'user_guid'   => Auth::user()->guid,
            'body'        => $body,
            'parent_id'   => $parentId,
        ]);

        // clear only this reply box
        unset($this->replyBoxes[$parentId]);
        if ($this->activeReplyFor === $parentId) {
            $this->activeReplyFor = null;
        }
        $this->refreshThread();
    }

    // keep reaction funcs from your original
    public function toggleReaction(int $commentId, string $emoji): void
    {
        $comment = FeedbackComment::find($commentId);
        if (!$comment) return;

        $userGuid = Auth::user()->guid;
        $existing = $comment->reactions()
            ->where('user_guid', $userGuid)
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $comment->reactions()->create([
                'user_guid' => $userGuid,
                'emoji'     => $emoji,
            ]);
        }

        $this->refreshThread();
    }

    public function refreshReactions(?int $commentId = null): void
    {
        $this->refreshThread();
    }

    // mention helpers
    public function searchMentions(string $q = ''): void
    {
        $q = trim($q);
        if ($q === '') {
            $this->mentionResults = [];
            return;
        }

        $this->mentionResults = User::query()
            ->where('name', 'like', '%'.$q.'%')
            ->orderBy('name')
            ->limit($this->mentionLimit)
            ->get(['guid','name'])
            ->map(fn($u) => ['guid' => $u->guid, 'name' => $u->name])
            ->toArray();
    }

    public function replyWithMention(string $name, ?int $targetCommentId = null): void
    {
        $prefix = '@'.$name.' ';
        if ($targetCommentId) {
            $existing = $this->replyBoxes[$targetCommentId] ?? '';
            $this->replyBoxes[$targetCommentId] = $prefix.$existing;
        } else {
            $this->newComment = $prefix.$this->newComment;
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['filterType','filterStatus','filterUser','search']);
        $this->resetPage();
    }

    protected function refreshThread(): void
    {
        if ($this->selectedFeedback) {
            $this->selectedFeedback->refresh()->load(['user','comments.user','comments.reactions.user']);
        }
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

        $feedbacks = $query->paginate(12);

        return view('livewire.feedback-viewer', [
            'feedbacks'   => $feedbacks,
            'allStatuses' => Feedback::statuses(),
        ]);
    }
}
