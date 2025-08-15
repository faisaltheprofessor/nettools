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
    ];
    public string $search = '';
    public ?int $currentFolderId = null;
    public array $breadcrumbs = [];
    public bool $globalSearch = false;

    // Create modal state
    public bool $showModal = false;
    public string $newBookmarkName = '';
    public string $newBookmarkUrl  = '';
    public ?int  $newBookmarkParentId = null;
    public string $newBookmarkType = 'link'; // link|folder

    // Icon selection for create
    public string $iconChoice = 'favicon';   // favicon|hero|upload
    public ?string $iconName  = null;        // heroicon name
    public $newBookmarkIcon   = null;        // uploaded PNG

    // Admin/global toggle
    public bool $makeGlobal = false;

    // ---- Edit modal state ----
    public bool $showEditModal = false;
    public ?int $editId = null;
    public string $editName = '';
    public string $editType = 'link';
    public string $editUrl = '';
    public ?int $editParentId = null;
    public string $editIconChoice = 'favicon';
    public ?string $editIconName = null;
    public $editIconUpload = null;

    // Heroicon options (extend as needed)
    public array $heroiconOptions = [
        'globe-alt','link','bookmark','star','bolt','rocket-launch',
        'code-bracket','cpu-chip','cube','presentation-chart-line',
        'document-text','photo','film','rss',
        'folder','folder-open','cloud','server','shield-check',
    ];

    protected array $messages = [
        'newBookmarkName.required' => 'Dieses Feld ist erforderlich.',
        'newBookmarkUrl.required'  => 'Dieses Feld ist erforderlich.',
        'newBookmarkUrl.url'       => 'Bitte eine gültige URL eingeben.',
        'newBookmarkType.required' => 'Dieses Feld ist erforderlich.',
        'newBookmarkType.in'       => 'Ungültiger Typ ausgewählt.',
        'newBookmarkIcon.image'    => 'Die Datei muss ein Bild sein.',
        'newBookmarkIcon.file'     => 'Bitte eine gültige Datei hochladen.',
        'newBookmarkIcon.max'      => 'Die Datei darf nicht größer als 2048 KB sein.',
        'iconName.required'        => 'Bitte ein Symbol auswählen.',

        'editName.required'        => 'Dieses Feld ist erforderlich.',
        'editUrl.required'         => 'Dieses Feld ist erforderlich.',
        'editUrl.url'              => 'Bitte eine gültige URL eingeben.',
        'editIconUpload.image'     => 'Die Datei muss ein Bild sein.',
        'editIconUpload.file'      => 'Bitte eine gültige Datei hochladen.',
        'editIconUpload.max'       => 'Die Datei darf nicht größer als 2048 KB sein.',
    ];

    public function mount(): void
    {
        $this->setBreadcrumbs();
    }

    /* ========== Admin helpers ========== */

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

    /* ========== Breadcrumbs ========== */

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
        $this->breadcrumbs     = array_slice($this->breadcrumbs, 0, $index + 1);
        $this->currentFolderId = $this->breadcrumbs[$index]['id'] ?? null;
        $this->setBreadcrumbs();
    }

    /* ========== Query helpers ========== */

    protected function scopeAccessible($q)
    {
        return $q->accessible($this->currentUserGuid());
    }

    public function getFilteredItemsProperty()
    {
        $query = trim($this->search);

        $baseQuery = $this->scopeAccessible(Bookmark::query());

        if (!$this->globalSearch) {
            $baseQuery->where('parent_id', $this->currentFolderId);
        }

        if ($query !== '') {
            $baseQuery->where(function ($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($query) . '%'])
                    ->orWhereRaw('LOWER(url)  LIKE ?', ['%' . strtolower($query) . '%']);
            });
        } elseif ($this->globalSearch) {
            $baseQuery->whereNull('parent_id');
        }

        return $baseQuery
            ->orderByRaw("CASE WHEN type = 'folder' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
    }

    public function getAllFoldersProperty()
    {
        return $this->scopeAccessible(
            Bookmark::where('type', 'folder')->orderBy('name')
        )->get();
    }

    /* ========== Create flow ========== */

    public function updatedNewBookmarkType(): void
    {
        if ($this->newBookmarkType === 'folder') {
            $this->iconChoice = 'hero';
            $this->iconName   = 'folder';
            $this->newBookmarkIcon = null;
        } else {
            $this->iconChoice = 'favicon';
            $this->iconName   = null;
        }
    }

    public function createBookmark(): void
    {
        // Block links at root (strict null check)
        if (
            $this->newBookmarkType === 'link'
            && is_null($this->newBookmarkParentId ?? $this->currentFolderId)
        ) {
            // No toast; just a field error (tooltip explains the rule)
            $this->addError('newBookmarkParentId', 'Bitte einen Ordner wählen.');
            return;
        }

        // Validation
        $rules = [
            'newBookmarkName' => 'required|string|max:255',
            'newBookmarkType' => 'required|in:link,folder',
        ];

        if ($this->newBookmarkType === 'link') {
            $rules['newBookmarkUrl'] = 'required|url';

            if ($this->iconChoice === 'upload') {
                $rules['newBookmarkIcon'] = 'nullable|file|image|max:2048';
            } elseif ($this->iconChoice === 'hero') {
                $rules['iconName'] = 'required|string|max:64';
            }
        } else {
            if ($this->iconChoice === 'hero' && $this->iconName) {
                $rules['iconName'] = 'nullable|string|max:64';
            }
        }

        $this->validate($rules, $this->messages);

        $uid      = $this->currentUserGuid();
        $isGlobal = $this->isAdmin() && $this->makeGlobal;
        $parentId = $this->newBookmarkParentId ?? $this->currentFolderId;

        // Icons
        $iconPath   = null;  // uploaded path
        $iconName   = null;  // heroicon
        $faviconUrl = null;  // derived favicon

        if ($this->newBookmarkType === 'link') {
            if ($this->iconChoice === 'upload' && $this->newBookmarkIcon) {
                $iconPath = $this->newBookmarkIcon->store('icons', 'public');
            } elseif ($this->iconChoice === 'hero') {
                $iconName = $this->iconName;
            } else {
                $faviconUrl = $this->deriveFavicon($this->newBookmarkUrl);
            }
        } else {
            $iconName = $this->iconName ?: 'folder';
        }

        Bookmark::create([
            'name'      => $this->newBookmarkName,
            'type'      => $this->newBookmarkType,
            'url'       => $this->newBookmarkType === 'link' ? $this->newBookmarkUrl : null,
            'parent_id' => $parentId,
            'icon'      => $iconPath,
            'icon_name' => $iconName,
            'favicon'   => $faviconUrl,
            'user_guid' => $isGlobal ? null : $uid,
            'is_global' => $isGlobal,
        ]);

        $this->reset([
            'newBookmarkName',
            'newBookmarkUrl',
            'newBookmarkIcon',
            'newBookmarkParentId',
            'newBookmarkType',
            'iconChoice',
            'iconName',
            'makeGlobal',
            'showModal',
        ]);

        $this->showModal = false;
        Flux::toast('Lesezeichen erfolgreich erstellt.');
    }

    /* ========== Edit flow ========== */

    public function startEdit(int $id): void
    {
        $b = Bookmark::findOrFail($id);
        if (!$this->canManage($b)) {
            Flux::toast('Du darfst dieses Lesezeichen nicht bearbeiten.', variant: 'danger');
            return;
        }

        $this->editId       = $b->id;
        $this->editName     = $b->name;
        $this->editType     = $b->type;
        $this->editUrl      = $b->url ?? '';
        $this->editParentId = $b->parent_id;

        // Decide icon choice from stored fields
        if (!empty($b->icon)) {
            $this->editIconChoice = 'upload';
            $this->editIconName   = null;
        } elseif (!empty($b->icon_name)) {
            $this->editIconChoice = 'hero';
            $this->editIconName   = $b->icon_name;
        } else {
            $this->editIconChoice = 'favicon';
            $this->editIconName   = null;
        }
        $this->editIconUpload = null;

        $this->showEditModal = true;
    }

    public function updateBookmark(): void
    {
        if (!$this->editId) return;

        $b = Bookmark::findOrFail($this->editId);
        if (!$this->canManage($b)) {
            Flux::toast('Du darfst dieses Lesezeichen nicht bearbeiten.', variant: 'danger');
            return;
        }

        // Block links at root on edit
        if ($this->editType === 'link' && is_null($this->editParentId ?? $this->currentFolderId)) {
            $this->addError('editParentId', 'Bitte einen Ordner wählen.');
            return;
        }

        // Validate
        $rules = [
            'editName' => 'required|string|max:255',
        ];
        if ($this->editType === 'link') {
            $rules['editUrl'] = 'required|url';
            if ($this->editIconChoice === 'upload') {
                $rules['editIconUpload'] = 'nullable|file|image|max:2048';
            } elseif ($this->editIconChoice === 'hero') {
                $rules['editIconName'] = 'required|string|max:64';
            }
        } else {
            if ($this->editIconChoice === 'hero' && $this->editIconName) {
                $rules['editIconName'] = 'nullable|string|max:64';
            }
        }
        $this->validate($rules, $this->messages);

        // Apply changes
        $b->name      = $this->editName;
        $b->parent_id = $this->editParentId ?? $this->currentFolderId;

        if ($this->editType === 'link') {
            $b->url = $this->editUrl;

            if ($this->editIconChoice === 'upload' && $this->editIconUpload) {
                $b->icon      = $this->editIconUpload->store('icons', 'public');
                $b->icon_name = null;
                // keep favicon as-is
            } elseif ($this->editIconChoice === 'hero') {
                $b->icon      = null;
                $b->icon_name = $this->editIconName;
            } else { // favicon
                $b->icon      = null;
                $b->icon_name = null;
                $b->favicon   = $this->deriveFavicon($this->editUrl);
            }
        } else {
            // folder
            $b->url = null;
            if ($this->editIconChoice === 'hero') {
                $b->icon      = null;
                $b->icon_name = $this->editIconName ?: 'folder';
            } else {
                // for folders, default to hero folder icon
                $b->icon      = null;
                $b->icon_name = 'folder';
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
        $this->editIconName   = null;
        $this->editIconUpload = null;
    }

    /* ========== Delete ========== */

    public function deleteBookmark(int $id): void
    {
        $b = Bookmark::findOrFail($id);
        if (!$this->canManage($b)) {
            Flux::toast('Du darfst dieses Lesezeichen nicht löschen.', variant: 'danger');
            return;
        }

        $b->delete();
        Flux::toast('Lesezeichen gelöscht.');
    }

    /* ========== Favicon util ========== */

    protected function deriveFavicon(?string $url): ?string
    {
        if (!$url) return null;

        try {
            $resp = Http::timeout(5)->get($url);
            if ($resp->ok()) {
                $html = $resp->body();
                if (preg_match('/<link[^>]+rel=["\'](?:shortcut\s+icon|icon|apple-touch-icon)["\'][^>]*href=["\']([^"\']+)["\']/i', $html, $m)) {
                    $href   = $m[1];
                    $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
                    $host   = parse_url($url, PHP_URL_HOST);
                    $base   = rtrim($scheme . '://' . $host, '/');

                    if (str_starts_with($href, '//'))  return $scheme . ':' . $href;
                    if (str_starts_with($href, '/'))   return $base . $href;
                    if (preg_match('#^https?://#i', $href)) return $href;
                    return $base . '/' . ltrim($href, '/');
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
        $host   = parse_url($url, PHP_URL_HOST);
        return $host ? ($scheme . '://' . $host . '/favicon.ico') : null;
    }

    public function render()
    {
        return view('livewire.bookmarks', [
            'filteredItems' => $this->filteredItems,
            'allFolders'    => $this->allFolders,
            'isAdmin'       => $this->isAdmin(),
            'userGuid'      => $this->currentUserGuid(),
        ]);
    }
}
