<?php

namespace App\Livewire;

use App\Models\Bookmark;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\WithFileUploads;

class Bookmarks extends Component
{
    use WithFileUploads;

    protected $queryString = [
        'currentFolderId' => ['as' => 'folder', 'except' => null],
        'search' => ['as' => 'q', 'except' => ''],
    ];

    // UI state
    public string $search = '';

    public ?int $currentFolderId = null;

    public array $breadcrumbs = [];

    public bool $includeChildren = false;

    public string $viewMode = 'grid';     // grid|list (persisted: users.bookmark_view)

    public string $nameSortDir = 'asc';   // asc|desc  (persisted: users.bookmark_sort_dir)

    // Create state
    public bool $showModal = false;

    public string $newBookmarkName = '';

    public string $newBookmarkUrl = '';

    public ?int $newBookmarkParentId = null;

    public string $newBookmarkType = 'link';

    public string $iconChoice = 'hero';   // default hero on create

    public ?string $iconName = 'link';   // default hero icon for links

    public $newBookmarkIcon = null;

    public bool $makeGlobal = false;

    // Edit state
    public bool $showEditModal = false;

    public ?int $editId = null;

    public string $editName = '';

    public string $editType = 'link';

    public string $editUrl = '';

    public ?int $editParentId = null;

    public string $editIconChoice = 'favicon';

    public ?string $editIconName = null;

    public $editIconUpload = null;

    protected array $messages = [
        'newBookmarkName.required' => 'Dieses Feld ist erforderlich.',
        'newBookmarkUrl.required' => 'Dieses Feld ist erforderlich.',
        'newBookmarkUrl.url' => 'Bitte eine gültige URL eingeben.',
        'newBookmarkType.required' => 'Dieses Feld ist erforderlich.',
        'newBookmarkType.in' => 'Ungültiger Typ ausgewählt.',
        'iconName.required' => 'Bitte ein Symbol auswählen.',
        'editName.required' => 'Dieses Feld ist erforderlich.',
        'editUrl.required' => 'Dieses Feld ist erforderlich.',
        'editUrl.url' => 'Bitte eine gültige URL eingeben.',
    ];

    public function mount(): void
    {
        if ($u = Auth::user()) {
            // view mode
            $this->viewMode = in_array($u->bookmark_view ?? 'grid', ['grid', 'list'], true) ? $u->bookmark_view : 'grid';

            // sort dir (ASC/DESC)
            $dir = $u->bookmark_sort_dir ?? 'asc';
            $this->nameSortDir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';

            // force the sort key to 'name' to match your requirement
            if (($u->bookmark_sort ?? null) !== 'name') {
                $u->bookmark_sort = 'name';
                $u->save();
            }
        }

        $this->currentFolderId = ($this->currentFolderId === null || $this->currentFolderId === '')
            ? null
            : (int) $this->currentFolderId;

        // ensure create defaults consistent with requested behavior
        $this->applyCreateDefaults();

        $this->setBreadcrumbs();
    }

    public function hydrate(): void
    {
        $this->currentFolderId = ($this->currentFolderId === null || $this->currentFolderId === '')
            ? null
            : (int) $this->currentFolderId;

        $this->newBookmarkParentId = ($this->newBookmarkParentId === null || $this->newBookmarkParentId === '')
            ? null
            : (int) $this->newBookmarkParentId;

        $this->editParentId = ($this->editParentId === null || $this->editParentId === '')
            ? null
            : (int) $this->editParentId;

        if (! in_array($this->nameSortDir, ['asc', 'desc'], true)) {
            $this->nameSortDir = 'asc';
        }
        if (! in_array($this->viewMode, ['grid', 'list'], true)) {
            $this->viewMode = 'grid';
        }
    }

    protected function applyCreateDefaults(): void
    {
        if ($this->newBookmarkType === 'link') {
            $this->iconChoice = 'hero';
            if (! $this->iconName) {
                $this->iconName = 'link';
            }
        } else {
            $this->iconChoice = 'hero';
            $this->iconName = 'folder';
        }
    }

    /* ===== Admin & ownership ===== */

    protected function adminUsers(): array
    {
        $raw = (string) config('users.admins');

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    public function isAdmin(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->username, $this->adminUsers(), true);
    }

