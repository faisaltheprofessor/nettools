<?php

namespace App\Livewire;

use App\Models\FirewallTemplate;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

class FirewallVorlagen extends Component
{
    public array $templates = [];
    public string $templateId = '';
    #[Validate('required|string|min:3', as: 'Vorlagenname')]
    public string $name = '';

    /** Active/open accordion index (single-open behavior) */
    public int $expandedIndex = 0;

    /** Avoid Livewire $rules collision: use $ruleGroups for data */
    /** @var array<int, array{sourcesText:string,destinationsText:string,ports:array,portInput:string,portQuick:string,portSelect:string}> */
    public array $ruleGroups = [];

    public array $portSuggestions = ['80/tcp','443/tcp','22/tcp','53/udp','3306/tcp','5432/tcp'];

    // Searchable catalog & label map for friendly names
    public array $portCatalog = [
        ['label' => 'HTTP',  'value' => '80/tcp'],
        ['label' => 'HTTPS', 'value' => '443/tcp'],
        ['label' => 'DNS',   'value' => '53/udp'],
        ['label' => 'LDAP',  'value' => '389/tcp'],
        ['label' => 'LDAPS', 'value' => '636/tcp'],
        // extend as needed...
    ];
    public array $portLabelMap = [];

    // E-Mail Vorschau
    public string $emailSubject = '';
    public string $emailBody = '';
    public string $mailtoUrl = '';
    /** @var array<int, array<int, array{src:string,dst:string,port:string}>> */
    public array $previewGroups = [];

    public function mount(): void
    {
        $this->refreshTemplates();

        foreach ($this->portCatalog as $opt) {
            $this->portLabelMap[$opt['value']] = $opt['label'];
        }

        if (empty($this->ruleGroups)) {
            $this->addRule();
        }
    }

    public function render()
    {
        return view('livewire.firewall-vorlagen');
    }

    public function refreshTemplates(): void
    {
        $this->templates = FirewallTemplate::orderBy('name')->get(['id','name'])->toArray();
    }

    /** When user selects a template or "new", load or reset */
    public function handleTemplateSelect(): void
    {
        if ($this->templateId === '') {
            $this->resetForm();
            $this->dispatch('flux-toast', title: 'Neu', description: 'Neues Verfahren – Felder zurückgesetzt.');
            return;
        }

        $id = (int)$this->templateId;
        $tpl = FirewallTemplate::find($id);
        if (!$tpl) {
            $this->resetForm();
            $this->dispatch('flux-toast', title: 'Nicht gefunden', description: 'Vorlage existiert nicht mehr.', variant: 'danger');
            return;
        }

        $this->name = $tpl->name;

        $srcs = $tpl->sources ?? [];
        $dsts = $tpl->destinations ?? [];
        $prts = $tpl->ports ?? [];

        $isNested = static fn($a) => !empty($a) && is_array(reset($a));
        $srcGroups = $isNested($srcs) ? $srcs : (empty($srcs) ? [] : [ $srcs ]);
        $dstGroups = $isNested($dsts) ? $dsts : (empty($dsts) ? [] : [ $dsts ]);
        $prtGroups = $isNested($prts) ? $prts : (empty($prts) ? [] : [ $prts ]);

        $count = max(count($srcGroups), count($dstGroups), count($prtGroups));
        $this->ruleGroups = [];

        for ($i = 0; $i < max(1, $count); $i++) {
            $sources = $srcGroups[$i] ?? [];
            $dests   = $dstGroups[$i] ?? [];
            $ports   = $prtGroups[$i] ?? [];
            $this->addRule(
                sourcesText: implode(PHP_EOL, $sources),
                destinationsText: implode(PHP_EOL, $dests),
                ports: array_values($ports)
            );
        }

        // After loading, open the first item
        $this->expandedIndex = 0;

        $this->dispatch('flux-toast', title: 'Vorlage geladen', description: $tpl->name);
    }

    /** Hard reset to pristine "new" state */
    private function resetForm(): void
    {
        $this->name = '';
        $this->ruleGroups = [];
        $this->addRule();
        $this->emailSubject = '';
        $this->emailBody = '';
        $this->mailtoUrl = '';
        $this->previewGroups = [];
        $this->expandedIndex = 0;
    }

    /* ===== Regeln ===== */

    public function addRule(string $sourcesText = '', string $destinationsText = '', array $ports = []): void
    {
        if ($sourcesText === '' && $destinationsText === '' && !empty($this->ruleGroups)) {
            // Auto: Quelle/Ziel vom vorherigen Eintrag vertauschen
            $prev = $this->ruleGroups[array_key_last($this->ruleGroups)];
            $prevSources = $this->normalizeList($prev['sourcesText'] ?? '');
            $prevDests   = $this->normalizeList($prev['destinationsText'] ?? '');
            $sourcesText = implode(PHP_EOL, $prevDests);
            $destinationsText = implode(PHP_EOL, $prevSources);
        }

        $this->ruleGroups[] = [
            'sourcesText'      => $sourcesText,
            'destinationsText' => $destinationsText,
            'ports'            => array_values($ports),
            'portInput'        => '',
            'portQuick'        => '',
            'portSelect'       => '',
        ];

        // Expand the new rule and collapse the others
        $this->expandedIndex = array_key_last($this->ruleGroups);
    }

