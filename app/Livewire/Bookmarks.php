<?php

namespace App\Livewire;

use App\Models\Bookmark;
use Livewire\Component;
use Livewire\WithFileUploads;

class Bookmarks extends Component
{
    use WithFileUploads;
    public string $search = '';
    public ?int $currentFolderId = null;
    public array $breadcrumbs = [];
    public bool $globalSearch = false;

    public bool $showModal = false;
    public string $newBookmarkName = '';
    public string $newBookmarkUrl = '';
    public ?int $newBookmarkParentId = null;
    public string $newBookmarkIcon = '';
    public string $newBookmarkType = 'link';

    public function mount(): void
    {
        $this->setBreadcrumbs();
    }

    public function openFolder(int $id): void
    {
        $this->currentFolderId = $id;
        $this->setBreadcrumbs();
    }

    public function goBackTo(int $index): void
    {
        $this->breadcrumbs = array_slice($this->breadcrumbs, 0, $index + 1);
        $this->currentFolderId = $this->breadcrumbs[$index]['id'] ?? null;
        $this->setBreadcrumbs();
    }

    protected function setBreadcrumbs(): void
    {
        $this->breadcrumbs = [];

        $folder = $this->currentFolderId ? Bookmark::find($this->currentFolderId) : null;
        while ($folder) {
            array_unshift($this->breadcrumbs, ['id' => $folder->id, 'name' => $folder->name]);
            $folder = $folder->parent;
        }

        array_unshift($this->breadcrumbs, ['id' => null, 'name' => 'Home']);
    }

    public function getFilteredItemsProperty()
    {
        $query = trim($this->search);

        $baseQuery = Bookmark::query();

        if (!$this->globalSearch) {
            $baseQuery->where('parent_id', $this->currentFolderId);
        }

        if ($query !== '') {
            $baseQuery->where(function ($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($query) . '%'])
                  ->orWhereRaw('LOWER(url) LIKE ?', ['%' . strtolower($query) . '%']);
            });
        } else if ($this->globalSearch) {
            $baseQuery->where('parent_id', null);
        }

        return $baseQuery->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
                         ->orderBy('name')
                         ->get();
    }

    public function createBookmark(): void
    {
        $this->validate([
            'newBookmarkName' => 'required|string|max:255',
            'newBookmarkType' => 'required|in:link,folder',
            'newBookmarkUrl' => $this->newBookmarkType === 'link' ? 'required|url' : 'nullable',
        ]);

        Bookmark::create([
            'name' => $this->newBookmarkName,
            'url' => $this->newBookmarkType === 'link' ? $this->newBookmarkUrl : null,
            'parent_id' => $this->newBookmarkParentId ?? $this->currentFolderId,
            'icon' => $this->newBookmarkIcon,
            'type' => $this->newBookmarkType,
        ]);

        $this->reset([
            'newBookmarkName',
            'newBookmarkUrl',
            'newBookmarkIcon',
            'newBookmarkParentId',
            'newBookmarkType',
            'showModal'
        ]);

        \Flux\Flux::toast('Bookmark created successfully.');
    }

    public function render()
    {
        return view('livewire.bookmarks', [
            'filteredItems' => $this->filteredItems,
            'allFolders' => Bookmark::where('type', 'folder')->orderBy('name')->get(),
        ]);
    }
}

