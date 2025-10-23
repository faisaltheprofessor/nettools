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

/**
 * Class SyncDomainsFromFs
 *
 * Mirrors a folder structure to domain categories and domain records.
 *
 * Folder layout:
 *   <root>/
 *     whitelist/
 *       domains          (newline-separated list of hosts, "#" for comments)
 *     blacklist/
 *       domains
 *     ...
 *   <root>/prio.txt      (optional; defines priority order of category slugs)
 *
 * What it does:
 *  - Ensures a DomainCategory exists for each folder in <root>.
 *  - Reads "<folder>/domains" files and creates/updates Domain rows.
 *  - Maintains many-to-many links domain <-> domain_category.
 *  - Computes/assigns the "primary" category on Domain based on priorities.
 *  - Optionally prunes categories and links that no longer exist on disk.
 *  - Emits a short, tabular summary at the end.
 */
class SyncDomainsFromFs extends Command
{
    /** @var string CLI signature with options */
    protected $signature = 'domains:sync {--root=public/storage/proxy-domains} {--no-prune}';

    /** @var string CLI description */
    protected $description = 'Mirror folders to categories and domains (create new, update existing, remove missing).';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $root = base_path($this->option('root'));
        if (! File::isDirectory($root)) {
            $this->error("Root not found: $root");
            return self::FAILURE;
        }

        $now = Carbon::now();
        $rootRel = str_replace(base_path().DIRECTORY_SEPARATOR, '', $root);

        // Read optional priority map from prio.txt (slug => 1..N)
        $prioMap = $this->loadPriorityMap($root);

        DB::beginTransaction();

        // ---- Aggregated stats for a concise summary table ----
        $statsByCat = [];              // catId => [slug, prio, seen, created, updated, detached]
        $categoriesCreated = 0;
        $categoriesDeleted = 0;
        $domainsCreatedTotal = 0;
        $domainsUpdatedTotal = 0;
        $domainsDetachedTotal = 0;
        $domainsOrphanDeleted = 0;

