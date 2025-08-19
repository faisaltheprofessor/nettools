<?php

namespace App\Livewire;

use App\Models\AppNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationBell extends Component
{
    use WithPagination;

    public $unreadCount = 0;
    public $open = false; // optional: to control dropdown with Alpine
    protected $paginationTheme = 'tailwind';

    protected $listeners = [
        'notifications:refresh' => 'refreshData',
    ];

    public function mount(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $this->unreadCount = AppNotification::where('user_guid', Auth::user()->guid)->unread()->count();
    }

    public function markAsRead(int $id): void
    {
        $n = AppNotification::where('user_guid', Auth::user()->guid)->find($id);
        if (!$n) return;
        if (is_null($n->read_at)) {
            $n->update(['read_at' => now()]);
            $this->refreshData();
        }
    }

    public function markAllAsRead(): void
    {
        AppNotification::where('user_guid', Auth::user()->guid)->unread()->update(['read_at' => now()]);
        $this->refreshData();
    }

    public function render()
    {
        $items = AppNotification::where('user_guid', Auth::user()->guid)
            ->latest()
            ->take(12)
            ->get();

        return view('livewire.notification-bell', [
            'items' => $items,
        ]);
    }
}