    public function removeRule(int $index): void
    {
        if (!isset($this->ruleGroups[$index])) return;
        unset($this->ruleGroups[$index]);
        $this->ruleGroups = array_values($this->ruleGroups);

        // Adjust expanded index to a valid item
        $last = count($this->ruleGroups) - 1;
        if ($last < 0) {
            $this->addRule();
            $this->expandedIndex = 0;
            return;
        }

        if ($this->expandedIndex > $last) {
            $this->expandedIndex = $last;
        }
    }

    public function addPortFromSelect(int $index): void
    {
        if (!isset($this->ruleGroups[$index])) return;
        $val = (string)($this->ruleGroups[$index]['portSelect'] ?? '');
        if ($val === '') return;

        $this->addPort($index, $val);
        $this->ruleGroups[$index]['portSelect'] = '';
    }

    public function addPortFromRadio(int $index): void
    {
        if (!isset($this->ruleGroups[$index])) return;
        $val = $this->ruleGroups[$index]['portQuick'] ?? '';
        if ($val !== '') {
            $this->addPort($index, $val);
            $this->ruleGroups[$index]['portQuick'] = '';
        }
    }

    public function addCustomPort(int $index): void
    {
        if (!isset($this->ruleGroups[$index])) return;
        $value = trim((string)($this->ruleGroups[$index]['portInput'] ?? ''));
        if ($value === '') return;

        if (!$this->isValidPort($value)) {
            $this->dispatch('flux-toast', title: 'Ungültiger Port', description: 'Format NNN/(tcp|udp), 1–65535', variant: 'danger');
            return;
        }

        $this->addPort($index, $value);
        $this->ruleGroups[$index]['portInput'] = '';
    }

    public function removePort(int $index, string $value): void
    {
        if (!isset($this->ruleGroups[$index])) return;
        $this->ruleGroups[$index]['ports'] = array_values(array_filter(
            $this->ruleGroups[$index]['ports'],
            fn ($p) => $p !== $value
        ));
    }

    private function addPort(int $index, string $value): void
    {
        if (!isset($this->ruleGroups[$index])) return;

        $value = strtolower(trim($value));
        if (!$this->isValidPort($value)) return;

        if (!in_array($value, $this->ruleGroups[$index]['ports'], true)) {
            $this->ruleGroups[$index]['ports'][] = $value;
        } else {
            $this->dispatch('flux-toast', title: 'Duplikat', description: "$value bereits vorhanden");
        }
    }

    private function isValidPort(string $value): bool
    {
        if (!preg_match('/^\s*(\d{1,5})\s*\/\s*(tcp|udp)\s*$/i', $value, $m)) return false;
        $n = (int)$m[1];
        return $n >= 1 && $n <= 65535;
    }

    /* ===== Speichern ===== */

    public function saveTemplate(): void
    {
        // Create when new (no templateId), update when existing
        $ignoreId = $this->templateId !== '' ? (int)$this->templateId : null;
        $this->validateNameAndRules($ignoreId);

        [$sources, $destinations, $ports] = $this->ruleGroupsToArrays();

        if ($this->templateId === '') {
            $tpl = FirewallTemplate::create([
                'name'         => $this->name,
                'sources'      => $sources,
                'destinations' => $destinations,
                'ports'        => $ports,
            ]);
            $this->templateId = (string)$tpl->id;
            $msg = 'Neue Vorlage wurde angelegt';
        } else {
            $tpl = FirewallTemplate::find((int)$this->templateId);
            if (!$tpl) {
                $this->resetForm();
                $this->dispatch('flux-toast', title: 'Nicht gefunden', description: 'Vorlage existiert nicht mehr.', variant: 'danger');
                return;
            }
            $tpl->update([
                'name'         => $this->name,
                'sources'      => $sources,
                'destinations' => $destinations,
                'ports'        => $ports,
            ]);
            $msg = 'Vorlage wurde aktualisiert';
        }

        $this->refreshTemplates();
        Flux::toast('Gespeichert');
        $this->dispatch('flux-toast', title: 'Gespeichert', description: $msg);
    }

    public function saveAsTemplate(): void
    {
        // Always create a new one
        $this->validateNameAndRules(null);

        [$sources, $destinations, $ports] = $this->ruleGroupsToArrays();
        $tpl = FirewallTemplate::create([
            'name'         => $this->name,
            'sources'      => $sources,
            'destinations' => $destinations,
            'ports'        => $ports,
        ]);

        $this->templateId = (string)$tpl->id;
        $this->refreshTemplates();

        Flux::toast('Gespeichert');
        $this->dispatch('flux-toast', title: 'Gespeichert', description: 'Neue Vorlage wurde angelegt');
    }

