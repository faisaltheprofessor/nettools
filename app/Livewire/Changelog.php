<?php

namespace App\Livewire;

use Livewire\Component;

class Changelog extends Component
{
    public string $version = '';
    public string $path = '';
    public array  $entries = [];
    public string $query = '';

    public function mount()
    {
        $this->version = config('app.version', '');
        $this->path    = $this->resolveChangelogPath();
        $markdown      = $this->path && file_exists($this->path) ? file_get_contents($this->path) : '';
        $this->entries = $markdown ? $this->parseChangelog($markdown) : [];
    }

    private function resolveChangelogPath(): string
    {
        $candidates = [
            base_path('CHANGELOG.md'),
            base_path('Changelog.md'),
            resource_path('CHANGELOG.md'),
        ];
        foreach ($candidates as $p) {
            if (file_exists($p)) return $p;
        }
        return '';
    }

    private function parseChangelog(string $md): array
    {
        $entries = [];
        $md = str_replace(["\r\n", "\r"], "\n", $md);

        $pattern = '/^##\s+\[?([^\]\n]+)\]?\s*(?:-\s*([0-9]{4}-[0-9]{2}-[0-9]{2}))?\s*$/m';
        if (!preg_match_all($pattern, $md, $matches, PREG_OFFSET_CAPTURE)) {
            return $entries;
        }

        $count = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $startPos = $matches[0][$i][1];
            $endPos   = ($i < $count - 1) ? $matches[0][$i+1][1] : strlen($md);

            $version  = trim($matches[1][$i][0]);
            $date     = isset($matches[2][$i][0]) ? trim($matches[2][$i][0]) : null;

            $lineEnd  = strpos($md, "\n", $startPos);
            $block    = substr($md, $lineEnd !== false ? $lineEnd : $startPos, $endPos - ($lineEnd !== false ? $lineEnd : $startPos));

            $entries[] = [
                'version'  => $version,
                'date'     => $date,
                'raw'      => trim($block),
                'sections' => $this->parseSections($block),
            ];
        }

        return $entries;
    }

    private function parseSections(string $block): array
    {
        $sections = [];
        $block = ltrim($block, "\n");

        $secPattern = '/^###\s+([^\n]+)\s*$/m';
        if (!preg_match_all($secPattern, $block, $secMatches, PREG_OFFSET_CAPTURE)) {
            $items = $this->collectBullets($block);
            if (!empty($items)) {
                $sections[] = ['title' => 'Other', 'items' => $items];
            }
            return $sections;
        }

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

    private function collectBullets(string $txt): array
    {
        $items = [];
        $txt = trim($txt);
        $lines = preg_split('/\n/', $txt);
        $buf = [];

        $flush = function() use (&$buf, &$items) {
            $s = trim(implode("\n", $buf));
            if ($s !== '') $items[] = $s;
            $buf = [];
        };

        foreach ($lines as $line) {
            if (preg_match('/^\s*([-*]|\d+\.)\s+(.+)$/', $line, $m)) {
                $flush();
                $buf[] = $m[2];
            } else {
                if (!empty($buf)) $buf[] = $line;
            }
        }
        $flush();

        return array_map(fn($s) => preg_replace('/`([^`]+)`/', '$1', $s), $items);
    }

    public function getFilteredEntriesProperty(): array
    {
        $q = mb_strtolower(trim($this->query));
        if ($q === '') return $this->entries;

        return array_values(array_filter($this->entries, function ($e) use ($q) {
            if (str_contains(mb_strtolower($e['version']), $q)) return true;
            if (!empty($e['date']) && str_contains(mb_strtolower($e['date']), $q)) return true;
            if (!empty($e['raw']) && str_contains(mb_strtolower($e['raw']), $q)) return true;
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
