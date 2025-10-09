<?php

namespace App\Console\Commands;

use App\Models\DomainCategory;
use App\Models\Domain;
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
        if (!File::isDirectory($root)) {
            $this->error("Root not found: $root");
            return self::FAILURE;
        }

        $now     = Carbon::now();
        $rootRel = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $root);
        $prioMap = $this->loadPriorityMap($root);

        DB::beginTransaction();
        try {
            $seenCategoryIds  = [];
            $seenHostsByCatId = [];

            $dirPaths = File::directories($root);
            foreach ($dirPaths as $catDir) {
                $slug       = basename($catDir);
                $domainFile = $catDir . DIRECTORY_SEPARATOR . 'domains';

                $category = DomainCategory::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name'       => $slug,
                        'files_path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $catDir),
                        'priority'   => $prioMap[$slug] ?? 0,
                        'updated_from_fs_at' => $now,
                    ]
                );

                $seenCategoryIds[]                 = $category->id;
                $seenHostsByCatId[$category->id]   = [];

                if (!File::exists($domainFile)) continue;

                foreach (File::lines($domainFile) as $raw) {
                    $raw = trim($raw);
                    if ($raw === '' || str_starts_with($raw, '#')) continue;

                    $host = $this->normalizeHostKeepingWww($raw);
                    if ($host === null) continue;

                    $normalized = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host;
                    $tld        = str_contains($normalized, '.') ? Str::afterLast($normalized, '.') : null;

                    $domain = Domain::firstOrCreate(
                        ['host' => $normalized],
                        [
                            'normalized_host' => $normalized,
                            'tld'             => $tld,
                            'category_id'     => $category->id,
                            'first_seen_at'   => $now,
                            'last_seen_at'    => $now,
                        ]
                    );

                    if ($domain->first_seen_at === null) $domain->first_seen_at = $now;
                    $domain->last_seen_at = $now;
                    if ($domain->normalized_host !== $normalized) $domain->normalized_host = $normalized;
                    if ($domain->tld !== $tld) $domain->tld = $tld;
                    $domain->save();

                    $domain->categories()->syncWithoutDetaching([$category->id]);
                    $seenHostsByCatId[$category->id][$normalized] = true;
                }
            }

            $this->assignPrimaryCategories();

            if (!$this->option('no-prune')) {
                $toDelete = DomainCategory::query()
                    ->where('files_path', 'like', rtrim($rootRel, '/').'/'.'%')
                    ->when(!empty($seenCategoryIds), fn($q) => $q->whereNotIn('id', $seenCategoryIds))
                    ->get();

                foreach ($toDelete as $cat) $cat->delete();

                foreach ($seenHostsByCatId as $catId => $seenHosts) {
                    $hostList = array_keys($seenHosts);

                    $detachIds = DB::table('domain_category_domain')
                        ->join('domains', 'domains.id', '=', 'domain_category_domain.domain_id')
                        ->where('domain_category_domain.domain_category_id', $catId)
                        ->when(!empty($hostList), fn($q) => $q->whereNotIn('domains.host', $hostList))
                        ->pluck('domain_category_domain.domain_id')
                        ->all();

                    if (!empty($detachIds)) {
                        DB::table('domain_category_domain')
                            ->where('domain_category_id', $catId)
                            ->whereIn('domain_id', $detachIds)
                            ->delete();
                    }
                }

                $orphans = Domain::doesntHave('categories')->pluck('id');
                if ($orphans->count()) Domain::whereIn('id', $orphans)->delete();
            }

            Cache::put('domain_stats_version', $now->timestamp, 86400);
            DB::commit();
            $this->info('Sync complete. Stats cache version bumped.');
            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function loadPriorityMap(string $root): array
    {
        $file = $root . DIRECTORY_SEPARATOR . 'prio.txt';
        if (!File::exists($file)) return [];

        $content = trim(File::get($file));
        if ($content === '') return [];

        $tokens = preg_split('/\s+/', $content);
        $order  = [];
        foreach ($tokens as $t) {
            $t = trim($t);
            if ($t === '' || strtolower($t) === 'pass') continue;
            $t    = ltrim($t, '!');
            $slug = Str::slug($t);
            if ($slug !== '') $order[] = $slug;
        }
        $order = array_values(array_unique($order));

        $map = [];
        foreach ($order as $i => $slug) $map[$slug] = $i + 1;
        return $map;
    }

    private function assignPrimaryCategories(): void
    {
        Domain::with(['categories:id,priority'])
            ->get(['id','category_id'])
            ->each(function (Domain $d) {
                if ($d->categories->isEmpty()) {
                    if ($d->category_id !== null) { $d->category_id = null; $d->save(); }
                    return;
                }
                $best = $d->categories
                    ->sortBy(fn($c) => ((int)($c->priority ?? 0)) > 0 ? (int)$c->priority : PHP_INT_MAX)
                    ->first();
                if ($best && $d->category_id !== $best->id) {
                    $d->category_id = $best->id;
                    $d->save();
                }
            });
    }

    private function normalizeHostKeepingWww(string $raw): ?string
    {
        $raw = strtolower(trim($raw));
        if ($raw === '') return null;

        $maybe = (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) ? $raw : 'http://' . $raw;
        $parts = @parse_url($maybe);
        $host  = $parts['host'] ?? null;
        if (!$host) return null;

        $host = rtrim($host, '.');
        if ($host === '' || (!str_contains($host, '.') && $host !== 'localhost')) return null;

        return $host;
    }
}
