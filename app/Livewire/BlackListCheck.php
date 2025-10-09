<?php

namespace App\Livewire;

use App\Models\Domain;
use App\Models\DomainCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class BlacklistCheck extends Component
{
    public string $search = '';
    public array  $results = [];
    public ?Domain $selected = null;
    public ?string $errorMsg = null;
    public bool $hasSearched = false;

    public array $chartData = [];

    public array $acSuggestions = [];
    public int $acIndex = -1;
    public bool $acOpen = false;
    public bool $acBlock = false; // suppress one autocomplete cycle after Enter

    public function mount(): void
    {
        $this->loadChartData();
    }

    public function render()
    {
        $ts = DomainCategory::query()->max('updated_from_fs_at');
        $lastSyncAt = $ts ? Carbon::parse($ts) : null;

        if (empty($this->chartData)) $this->loadChartData();

        return view('livewire.blacklist-check', ['lastSyncAt' => $lastSyncAt]);
    }

    public function updatedSearch(): void
    {
        if ($this->acBlock) {
            $this->acBlock = false;
            $this->closeAutocomplete();
            return;
        }

        $q = $this->normalizeInput($this->search);

        $this->acIndex = -1;
        $this->acSuggestions = [];
        $this->acOpen = false;

        if (mb_strlen($q) < 2) return;

        $perCat = 5;
        $rows = Domain::query()
            ->select('domains.id','domains.host','domains.normalized_host','domain_categories.name as category')
            ->join('domain_categories','domain_categories.id','=','domains.category_id')
            ->where('domains.normalized_host','like',"%{$q}%")
            ->orderByRaw("CASE WHEN INSTR(domains.normalized_host, ?) = 0 THEN 9999 ELSE INSTR(domains.normalized_host, ?) END", [$q,$q])
            ->orderBy('domains.normalized_host')
            ->limit(200)
            ->get();

        $grouped = [];
        foreach ($rows as $r) {
            $cat = $r->category ?? 'Uncategorized';
            $grouped[$cat] = $grouped[$cat] ?? [];
            if (count($grouped[$cat]) < $perCat) {
                $grouped[$cat][] = ['id' => (int)$r->id, 'host' => (string)$r->host];
            }
        }

        $out = [];
        $total = 0;
        foreach ($grouped as $cat => $items) {
            if ($total >= 40) break;
            $slice = array_slice($items, 0, max(0, 40 - $total));
            if (!empty($slice)) {
                $out[] = ['category' => $cat, 'items' => $slice];
                $total += count($slice);
            }
        }

        $this->acSuggestions = $out;
        $this->acOpen = !empty($out);
    }

    public function acMove(int $delta): void
    {
        if (!$this->acOpen) return;
        $flat = $this->flattenSuggestions();
        if (empty($flat)) return;
        $this->acIndex = ($this->acIndex + $delta + count($flat)) % count($flat);
    }

    public function acSelectCurrent(): void
    {
        if (!$this->acOpen) return;
        $flat = $this->flattenSuggestions();
        if (!isset($flat[$this->acIndex])) return;
        $this->acClickSelect((int)$flat[$this->acIndex]['id'], (string)$flat[$this->acIndex]['host']);
    }

    public function acClickSelect(int $id, string $host): void
    {
        $this->selectDomain($id);
        $this->search = $host;
        $this->closeAutocomplete();
    }

    public function searchNow(): void
    {
        $this->suppressAutocomplete();
        $this->hasSearched = true;
        $this->errorMsg = null;
        $this->selected = null;
        $this->results = [];

        $q = $this->normalizeInput($this->search);
        if ($q === '') return;

        $candidates = Domain::with('category')
            ->where('normalized_host','like',"%{$q}%")
            ->orderBy('normalized_host')
            ->limit(300)
            ->get()
            ->all();

        if (!$candidates) return;

        $scored = array_map(function (Domain $d) use ($q) {
            $h = $d->normalized_host;
            $dist  = levenshtein($h, $q);
            $pos   = mb_strpos($h, $q);
            $starts= $pos === 0 ? 1 : 0;
            $exact = ($h === $q) ? 1 : 0;
            $score = $dist + ($pos === false ? 5 : min(3, (int)$pos)) - ($starts * 2) - ($exact * 5);
            return [$score, $d];
        }, $candidates);

        usort($scored, fn($a,$b) => $a[0] <=> $b[0]);
        $this->results = array_map(fn($row) => $row[1], array_slice($scored, 0, 300));
    }

    public function selectDomain(int $id): void
    {
        $this->suppressAutocomplete();
        $this->hasSearched = true;
        $this->errorMsg = null;

        $domain = Domain::with('category')->find($id);
        if (!$domain) { $this->selected = null; return; }

        $this->selected = $domain;
        $this->results  = [$domain];
    }

    private function suppressAutocomplete(): void
    {
        $this->acBlock = true; // skip the very next updatedSearch tick
        $this->closeAutocomplete();
    }

    private function closeAutocomplete(): void
    {
        $this->acOpen = false;
        $this->acIndex = -1;
        $this->acSuggestions = [];
    }

    private function normalizeInput(string $input): string
    {
        $in = trim(mb_strtolower($input));
        if ($in === '') return '';
        $in = preg_replace('#^\s*(https?://)#', '', $in);
        $first = explode('/', $in, 2)[0];
        if (str_contains($first, '@')) $first = substr($first, strrpos($first, '@') + 1);
        $host = explode(':', $first, 2)[0];
        $host = preg_replace('/^www\./', '', $host);
        $host = rtrim($host, '.');
        if ($host === '' || $host === null) return '';
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii !== false && $ascii !== null && $ascii !== '') $host = $ascii;
        }
        return $host;
    }

    private function flattenSuggestions(): array
    {
        $flat = [];
        foreach ($this->acSuggestions as $group) {
            foreach ($group['items'] as $it) {
                $flat[] = $it;
            }
        }
        return $flat;
    }

    private function loadChartData(): void
    {
        $this->chartData = Cache::remember('domain_category_counts:v1', 3600, function () {
            $rows = DomainCategory::query()
                ->withCount('domains')
                ->orderByDesc('domains_count')
                ->get(['id','name'])
                ->map(fn($c) => ['category' => $c->name, 'count' => (int)$c->domains_count])
                ->all();

            $top  = array_slice($rows, 0, 12);
            $rest = array_slice($rows, 12);
            if (!empty($rest)) {
                $others = array_sum(array_column($rest, 'count'));
                if ($others > 0) $top[] = ['category' => 'Others', 'count' => $others];
            }
            return $top;
        });
    }
}
