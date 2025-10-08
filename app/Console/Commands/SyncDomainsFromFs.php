<?php

namespace App\Console\Commands;

use App\Models\DomainCategory;
use App\Models\Domain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SyncDomainsFromFs extends Command
{
    protected $signature = 'domains:sync {--root=public/storage/proxy-domains} {--no-prune}';
    protected $description = 'Sync domains from folder structure into the database.';

    public function handle(): int
    {
        $root = base_path($this->option('root'));
        if (!File::isDirectory($root)) {
            $this->error("Root not found: $root");
            return self::FAILURE;
        }

        $now = Carbon::now();
        $seenHosts = [];
        $seenCategorySlugs = [];
        $rootRel = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $root);

        DB::beginTransaction();

        try {
            foreach (File::directories($root) as $catDir) {
                $slug = basename($catDir);
                $seenCategorySlugs[$slug] = true;

                $name = Str::headline($slug);
                $domainFile = $catDir . DIRECTORY_SEPARATOR . 'domains';

                $category = DomainCategory::query()->updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'files_path' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $catDir)]
                );

                if (!File::exists($domainFile)) {
                    $this->warn("No 'domains' file in $catDir");
                    $category->updated_from_fs_at = $now;
                    $category->save();
                    continue;
                }

                foreach (File::lines($domainFile) as $raw) {
                    $raw = trim($raw);
                    if ($raw === '' || str_starts_with($raw, '#')) continue;

                    $host = $this->normalizeHost($raw);
                    if ($host === null) continue;

                    $normalized = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host;
                    $tld = str_contains($normalized, '.') ? Str::afterLast($normalized, '.') : null;

                    $domain = Domain::query()->updateOrCreate(
                        ['host' => $normalized],
                        [
                            'normalized_host' => $normalized,
                            'tld'            => $tld,
                            'category_id'    => $category->id,
                            'last_seen_at'   => $now,
                        ]
                    );

                    if (!$domain->first_seen_at) {
                        $domain->first_seen_at = $now;
                        $domain->save();
                    }

                    $seenHosts[$normalized] = true;
                }

                $category->updated_from_fs_at = $now;
                $category->save();
            }

            if (!$this->option('no-prune')) {
                $rootCategoryIds = DomainCategory::query()
                    ->where('files_path', 'like', $rootRel . '/%')
                    ->pluck('id');

                if ($rootCategoryIds->isNotEmpty()) {
                    $deletedDomains = Domain::query()
                        ->whereIn('category_id', $rootCategoryIds)
                        ->when(!empty($seenHosts), function ($q) use ($seenHosts) {
                            $q->whereNotIn('host', array_keys($seenHosts));
                        })
                        ->delete();

                    if ($deletedDomains) {
                        $this->info("Pruned $deletedDomains domain(s) not present in filesystem.");
                    }
                }

                $deletedCats = DomainCategory::query()
                    ->where('files_path', 'like', $rootRel . '/%')
                    ->whereNotIn('slug', array_keys($seenCategorySlugs))
                    ->get();

                foreach ($deletedCats as $cat) {
                    $cat->delete();
                }
                if ($deletedCats->count()) {
                    $this->info("Removed {$deletedCats->count()} stale categor(ies) not present in filesystem.");
                }
            }

            DB::commit();

            // refresh cached stats AFTER successful commit
            $this->refreshStatsCache();

            $this->info('Sync complete (mirrored).');
            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function normalizeHost(string $raw): ?string
    {
        $raw = strtolower($raw);
        $maybe = $raw;
        if (!str_starts_with($maybe, 'http://') && !str_starts_with($maybe, 'https://')) {
            $maybe = 'http://' . $maybe;
        }

        $parts = @parse_url($maybe);
        $host = $parts['host'] ?? null;
        if (!$host) return null;

        $host = preg_replace('/^www\./', '', $host);
        $host = rtrim($host, '.');

        if ($host === '' || (!str_contains($host, '.') && $host !== 'localhost')) {
            return null;
        }

        return $host;
    }

    private function refreshStatsCache(): void
    {
        // clear old cache
        Cache::forget('domain_category_counts:v1');

        // recompute counts
        $rows = DomainCategory::query()
            ->withCount('domains')
            ->orderByDesc('domains_count')
            ->get(['id','name'])
            ->map(fn($c) => ['category' => $c->name, 'count' => (int)$c->domains_count])
            ->all();

        // Top 12 + "Others"
        $top = array_slice($rows, 0, 12);
        $rest = array_slice($rows, 12);
        if (!empty($rest)) {
            $others = array_sum(array_column($rest, 'count'));
            if ($others > 0) {
                $top[] = ['category' => 'Others', 'count' => $others];
            }
        }

        // cache for 1 hour
        Cache::put('domain_category_counts:v1', $top, now()->addHour());
    }
}
