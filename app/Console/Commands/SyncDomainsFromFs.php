<?php

namespace App\Console\Commands;

use App\Models\DomainCategory;
use App\Models\Domain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SyncDomainsFromFs extends Command
{
    // Default root & pruning behavior (you can tweak)
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
        $seenHosts = [];        // host => true
        $seenCategorySlugs = []; // slug => true
        $rootRel = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $root);

        // Wrap the whole thing to keep it consistent on failure
        DB::beginTransaction();

        try {
            // Enumerate categories (subfolders under root)
            foreach (File::directories($root) as $catDir) {
                $slug = basename($catDir);
                $seenCategorySlugs[$slug] = true;

                $name = Str::headline($slug);
                $domainFile = $catDir . DIRECTORY_SEPARATOR . 'domains';

                // upsert category
                $category = DomainCategory::query()->updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'files_path' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $catDir)]
                );

                if (!File::exists($domainFile)) {
                    $this->warn("No 'domains' file in $catDir");
                    // still update timestamp so we know the folder was scanned
                    $category->updated_from_fs_at = $now;
                    $category->save();
                    continue;
                }

                // Stream lines (memory-friendly)
                foreach (File::lines($domainFile) as $raw) {
                    $raw = trim($raw);
                    if ($raw === '' || str_starts_with($raw, '#')) continue;

                    // Normalize to bare host (drop scheme/path/port)
                    $host = $this->normalizeHost($raw);
                    if ($host === null) continue;

                    $normalized = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host;
                    $tld = str_contains($normalized, '.') ? Str::afterLast($normalized, '.') : null;

                    // Upsert by host; update category if it moved
                    $domain = Domain::query()->updateOrCreate(
                        ['host' => $normalized],
                        [
                            'normalized_host' => $normalized,
                            'tld' => $tld,
                            'category_id' => $category->id,
                            'last_seen_at' => $now,
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

            // PRUNE to mirror (unless --no-prune)
            if (!$this->option('no-prune')) {
                // 1) Delete domains under this root whose hosts were NOT seen
                $rootCategoryIds = DomainCategory::query()
                    ->where('files_path', 'like', $rootRel . '/%')
                    ->pluck('id');

                if ($rootCategoryIds->isNotEmpty()) {
                    $deletedDomains = Domain::query()
                        ->whereIn('category_id', $rootCategoryIds)
                        ->when(!empty($seenHosts), function ($q) use ($seenHosts) {
                            $q->whereNotIn('host', array_keys($seenHosts));
                        }, function ($q) {
                            // If nothing was seen, delete all domains for this root
                            // (because the mirror is empty)
                        })
                        ->delete();

                    if ($deletedDomains) {
                        $this->info("Pruned $deletedDomains domain(s) not present in filesystem.");
                    }
                }

                // 2) Delete categories under this root whose folders no longer exist
                $deletedCats = DomainCategory::query()
                    ->where('files_path', 'like', $rootRel . '/%')
                    ->whereNotIn('slug', array_keys($seenCategorySlugs))
                    ->get();

                foreach ($deletedCats as $cat) {
                    // cascadeOnDelete will remove its domains
                    $cat->delete();
                }
                if ($deletedCats->count()) {
                    $this->info("Removed {$deletedCats->count()} stale categor(ies) not present in filesystem.");
                }
            }

            DB::commit();
            $this->info('Sync complete (mirrored).');
            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Turn a line from the file into a bare hostname (e.g., example.com).
     * Returns null if it cannot be parsed into a valid host-ish string.
     */
    private function normalizeHost(string $raw): ?string
    {
        $raw = strtolower($raw);

        // If it lacks scheme, parse_url may treat it as path; force scheme when missing
        $maybe = $raw;
        if (!str_starts_with($maybe, 'http://') && !str_starts_with($maybe, 'https://')) {
            $maybe = 'http://' . $maybe;
        }

        $parts = @parse_url($maybe);
        $host = $parts['host'] ?? null;
        if (!$host) return null;

        // strip leading www. if you prefer (optional)
        $host = preg_replace('/^www\./', '', $host);

        // remove trailing dots
        $host = rtrim($host, '.');

        // basic sanity (contains at least one dot or is localhost-like)
        if ($host === '' || (!str_contains($host, '.') && $host !== 'localhost')) {
            return null;
        }

        return $host;
    }
}
