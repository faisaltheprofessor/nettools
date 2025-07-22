<?php

namespace App\Livewire;

use App\Models\Bookmark;
use Livewire\Component;

class Bookmarks extends Component
{
    public string $search = '';
    public ?int $currentFolderId = null;
    public array $breadcrumbs = [];
    public bool $globalSearch = false;

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
    if ($index === 0) {
        // Reset everything to root
        $this->currentFolderId = null;
        $this->breadcrumbs = [['id' => null, 'name' => 'Home']];
    } else {
        $this->breadcrumbs = array_slice($this->breadcrumbs, 0, $index + 1);
        $this->currentFolderId = $this->breadcrumbs[$index]['id'] ?? null;
    }

    // Rebuild breadcrumbs from the new folder ID
    $this->setBreadcrumbs();

    // Force reactivity (even if currentFolderId was already null)
    $this->dispatch('$refresh');
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

    public function render()
    {
        return view('livewire.bookmarks', [
            'filteredItems' => $this->filteredItems,
        ]);
    }
}