        try {
            $seenCategoryIds = [];     // ids of categories encountered in this run (for pruning)
            $seenHostsByCatId = [];    // catId => [host => true] hosts read from disk per category

            // Each folder in root is a category candidate
            $dirPaths = File::directories($root);

            foreach ($dirPaths as $catDir) {
                $slug = basename($catDir);
                $domainFile = $catDir.DIRECTORY_SEPARATOR.'domains';

                // Upsert category row (by slug); also keep a relative path for reference
                $category = DomainCategory::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $slug,
                        'files_path' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $catDir),
                        'priority' => $prioMap[$slug] ?? 0,
                        'updated_from_fs_at' => $now,
                    ]
                );

                if (!isset($statsByCat[$category->id])) {
                    $statsByCat[$category->id] = [
                        'slug'     => $slug,
                        'prio'     => (int) ($prioMap[$slug] ?? 0),
                        'seen'     => 0,   // lines processed (valid hosts) in this run
                        'created'  => 0,   // newly created domains
                        'updated'  => 0,   // existing domains that changed (timestamps/tld/etc.)
                        'detached' => 0,   // links removed during pruning
                    ];
                }

                if ($category->wasRecentlyCreated) {
                    $categoriesCreated++;
                }

                $seenCategoryIds[] = $category->id;
                $seenHostsByCatId[$category->id] = [];

                // If there is no "domains" file, skip reading for this folder
                if (! File::exists($domainFile)) {
                    continue;
                }

                // Stream through file lines (memory-friendly for large lists)
                foreach (File::lines($domainFile) as $raw) {
                    $raw = trim($raw);

                    // Ignore empty lines and full-line comments
                    if ($raw === '' || str_starts_with($raw, '#')) {
                        continue;
                    }

                    // Convert to a sanitized host; keep "www" if present
                    $host = $this->normalizeHostKeepingWww($raw);
                    if ($host === null) {
                        continue; // skip unparsable entries
                    }

                    // Normalize (IDNA) and extract TLD (if any)
                    $normalized = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host;
                    $tld = str_contains($normalized, '.') ? Str::afterLast($normalized, '.') : null;

                    // Create or fetch domain row by "host" (unique identity)
                    $domain = Domain::firstOrCreate(
                        ['host' => $normalized],
                        [
                            'normalized_host' => $normalized,
                            'tld' => $tld,
                            'category_id' => $category->id, // initial primary category (may be changed later)
                            'first_seen_at' => $now,
                            'last_seen_at' => $now,
                        ]
                    );

                    $justCreated = $domain->wasRecentlyCreated;
                    if ($justCreated) {
                        $domainsCreatedTotal++;
                        $statsByCat[$category->id]['created']++;
                    }

                    // Apply changes/timestamps to existing rows when appropriate
                    $mustSave = false;

                    if ($domain->first_seen_at === null) {
                        $domain->first_seen_at = $now;
                        $mustSave = true;
                    }

                    // Always refresh last_seen_at to reflect presence in this run
                    $domain->last_seen_at = $now;
                    $mustSave = true;

                    if ($domain->normalized_host !== $normalized) {
                        $domain->normalized_host = $normalized;
                        $mustSave = true;
                    }

                    if ($domain->tld !== $tld) {
                        $domain->tld = $tld;
                        $mustSave = true;
                    }

                    if ($mustSave && ! $justCreated) {
                        $domainsUpdatedTotal++;
                        $statsByCat[$category->id]['updated']++;
                    }

                    if ($mustSave) {
                        $domain->save();
                    }

                    // Ensure the many-to-many link exists
                    $domain->categories()->syncWithoutDetaching([$category->id]);

                    // Track for pruning decisions
                    $seenHostsByCatId[$category->id][$normalized] = true;
                    $statsByCat[$category->id]['seen']++;
                }
            }

            // Pick each domain's "primary" category by the best (lowest) priority number
            $this->assignPrimaryCategories();

            // Prune categories and links that are no longer present on disk (unless --no-prune)
            if (! $this->option('no-prune')) {
                // Remove categories under <root> that were not encountered in this run
                $toDelete = DomainCategory::query()
                    ->where('files_path', 'like', rtrim($rootRel, '/').'/'.'%')
                    ->when(! empty($seenCategoryIds), fn ($q) => $q->whereNotIn('id', $seenCategoryIds))
                    ->get();

                foreach ($toDelete as $cat) {
                    $categoriesDeleted++;
                    $cat->delete();
                }

                // For each category processed, detach domain links that disappeared from its "domains" file
                foreach ($seenHostsByCatId as $catId => $seenHosts) {
                    $hostList = array_keys($seenHosts);

                    $detachIds = DB::table('domain_category_domain')
                        ->join('domains', 'domains.id', '=', 'domain_category_domain.domain_id')
                        ->where('domain_category_domain.domain_category_id', $catId)
                        ->when(! empty($hostList), fn ($q) => $q->whereNotIn('domains.host', $hostList))
                        ->pluck('domain_category_domain.domain_id')
                        ->all();

                    if (! empty($detachIds)) {
                        $count = count($detachIds);
                        $domainsDetachedTotal += $count;

                        if (isset($statsByCat[$catId])) {
                            $statsByCat[$catId]['detached'] += $count;
                        }

                        DB::table('domain_category_domain')
                            ->where('domain_category_id', $catId)
                            ->whereIn('domain_id', $detachIds)
                            ->delete();
                    }
                }

                // Remove domains that no longer belong to any category (fully orphaned)
                $orphans = Domain::doesntHave('categories')->pluck('id');
                if ($orphans->count()) {
                    $domainsOrphanDeleted = (int) $orphans->count();
                    Domain::whereIn('id', $orphans)->delete();
                }
            }

            // Bump a cache "version" to let UIs know data changed
            Cache::put('domain_stats_version', $now->timestamp, 86400);

            DB::commit();

            // ---- Compact, human-friendly summary ----
            $this->info('Sync complete. Stats cache version bumped.');
            $this->line('');

            // Count links per category (post-sync)
            $linkedCounts = DB::table('domain_category_domain')
                ->select('domain_category_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('domain_category_id')
                ->pluck('cnt', 'domain_category_id');

            // Prepare display rows; sort by effective priority then slug
            $rows = [];
            uasort($statsByCat, function ($a, $b) {
                $pa = $a['prio'] ?: PHP_INT_MAX;
                $pb = $b['prio'] ?: PHP_INT_MAX;
                if ($pa === $pb) {
                    return strcmp($a['slug'], $b['slug']);
                }
                return $pa <=> $pb;
            });

            foreach ($statsByCat as $catId => $s) {
                $rows[] = [
                    $s['slug'],
                    $s['prio'] ?: 0,
                    $s['seen'],
                    (int) ($linkedCounts[$catId] ?? 0),
                    $s['created'],
                    $s['updated'],
                    $s['detached'],
                ];
            }

            if (!empty($rows)) {
                $this->table(
                    ['Category', 'Prio', 'Seen (this run)', 'Linked (post-sync)', 'New', 'Updated', 'Detached'],
                    $rows
                );
            } else {
                $this->line('No categories processed.');
            }

            // Totals table
            $this->line('');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Categories created', $categoriesCreated],
                    ['Categories deleted', $categoriesDeleted],
                    ['Domains created', $domainsCreatedTotal],
                    ['Domains updated', $domainsUpdatedTotal],
                    ['Domain links detached', $domainsDetachedTotal],
                    ['Orphan domains deleted', $domainsOrphanDeleted],
                ]
            );

            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Sync failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Build a map of priorities from "<root>/prio.txt".
     *
     * Format rules:
     *  - Lines may contain names separated by spaces and/or commas.
     *  - Empty lines and lines starting with "#" are ignored.
     *  - Inline comments after "#" are stripped.
     *  - A leading "!" on a token is ignored (the name is still used).
     *  - The first occurrence wins and defines the lower priority number.
     *
     * Example line: "whitelist, blacklist   ddi,   webmailer"
     * Resulting map: ['whitelist' => 1, 'blacklist' => 2, 'ddi' => 3, 'webmailer' => 4]
     *
     * @param  string $root Absolute root folder.
     * @return array<string,int> slug => position (1..N)
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

            // Remove trailing inline comments
            $line = preg_replace('/#.*/', '', $line) ?? '';
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Accept both commas and whitespace as separators
            $parts = preg_split('/[,\s]+/', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($parts as $t) {
                // Treat a leading "!" as a marker to ignore (we still keep the name)
                $t = ltrim(trim($t), '!');
                if ($t === '') {
                    continue;
                }
                // Preserve encountered order; duplicates removed later
                $order[] = $t;
            }
        }

        // Preserve first occurrence order
        $order = array_values(array_unique($order));

        // Map to 1-based priority index
        $map = [];
        foreach ($order as $i => $slug) {
            $map[$slug] = $i + 1;
        }

        return $map;
    }

    /**
     * Choose and persist the "primary" category on each Domain.
     *
     * Rules:
     *  - If a domain has no categories, clear its primary category.
     *  - If it has categories, pick the one with the lowest non-zero "priority".
     *  - Categories with priority=0 are considered lowest preference.
     *
     * @return void
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
     * Normalize an input string to a host, preserving "www" if present.
     * Accepts raw host or URL (http/https).
     *
     * Examples:
     *  - "Example.com"  -> "example.com"
     *  - "http://www.x.org/" -> "www.x.org"
     *  - "foo" (no dot) -> null (unless "localhost")
     *
     * @param  string $raw
     * @return string|null sanitized host or null if invalid
     */
    private function normalizeHostKeepingWww(string $raw): ?string
    {
        $raw = strtolower(trim($raw));
        if ($raw === '') {
            return null;
        }

        // Ensure parse_url can extract host by guaranteeing a scheme
        $maybe = (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) ? $raw : 'http://'.$raw;
        $parts = @parse_url($maybe);
        $host = $parts['host'] ?? null;
        if (! $host) {
            return null;
        }

        // Remove trailing dot (rooted FQDNs)
        $host = rtrim($host, '.');

        // Reject bare labels (e.g., "foo") unless it's "localhost"
        if ($host === '' || (! str_contains($host, '.') && $host !== 'localhost')) {
            return null;
        }

        return $host;
    }
}

/*
================================================================================
prio.txt — EXAMPLES
(Spaces and/or commas separate categories. Leading "!" will be ignored.)
================================================================================

1) Space-separated (each space is a category):
   whitelist blacklist ddi webmailer

   Parsed priority map:
   [
     'whitelist' => 1,
     'blacklist' => 2,
     'ddi'       => 3,
     'webmailer' => 4,
   ]

2) Mixed commas and spaces:
   whitelist, blacklist   ddi,   webmailer

   Parsed priority map:
   [
     'whitelist' => 1,
     'blacklist' => 2,
     'ddi'       => 3,
     'webmailer' => 4,
   ]

3) With leading "!" markers (they are IGNORED; names are still used):
   !whitelist !blacklist ddi webmailer

   Parsed priority map:
   [
     'whitelist' => 1,
     'blacklist' => 2,
     'ddi'       => 3,
     'webmailer' => 4,
   ]
*/
