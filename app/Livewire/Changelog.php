<?php

namespace App\Livewire;

use Illuminate\Support\Str;
use Livewire\Component;

class Changelog extends Component
{
    public string $version = '';
    public string $path = '';
    public array  $entries = [];   // parsed changelog entries
    public string $query = '';     // simple search filter

    public function mount()
    {
        $this->version = config('app.version', '');
        $this->path = $this->resolveChangelogPath();
        $markdown = $this->path && file_exists($this->path) ? file_get_contents($this->path) : '';

        $this->entries = $markdown ? $this->parseChangelog($markdown) : [];
    }

    private function resolveChangelogPath(): string
    {
        $candidates = [
            base_path('CHANGELOG.md'),
            base_path('Changelog.md'),
            base_path('changelog.md'),
            resource_path('changelog.md'),
        ];
        foreach ($candidates as $p) {
            if (file_exists($p)) return $p;
        }
        return '';
    }

    /**
     * Parse a Keep-a-Changelog-styled Markdown file into a structured array.
     *
     * Format expected (flexible):
     * ## [1.2.3] - 2025-08-15
     * ### Added
     * - Item
     * ### Fixed
     * - Bug
     */
    private function parseChangelog(string $md): array
    {
        $entries = [];

        // Normalize line endings
        $md = str_replace(["\r\n", "\r"], "\n", $md);

        // Find all version blocks by H2 headings:
        // e.g. "## [1.2.3] - 2025-08-15" or "## 1.2.3 - 2025-08-15" (with/without brackets)
        $pattern = '/^##\s+\[?([^\]\n]+)\]?\s*(?:-\s*([0-9]{4}-[0-9]{2}-[0-9]{2}))?\s*$/m';
        if (!preg_match_all($pattern, $md, $matches, PREG_OFFSET_CAPTURE)) {
            return $entries;
        }

        // Build ranges between headings
        $count = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $startPos = $matches[0][$i][1];
            $endPos   = ($i < $count - 1) ? $matches[0][$i+1][1] : strlen($md);

            $version  = trim($matches[1][$i][0]);
            $date     = isset($matches[2][$i][0]) ? trim($matches[2][$i][0]) : null;

            // Extract the block content after the heading line
            $lineEnd  = strpos($md, "\n", $startPos);
            $block    = substr($md, $lineEnd !== false ? $lineEnd : $startPos, $endPos - ($lineEnd !== false ? $lineEnd : $startPos));

            $entries[] = [
                'version'  => $version,
                'date'     => $date,
                'sections' => $this->parseSections($block),
                'raw'      => trim($block),
            ];
        }

        return $entries;
    }

    /**
     * Parse ### sections and their bullet lists within a version block.
     */
    private function parseSections(string $block): array
    {
        $sections = [];
        $block = ltrim($block, "\n");

        // Split into sections by H3 headings like "### Added"
        $secPattern = '/^###\s+([^\n]+)\s*$/m';
        if (!preg_match_all($secPattern, $block, $secMatches, PREG_OFFSET_CAPTURE)) {
            // No H3 sections—try to collect top-level bullets as "Other"
            $items = $this->collectBullets($block);
            if (!empty($items)) {
                $sections[] = [
                    'title' => 'Other',
                    'items' => $items,
                ];
            }
            return $sections;
        }

        // Build ranges for each section
        $count = count($secMatches[0]);
        for ($i = 0; $i < $count; $i++) {
            $secTitle = trim($secMatches[1][$i][0]);
            $startPos = $secMatches[0][$i][1];
            $lineEnd  = strpos($block, "\n", $startPos);
            $contentStart = $lineEnd !== false ? $lineEnd : $startPos;
            $endPos   = ($i < $count - 1) ? $secMatches[0][$i+1][1] : strlen($block);
            $content  = substr($block, $contentStart, $endPos - $contentStart);

            $sections[] = [
                'title' => $secTitle,
                'items' => $this->collectBullets($content),
            ];
        }

        return $sections;
    }

    /**
     * Collect bullet points (- ..., * ..., or numbered 1. ...) from a section content.
     */
    private function collectBullets(string $txt): array
    {
        $items = [];
        $txt = trim($txt);

        // Handle GitHub-flavored lists; ignore code blocks
        // Extract lines starting with -, *, or \d.
        $lines = preg_split('/\n/', $txt);
        $buf = [];

        $flush = function() use (&$buf, &$items) {
            $s = trim(implode("\n", $buf));
            if ($s !== '') $items[] = $s;
            $buf = [];
        };

        foreach ($lines as $line) {
            if (preg_match('/^\s*([-*]|\d+\.)\s+(.+)$/', $line, $m)) {
                // New bullet => flush previous
                $flush();
                $buf[] = $m[2];
            } else {
                // Continuation of previous bullet (indented or wrapped)
                if (!empty($buf)) {
                    $buf[] = $line;
                }
            }
        }
        $flush();

        // Strip surrounding backticks from inline code if present (keep simple)
        $items = array_map(function ($s) {
            return preg_replace('/`([^`]+)`/', '$1', $s);
        }, $items);

        return $items;
    }

    public function getFilteredEntriesProperty(): array
    {
        $q = mb_strtolower(trim($this->query));
        if ($q === '') return $this->entries;

        return array_values(array_filter($this->entries, function ($e) use ($q) {
            if (str_contains(mb_strtolower($e['version']), $q)) return true;
            if (!empty($e['date']) && str_contains(mb_strtolower($e['date']), $q)) return true;
            foreach ($e['sections'] as $sec) {
                if (str_contains(mb_strtolower($sec['title']), $q)) return true;
                foreach ($sec['items'] as $it) {
                    if (str_contains(mb_strtolower($it), $q)) return true;
                }
            }
            return false;
        }));
    }

    public function render()
    {
        return view('livewire.changelog');
    }
}
