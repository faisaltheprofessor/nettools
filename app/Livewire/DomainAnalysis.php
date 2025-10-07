<?php

namespace App\Livewire;

use App\Models\Domain;
use App\Models\DomainCategory;
use Illuminate\Support\Carbon;
use Livewire\Component;

class DomainAnalysis extends Component
{
    public string $search = '';
    public array $results = [];
    public ?Domain $selected = null;
    public ?string $errorMsg = null;
    public bool $hasSearched = false;

    public function searchNow(): void
    {
        $this->hasSearched = true;
        $this->errorMsg = null;
        $this->selected = null;
        $this->results = [];

        $raw = trim($this->search);
        if ($raw === '') return;

        [$host] = $this->normalizeHostFromInput($raw);
        $q = mb_strtolower($host ?: $raw);

        $candidates = Domain::with('category')
            ->where('normalized_host', 'like', "%{$q}%")
            ->orWhere('normalized_host', 'like', '%' . ltrim($q, 'w.') . '%')
            ->orderBy('normalized_host')
            ->limit(300)
            ->get()
            ->all();

        if (!$candidates) return;

        $scored = array_map(function (Domain $d) use ($q) {
            $h = $d->normalized_host;
            $dist = levenshtein($h, $q);
            $pos = mb_strpos($h, $q);
            $starts = $pos === 0 ? 1 : 0;
            $exact = ($h === $q) ? 1 : 0;
            $score = $dist + ($pos === false ? 5 : min(3, (int)$pos)) - ($starts * 2) - ($exact * 5);
            return [$score, $d];
        }, $candidates);

        usort($scored, fn($a, $b) => $a[0] <=> $b[0]);
        $this->results = array_map(fn($row) => $row[1], array_slice($scored, 0, 200));

        if (count($this->results) === 1) {
            $this->selected = $this->results[0];
        }
    }

    public function selectDomain(int $id): void
    {
        $this->hasSearched = true;
        $this->errorMsg = null;

        $domain = Domain::with('category')->find($id);
        if (!$domain) {
            $this->selected = null;
            return;
        }

        $this->selected = $domain;
        $this->results = [$domain];
    }

    public function render()
    {
        $ts = DomainCategory::query()->max('updated_from_fs_at');
        $lastSyncAt = $ts ? Carbon::parse($ts) : null;

        return view('livewire.domain-analysis', [
            'lastSyncAt' => $lastSyncAt,
        ]);
    }

    private function normalizeHostFromInput(string $input): array
    {
        $in = trim(mb_strtolower($input));
        $probe = (str_starts_with($in, 'http://') || str_starts_with($in, 'https://')) ? $in : 'http://' . $in;
        $parts = @parse_url($probe);
        $host = $parts['host'] ?? null;
        if ($host) {
            $host = preg_replace('/^www\./', '', $host);
            $host = rtrim($host, '.');
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            $host = $ascii ?: $host;
        }
        return [$host];
    }
}
