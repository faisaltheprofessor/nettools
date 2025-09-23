<?php

namespace App\Livewire;

use App\Models\Bookmark;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SidebarBookmarkFolders extends Component
{
    protected $listeners = ['bookmarksUpdated' => '$refresh'];

    public function render()
    {
        $userGuid = optional(Auth::user())->guid;

        // Get ONLY accessible folders (global + owned), sorted by name
        $folders = Bookmark::query()
            ->accessible($userGuid)
            ->where('type', 'folder')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'icon_name']);

        // Group by parent_id for recursion; use 0 for root/null
        $byParent = $folders->groupBy(fn ($f) => $f->parent_id ?? 0);

        return view('livewire.sidebar-bookmark-folders', [
            'byParent' => $byParent,
        ]);
    }
}
