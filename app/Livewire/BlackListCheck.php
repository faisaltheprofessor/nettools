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

    public array $results = [];

    public ?Domain $selected = null;

    public ?string $errorMsg = null;

    public bool $hasSearched = false;

    public array $chartData = [];

    public array $acSuggestions = [];

    public int $acIndex = -1;

    public bool $acOpen = false;

    public bool $acBlock = false;

    public array $prevResults = [];

    public function mount(): void
    {
        $this->loadChartData();
    }

    public function render()
    {
        $ts = DomainCategory::query()->max('updated_from_fs_at');
        $lastSyncAt = $ts ? Carbon::parse($ts) : null;

        if (empty($this->chartData)) {
            $this->loadChartData();
        }

        return view('livewire.blacklist-check', [
            'lastSyncAt' => $lastSyncAt,
        ]);
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
        if (mb_strlen($q) < 2) {
            return;
        }

        $rows = Domain::query()
            ->select('domains.id', 'domains.host', 'domains.normalized_host', 'domain_categories.slug as category')
            ->join('domain_category_domain', 'domain_category_domain.domain_id', '=', 'domains.id')
            ->join('domain_categories', 'domain_categories.id', '=', 'domain_category_domain.domain_category_id')
            ->where('domains.normalized_host', 'like', "%{$q}%")
            ->orderByRaw('CASE WHEN INSTR(domains.normalized_host, ?) = 0 THEN 9999 ELSE INSTR(domains.normalized_host, ?) END', [$q, $q])
            ->orderBy('domains.normalized_host')
            ->limit(200)
            ->get();

        $grouped = [];
        foreach ($rows as $r) {
            $cat = $r->category ?? 'uncategorized';
            $grouped[$cat] = $grouped[$cat] ?? [];
            if (count($grouped[$cat]) < 5) {
                $grouped[$cat][] = ['id' => (int) $r->id, 'host' => (string) $r->host];
            }
        }

        $out = [];
        $total = 0;
        foreach ($grouped as $cat => $items) {
            if ($total >= 40) {
                break;
            }
            $slice = array_slice($items, 0, max(0, 40 - $total));
            if (! empty($slice)) {
                $out[] = ['category' => $cat, 'items' => $slice];
                $total += count($slice);
            }
        }

        $this->acSuggestions = $out;
        $this->acOpen = ! empty($out);
    }

    public function acMove(int $delta): void
    {
        if (! $this->acOpen) {
            return;
        }
        $flat = $this->flattenSuggestions();
        if (empty($flat)) {
            return;
        }
        $this->acIndex = ($this->acIndex + $delta + count($flat)) % count($flat);
    }

    public function acSelectCurrent(): void
    {
        if (! $this->acOpen) {
            return;
        }
        $flat = $this->flattenSuggestions();
        if (! isset($flat[$this->acIndex])) {
            return;
        }
        $this->acClickSelect((int) $flat[$this->acIndex]['id'], (string) $flat[$this->acIndex]['host']);
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

        $this->prevResults = [];
        $this->results = [];

        $q = $this->normalizeInput($this->search);
        if ($q === '') {
            return;
        }

        $candidates = Domain::with(['categories' => function ($q) {
            $q->select('domain_categories.id', 'domain_categories.slug', 'domain_categories.priority');
        }])
            ->where('normalized_host', 'like', "%{$q}%")
            ->orderBy('normalized_host')
            ->limit(300)
            ->get()
            ->all();

        if (! $candidates) {
            return;
        }

        $scored = array_map(function (Domain $d) use ($q) {
            $h = $d->normalized_host;
            $dist = levenshtein($h, $q);
            $pos = mb_strpos($h, $q);
            $starts = $pos === 0 ? 1 : 0;
            $exact = ($h === $q) ? 1 : 0;
            $score = $dist + ($pos === false ? 5 : min(3, (int) $pos)) - ($starts * 2) - ($exact * 5);

            return [$score, $d];
        }, $candidates);
        usort($scored, fn ($a, $b) => $a[0] <=> $b[0]);
        $this->results = array_map(fn ($r) => $r[1], array_slice($scored, 0, 300));
    }

    public function selectDomain(int $id): void
    {
        $this->suppressAutocomplete();
        $this->hasSearched = true;
        $this->errorMsg = null;

        $domain = Domain::with(['categories' => fn ($q) => $q->select('domain_categories.id', 'domain_categories.slug', 'domain_categories.priority')])->find($id);
        if (! $domain) {
            $this->selected = null;

            return;
        }

        $this->prevResults = $this->results;

        $this->selected = $domain;
        $this->results = [$domain];
    }

    public function backToList(): void
    {
        $this->selected = null;

        if (! empty($this->prevResults)) {
            $this->results = $this->prevResults;
            $this->prevResults = [];
        } else {
            $this->searchNow();
        }
    }

    private function suppressAutocomplete(): void
    {
        $this->acBlock = true;
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
        if ($in === '') {
            return '';
        }
        $in = preg_replace('#^\s*(https?://)#', '', $in);
        $first = explode('/', $in, 2)[0];
        if (str_contains($first, '@')) {
            $first = substr($first, strrpos($first, '@') + 1);
        }
        $host = explode(':', $first, 2)[0];
        $host = rtrim($host, '.');
        if ($host === '' || $host === null) {
            return '';
        }
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii !== false && $ascii !== null && $ascii !== '') {
                $host = $ascii;
            }
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
        $ver = Cache::get('domain_stats_version', 0);
        $key = "domain_category_counts:v{$ver}";

        $this->chartData = Cache::remember($key, 3600, function () {
            return DomainCategory::query()
                ->select('domain_categories.id', 'domain_categories.slug', 'domain_categories.priority')
                ->withCount(['domains'])
                ->orderByRaw('CASE WHEN priority=0 THEN 1 ELSE 0 END, priority ASC')
                ->get()
                ->map(fn ($c) => [
                    'slug' => $c->slug,
                    'count' => (int) $c->domains_count,
                    'priority' => (int) $c->priority,
                ])
                ->filter(fn ($r) => $r['count'] > 0)
                ->values()
                ->all();
        });
    }
}
