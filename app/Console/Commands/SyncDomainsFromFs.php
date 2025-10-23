<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\DomainCategory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SyncDomainsFromFs extends Command
{
    protected $signature = 'domains:sync {--root=public/storage/proxy-domains} {--no-prune}';
    protected $description = 'Mirror folders to categories and domains (create new, update existing, remove missing).';

    public function handle(): int
    {
        $root = base_path($this->option('root'));
        if (! File::isDirectory($root)) {
            $this->error("Root not found: $root");
            return self::FAILURE;
        }

        $now = Carbon::now();
        $rootRel = str_replace(base_path().DIRECTORY_SEPARATOR, '', $root);
        $prioMap = $this->loadPriorityMap($root);

        DB::beginTransaction();

        $domainsCreatedTotal = 0;
        $domainsOrphanDeleted = 0;
        $categoriesCreated = 0;
        $categoriesDeleted = 0;

        $seenCategoryIds = [];
        $seenHostsByCatId = [];

        try {
            foreach (File::directories($root) as $catDir) {
                $slug = basename($catDir);
                $domainFile = $catDir.DIRECTORY_SEPARATOR.'domains';

                $category = DomainCategory::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $slug,
                        'files_path' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $catDir),
                        'priority' => $prioMap[$slug] ?? 0,
                        'updated_from_fs_at' => $now,
                    ]
                );

                if ($category->wasRecentlyCreated) {
                    $categoriesCreated++;
                }

                $seenCategoryIds[] = $category->id;
                $seenHostsByCatId[$category->id] = [];

                if (! File::exists($domainFile)) {
                    continue;
                }

                foreach (File::lines($domainFile) as $raw) {
                    $raw = trim($raw);
                    if ($raw === '' || str_starts_with($raw, '#')) {
                        continue;
                    }

                    $host = $this->normalizeHostKeepingWww($raw);
                    if ($host === null) {
                        continue;
                    }

                    $normalized = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host;
                    $tld = str_contains($normalized, '.') ? Str::afterLast($normalized, '.') : null;

                    $domain = Domain::firstOrCreate(
                        ['host' => $normalized],
                        [
                            'normalized_host' => $normalized,
                            'tld' => $tld,
                            'category_id' => $category->id,
                            'first_seen_at' => $now,
                            'last_seen_at' => $now,
                        ]
                    );

                    if ($domain->wasRecentlyCreated) {
                        $domainsCreatedTotal++;
                    }

                    $domain->last_seen_at = $now;
                    $domain->save();

                    $domain->categories()->syncWithoutDetaching([$category->id]);
                    $seenHostsByCatId[$category->id][$normalized] = true;
                }
            }

            $this->assignPrimaryCategories();

            if (! $this->option('no-prune')) {
                $toDelete = DomainCategory::query()
                    ->where('files_path', 'like', rtrim($rootRel, '/').'/'.'%')
                    ->when(! empty($seenCategoryIds), fn ($q) => $q->whereNotIn('id', $seenCategoryIds))
                    ->get();

                foreach ($toDelete as $cat) {
                    $categoriesDeleted++;
                    $cat->delete();
                }

                foreach ($seenHostsByCatId as $catId => $seenHosts) {
                    $hostList = array_keys($seenHosts);

                    $detachIds = DB::table('domain_category_domain')
                        ->join('domains', 'domains.id', '=', 'domain_category_domain.domain_id')
                        ->where('domain_category_domain.domain_category_id', $catId)
                        ->when(! empty($hostList), fn ($q) => $q->whereNotIn('domains.host', $hostList))
                        ->pluck('domain_category_domain.domain_id')
                        ->all();

                    if (! empty($detachIds)) {
                        DB::table('domain_category_domain')
                            ->where('domain_category_id', $catId)
                            ->whereIn('domain_id', $detachIds)
                            ->delete();
                    }
                }

                $orphans = Domain::doesntHave('categories')->pluck('id');
                if ($orphans->count()) {
                    $domainsOrphanDeleted = (int) $orphans->count();
                    Domain::whereIn('id', $orphans)->delete();
                }
            }

            Cache::put('domain_stats_version', $now->timestamp, 86400);
            DB::commit();

            $this->info('Sync complete. Compact summary:');
            $this->line('');

            $totalDomainsDistinct = Domain::count();
            $newDomains = $domainsCreatedTotal;
            $removedDomains = $domainsOrphanDeleted;
            $totalCategories = DomainCategory::count();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['New domains added', $newDomains],
                    ['Domains removed', $removedDomains],
                    ['Total domains in DB (distinct)', $totalDomainsDistinct],
                    ['New categories created', $categoriesCreated],
                    ['Categories removed', $categoriesDeleted],
                    ['Total categories in DB', $totalCategories],
                ]
            );

            $this->line('');
            $this->info('Current categories, priorities, and domain counts:');

            $cats = DomainCategory::query()
                ->orderBy('priority')
                ->orderBy('slug')
                ->get(['id', 'slug', 'priority']);

            $domainsPerCategory = DB::table('domain_category_domain')
                ->select('domain_category_id', DB::raw('COUNT(DISTINCT domain_id) AS cnt'))
                ->groupBy('domain_category_id')
                ->pluck('cnt', 'domain_category_id');

            if ($cats->isEmpty()) {
                $this->line('  (no categories found)');
            } else {
                $this->table(
                    ['Category', 'Priority', 'Domains (distinct)'],
                    $cats->map(function ($c) use ($domainsPerCategory) {
                        return [$c->slug, $c->priority ?? 0, (int) ($domainsPerCategory[$c->id] ?? 0)];
                    })->toArray()
                );
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Sync failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Reads <root>/prio.txt and returns a map of category slugs with their priority numbers.
     * Skips empty and commented lines (#). "!" before a name is ignored.
     */
    private function loadPriorityMap(string $root): array
    {
        $file = $root.DIRECTORY_SEPARATOR.'prio.txt';
        if (! File::exists($file)) {
            return [];
        }

        $order = [];
        foreach (File::lines($file) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $line = preg_replace('/#.*/', '', $line) ?? '';
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = preg_split('/[,\s]+/', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($parts as $t) {
                $t = ltrim(trim($t), '!');
                if ($t === '') {
                    continue;
                }
                $order[] = $t;
            }
        }

        $order = array_values(array_unique($order));

        $map = [];
        foreach ($order as $i => $slug) {
            $map[$slug] = $i + 1;
        }

        return $map;
    }

    /**
     * Ensures each domain has a primary category based on priority.
     */
    private function assignPrimaryCategories(): void
    {
        Domain::with(['categories:id,priority'])
            ->get(['id', 'category_id'])
            ->each(function (Domain $d) {
                if ($d->categories->isEmpty()) {
                    if ($d->category_id !== null) {
                        $d->category_id = null;
                        $d->save();
                    }
                    return;
                }

                $best = $d->categories
                    ->sortBy(fn ($c) => ((int) ($c->priority ?? 0)) > 0 ? (int) $c->priority : PHP_INT_MAX)
                    ->first();

                if ($best && $d->category_id !== $best->id) {
                    $d->category_id = $best->id;
                    $d->save();
                }
            });
    }

    /**
     * Takes a raw string (host or URL) and normalizes it into a hostname.
     * Keeps "www" if present and rejects invalid values.
     */
    private function normalizeHostKeepingWww(string $raw): ?string
    {
        $raw = strtolower(trim($raw));
        if ($raw === '') {
            return null;
        }

        $maybe = (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) ? $raw : 'http://'.$raw;
        $parts = @parse_url($maybe);
        $host = $parts['host'] ?? null;
        if (! $host) {
            return null;
        }

        $host = rtrim($host, '.');
        if ($host === '' || (! str_contains($host, '.') && $host !== 'localhost')) {
            return null;
        }

        return $host;
    }
}

/*
================================================================================
prio.txt — examples
Each space or comma separates a category name. Leading "!" is ignored. "#" starts a comment.
================================================================================

whitelist blacklist ddi webmailer

Parsed priority map:
[
  'whitelist' => 1,
  'blacklist' => 2,
  'ddi'       => 3,
  'webmailer' => 4,
]

Also valid:
whitelist, blacklist, ddi, webmailer
whitelist blacklist, ddi webmailer
*/