    private function ruleGroupsToArrays(): array
    {
        $sources = [];
        $destinations = [];
        $ports = [];
        foreach ($this->ruleGroups as $r) {
            $sources[]      = $this->normalizeList((string)($r['sourcesText'] ?? ''));
            $destinations[] = $this->normalizeList((string)($r['destinationsText'] ?? ''));
            $ports[]        = array_values($r['ports'] ?? []);
        }
        return [$sources, $destinations, $ports];
    }

    private function validateNameAndRules(?int $ignoreId): void
    {
        $this->validate([
            'name' => [
                'required','string','min:3',
                Rule::unique('firewall_templates', 'name')->ignore($ignoreId),
            ],
        ], [
            'name.required' => 'Bitte einen Namen für das Verfahren angeben.',
            'name.unique'   => 'Dieser Verfahrensname ist bereits vergeben.',
        ], [
            'name' => 'Verfahrensname',
        ]);

        if (empty($this->ruleGroups)) {
            $this->addError('ruleGroups', 'Mindestens eine Regel ist erforderlich.');
        } else {
            foreach ($this->ruleGroups as $idx => $r) {
                if (count($this->normalizeList((string)($r['destinationsText'] ?? ''))) < 1) {
                    $this->addError("ruleGroups.$idx.destinationsText", "Mindestens ein Ziel in Regel ".($idx+1)." angeben.");
                }
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages($this->getErrorBag()->toArray());
        }
    }

    /* ===== E-Mail erzeugen ===== */

    public function generate(): void
    {
        $this->emailSubject = "Firewall-Antrag – {$this->name}";

        $this->previewGroups = [];
        $textBlocks = [];

        foreach ($this->ruleGroups as $idx => $r) {
            $sources      = $this->normalizeList((string)($r['sourcesText'] ?? ''));
            $destinations = $this->normalizeList((string)($r['destinationsText'] ?? ''));
            $ports        = array_values($r['ports'] ?? []);

            $rows = $this->zipRows3($sources, $destinations, $ports);
            $this->previewGroups[] = $rows;

            $table = $this->buildTableText($rows);
            $textBlocks[] = "Regel ".($idx+1).":\nQuellen / Ziele / Ports:\n\n".$table;
        }

        $greeting  = "Guten Tag,";
        $intro     = "ich bitte um das Hinzufügen folgender Firewall-Einträge.";
        $verfahren = "Verfahren: {$this->name}";

        $this->emailBody = trim(
            "{$greeting}\n\n".
            "{$intro}\n\n".
            "{$verfahren}\n\n".
            implode("\n\n", $textBlocks)."\n\n".
            "Vielen Dank für die Umsetzung.\n".
            "Freundliche Grüße\n".
            (auth()->check() ? auth()->user()->name : '')
        );

        $this->mailtoUrl = 'mailto:?subject=' . rawurlencode($this->emailSubject)
                         . '&body=' . rawurlencode($this->emailBody);

        $this->modal('preview-email')->show();
    }

    /** @return array<int, array{src:string,dst:string,port:string}> */
    private function zipRows3(array $sources, array $destinations, array $ports): array
    {
        $max = max(count($sources), count($destinations), count($ports));
        $rows = [];
        for ($i = 0; $i < $max; $i++) {
            $rows[] = [
                'src'  => $sources[$i] ?? '',
                'dst'  => $destinations[$i] ?? '',
                'port' => $ports[$i] ?? '',
            ];
        }
        return $rows;
    }

    private function buildTableText(array $rows): string
    {
        $c1 = 'Quelle(n)'; $c2 = 'Ziel(e)'; $c3 = 'Port/Protokoll';

        $w1 = max(strlen($c1), ...array_map(fn($r)=>strlen($r['src']),  $rows ?: [['src'=>'']]));
        $w2 = max(strlen($c2), ...array_map(fn($r)=>strlen($r['dst']),  $rows ?: [['dst'=>'']]));
        $w3 = max(strlen($c3), ...array_map(fn($r)=>strlen($r['port']), $rows ?: [['port'=>'']]));

        $header = sprintf("%-{$w1}s | %-{$w2}s | %-{$w3}s", $c1, $c2, $c3);
        $line   = str_repeat('-', $w1 + $w2 + $w3 + 7);

        $body = implode(PHP_EOL, array_map(
            fn($r) => sprintf("%-{$w1}s | %-{$w2}s | %-{$w3}s", $r['src'], $r['dst'], $r['port']),
            $rows
        ));

        return $header . PHP_EOL . $line . PHP_EOL . $body;
    }

    private function normalizeList(string $input): array
    {
        $items = preg_split('/[\r\n,]+/', $input) ?: [];
        return array_values(array_filter(array_map('trim', $items), fn($v)=>$v!==''));
    }
}
