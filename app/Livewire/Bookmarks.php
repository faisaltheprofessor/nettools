<?php

namespace App\Livewire;

use App\Models\Bookmark;
use Livewire\Component;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\Computed;
class Bookmarks extends Component
{
    public string $search = "";
    public ?int $currentFolderId = null;
    public array $breadcrumbs = [];

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

    #[Computed]
    public function filteredItems()
    {
        $query = trim($this->search);

        $baseQuery = Bookmark::where('parent_id', $this->currentFolderId);

        if ($query !== '') {
            $baseQuery->where(function ($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($query) . '%'])
                  ->orWhereRaw('LOWER(url) LIKE ?', ['%' . strtolower($query) . '%']);
            });
        }

        // folders first, then links, ordered by name
        return $baseQuery->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
                         ->orderBy('name')
                         ->get();
    }

    public function render()
    {
        return view('livewire.bookmarks', [
            'filteredItems' => $this->filteredItems,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }
}