    protected function currentUserGuid(): ?string
    {
        return optional(Auth::user())->guid;
    }

    protected function canManage(Bookmark $b): bool
    {
        $uid = $this->currentUserGuid();

        return $this->isAdmin() || ($uid !== null && $b->user_guid === $uid);
    }

    /* ===== Prefs ===== */

    protected function persistPref(array $attrs): void
    {
        if (! $user = Auth::user()) {
            return;
        }
        foreach ($attrs as $k => $v) {
            $user->setAttribute($k, $v);
        }
        $user->save();
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['grid', 'list'], true)) {
            return;
        }
        $this->viewMode = $mode;
        $this->persistPref(['bookmark_view' => $mode]);
    }

    public function toggleNameSort(): void
    {
        $this->nameSortDir = $this->nameSortDir === 'asc' ? 'desc' : 'asc';
        $this->persistPref([
            'bookmark_sort' => 'name',
            'bookmark_sort_dir' => $this->nameSortDir,
        ]);
    }

    /* ===== Breadcrumbs ===== */

    protected function setBreadcrumbs(): void
    {
        $this->breadcrumbs = [];
        $folder = $this->currentFolderId ? Bookmark::find($this->currentFolderId) : null;

        while ($folder) {
            array_unshift($this->breadcrumbs, ['id' => $folder->id, 'name' => $folder->name]);
            $folder = $folder->parent;
        }

        array_unshift($this->breadcrumbs, ['id' => null, 'name' => 'Start']);
    }

    public function openFolder(int $id): void
    {
        $this->currentFolderId = $id;
        $this->setBreadcrumbs();
    }

    public function goBackTo(int $index): void
    {
        $this->breadcrumbs = array_slice($this->breadcrumbs, 0, $index + 1);
        $id = $this->breadcrumbs[$index]['id'] ?? null;
        $this->currentFolderId = ($id === null || $id === '') ? null : (int) $id;
        $this->setBreadcrumbs();
    }

    /* ===== Query helpers ===== */

    protected function scopeAccessible($q)
    {
        return $q->accessible($this->currentUserGuid());
    }

    protected function descendantFolderIds(?int $startId): array
    {
        if ($startId === null) {
            return [];
        }
        $all = $this->scopeAccessible(Bookmark::where('type', 'folder'))->get(['id', 'parent_id']);
        $byParent = [];
        foreach ($all as $f) {
            $byParent[$f->parent_id ?? 0][] = $f->id;
        }

        $stack = [$startId];
        $seen = [$startId => true];
        $out = [$startId];
        while ($stack) {
            $pid = array_pop($stack);
            $kids = $byParent[$pid] ?? [];
            foreach ($kids as $k) {
                if (! isset($seen[$k])) {
                    $seen[$k] = true;
                    $out[] = $k;
                    $stack[] = $k;
                }
            }
        }

        return $out;
    }

    protected function likePattern(string $q): string
    {
        $q = trim($q);
        if ($q === '') {
            return '%';
        }
        $q = str_replace(['%', '_'], ['\\%', '\\_'], $q);
        $q = str_replace(['*', '?'], ['%', '_'], $q);
        if (! str_contains($q, '%') && ! str_contains($q, '_')) {
            $q = "%{$q}%";
        }

        return strtolower($q);
    }

    public function getFilteredItemsProperty()
    {
        $base = $this->scopeAccessible(Bookmark::query());

        // scope: current folder vs include children
        if ($this->includeChildren) {
            if ($this->currentFolderId === null) {
                $desc = $this->scopeAccessible(Bookmark::where('type', 'folder'))->pluck('id')->all();
                $base->where(function ($q) use ($desc) {
                    $q->whereNull('parent_id');
                    if (! empty($desc)) {
                        $q->orWhereIn('parent_id', $desc);
                    }
                });
            } else {
                $ids = $this->descendantFolderIds($this->currentFolderId);
                if (empty($ids)) {
                    $base->where('parent_id', $this->currentFolderId);
                } else {
                    $base->whereIn('parent_id', $ids);
                }
            }
        } else {
            $base->where('parent_id', $this->currentFolderId);
        }

        // search
        $query = trim($this->search);
        if ($query !== '') {
            $like = $this->likePattern($query);
            $base->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(name) LIKE ? ESCAPE "\\"', [$like])
                    ->orWhereRaw('LOWER(url)  LIKE ? ESCAPE "\\"', [$like]);
            });
        }

        // folders first + true alphabetical (case-insensitive)
        $dir = in_array($this->nameSortDir, ['asc', 'desc'], true) ? strtoupper($this->nameSortDir) : 'ASC';
        $base->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
            ->orderByRaw("LOWER(name) COLLATE NOCASE {$dir}");

        return $base->get();
    }

    public function getAllFoldersProperty()
    {
        return $this->scopeAccessible(
            Bookmark::where('type', 'folder')->orderByRaw('LOWER(name) COLLATE NOCASE ASC')
        )->get();
    }

    /* ===== Create / Edit / Delete ===== */

    public function updatedNewBookmarkType(): void
    {
        // Always default to hero for both types
        if ($this->newBookmarkType === 'folder') {
            $this->iconChoice = 'hero';
            $this->iconName = 'folder';
            $this->newBookmarkIcon = null;
        } else {
            $this->iconChoice = 'hero';
            $this->iconName = 'link'; // default hero icon for links
        }
    }

    public function createBookmark(): void
    {
        if ($this->newBookmarkType === 'link' && is_null($this->newBookmarkParentId ?? $this->currentFolderId)) {
            $this->addError('newBookmarkParentId', 'Bitte einen Ordner wählen.');

            return;
        }

        $rules = [
            'newBookmarkName' => 'required|string|max:255',
            'newBookmarkType' => 'required|in:link,folder',
        ];
        if ($this->newBookmarkType === 'link') {
            $rules['newBookmarkUrl'] = 'required|url';
            if ($this->iconChoice === 'hero') {
                $rules['iconName'] = 'required|string|max:64';
            }
        } else {
            if ($this->iconChoice === 'hero' && $this->iconName) {
                $rules['iconName'] = 'nullable|string|max:64';
            }
        }
        $this->validate($rules, $this->messages);

        $uid = $this->currentUserGuid();
        $isGlobal = $this->isAdmin() && $this->makeGlobal;
        $parentId = $this->newBookmarkParentId ?? $this->currentFolderId;

        $iconName = null;
        $faviconUrl = null;

        if ($this->newBookmarkType === 'link') {
            if ($this->iconChoice === 'hero') {
                $iconName = $this->iconName ?: 'link';
            } else {
                $faviconUrl = $this->deriveFavicon($this->newBookmarkUrl);
                if ($faviconUrl === null) {
                    $faviconUrl = '__none__';
                } // cache negative result
            }
        } else {
            $iconName = $this->iconName ?: 'folder';
        }

        Bookmark::create([
            'name' => $this->newBookmarkName,
            'type' => $this->newBookmarkType,
            'url' => $this->newBookmarkType === 'link' ? $this->newBookmarkUrl : null,
            'parent_id' => $parentId,
            'icon' => null,
            'icon_name' => $iconName,
            'favicon' => $faviconUrl,
            'user_guid' => $isGlobal ? null : $uid,
            'is_global' => $isGlobal,
            'sort_order' => 0,
        ]);

        // reset create form but keep sensible defaults
        $this->reset([
            'newBookmarkName', 'newBookmarkUrl', 'newBookmarkIcon', 'newBookmarkParentId',
            'newBookmarkType', 'makeGlobal', 'showModal',
        ]);
        // re-apply default icon settings
        $this->iconChoice = 'hero';
        $this->iconName = 'link';

        Flux::toast('Lesezeichen erfolgreich erstellt.');
    }

    public function startEdit(int $id): void
    {
        $b = Bookmark::findOrFail($id);
        if (! $this->canManage($b)) {
            Flux::toast('Du darfst dieses Lesezeichen nicht bearbeiten.', variant: 'danger');

            return;
        }

        $this->editId = $b->id;
        $this->editName = $b->name;
        $this->editType = $b->type;
        $this->editUrl = $b->url ?? '';
        $this->editParentId = $b->parent_id;

        if (! empty($b->icon_name)) {
            $this->editIconChoice = 'hero';
            $this->editIconName = $b->icon_name;
        } else {
            $this->editIconChoice = 'favicon';
            $this->editIconName = null;
        }
        $this->editIconUpload = null;

        $this->showEditModal = true;
    }

    public function updateBookmark(): void
    {
        if (! $this->editId) {
            return;
        }

        $b = Bookmark::findOrFail($this->editId);
        if (! $this->canManage($b)) {
            Flux::toast('Du darfst dieses Lesezeichen nicht bearbeiten.', variant: 'danger');

            return;
        }

        if ($this->editType === 'link' && is_null($this->editParentId ?? $this->currentFolderId)) {
            $this->addError('editParentId', 'Bitte einen Ordner wählen.');

            return;
        }

        $rules = ['editName' => 'required|string|max:255'];
        if ($this->editType === 'link') {
            $rules['editUrl'] = 'required|url';
            if ($this->editIconChoice === 'hero') {
                $rules['editIconName'] = 'required|string|max:64';
            }
        } else {
            if ($this->editIconChoice === 'hero' && $this->editIconName) {
                $rules['editIconName'] = 'nullable|string|max:64';
            }
        }
        $this->validate($rules, $this->messages);

        $b->name = $this->editName;
        $b->parent_id = $this->editParentId ?? $this->currentFolderId;

        if ($this->editType === 'link') {
            $urlChanged = $b->url !== $this->editUrl;
            $b->url = $this->editUrl;

            if ($this->editIconChoice === 'hero') {
                $b->icon = null;
                $b->icon_name = $this->editIconName ?: 'link';
                $b->favicon = null; // force hero usage
            } else {
                $b->icon = null;
                $b->icon_name = null;

                // Only (re)derive if URL changed OR favicon was never set
                if ($urlChanged || $b->favicon === null) {
                    $fav = $this->deriveFavicon($this->editUrl);
                    $b->favicon = $fav ?? '__none__';
                }
            }
        } else {
            $b->url = null;
            if ($this->editIconChoice === 'hero') {
                $b->icon = null;
                $b->icon_name = $this->editIconName ?: 'folder';
                $b->favicon = null;
            } else {
                $b->icon = null;
                $b->icon_name = 'folder';
                $b->favicon = null;
            }
        }

        $b->save();

        $this->resetEdit();
        Flux::toast('Lesezeichen aktualisiert.');
    }

    protected function resetEdit(): void
    {
        $this->showEditModal = false;
        $this->editId = null;
        $this->editName = '';
        $this->editType = 'link';
        $this->editUrl = '';
        $this->editParentId = null;
        $this->editIconChoice = 'favicon';
        $this->editIconName = null;
        $this->editIconUpload = null;
    }

    public function deleteBookmark(int $id): void
    {
        $b = Bookmark::findOrFail($id);
        if (! $this->canManage($b)) {
            Flux::toast('Du darfst dieses Lesezeichen nicht löschen.', variant: 'danger');

            return;
        }
        $b->delete();
        Flux::toast('Lesezeichen gelöscht.');
    }

    /* ===== Favicon util (with negative cache) ===== */

    protected function deriveFavicon(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        try {
            $resp = Http::timeout(5)->get($url);
            if ($resp->ok()) {
                $html = $resp->body();
                if (preg_match('/<link[^>]+rel=[\"\'](?:shortcut\s+icon|icon|apple-touch-icon)[\"\'][^>]*href=[\"\']([^\"\']+)[\"\']/i', $html, $m)) {
                    $href = $m[1];
                    $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
                    $host = parse_url($url, PHP_URL_HOST);
                    $base = rtrim($scheme.'://'.$host, '/');

                    if (str_starts_with($href, '//')) {
                        return $scheme.':'.$href;
                    }
                    if (str_starts_with($href, '/')) {
                        return $base.$href;
                    }
                    if (preg_match('#^https?://#i', $href)) {
                        return $href;
                    }

                    return $base.'/'.ltrim($href, '/');
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // last-resort guess; we don't test it to avoid 404 spam
        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($url, PHP_URL_HOST);

        return $host ? ($scheme.'://'.$host.'/favicon.ico') : null;
    }

    public function render()
    {
        return view('livewire.bookmarks', [
            'filteredItems' => $this->filteredItems,
            'allFolders' => $this->allFolders,
            'isAdmin' => $this->isAdmin(),
            'userGuid' => $this->currentUserGuid(),
            'viewMode' => $this->viewMode,
            'nameSortDir' => $this->nameSortDir,
            'includeChildren' => $this->includeChildren,
        ]);
    }
}
