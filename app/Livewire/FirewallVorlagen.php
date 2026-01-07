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

    #[\Livewire\Attributes\Url(as: 'tpl', history: true)]
    public string $templateId = '';

    #[Validate('required|string|min:3', as: 'Vorlagenname')]
    public string $name = '';

    public string $notes = '';

    public int $expandedIndex = 0;

    public array $portSuggestions = ['80/tcp', '443/tcp', '22/tcp', '53/udp', '3306/tcp', '5432/tcp'];

    /**
     * @var array<int, array{sources:array<int,string>,destinations:array<int,string>,ports:array<int,string>,portInput:string,portQuick:string,portSelect:string}>
     */
    public array $ruleGroups = [];

    public array $portCatalog = [
        ['label' => 'HTTP',  'value' => '80/tcp'],
        ['label' => 'HTTPS', 'value' => '443/tcp'],
        ['label' => 'DNS',   'value' => '53/udp'],
        ['label' => 'LDAP',  'value' => '389/tcp'],
        ['label' => 'LDAPS', 'value' => '636/tcp'],
        ['label' => 'SSH',   'value' => '22/tcp'],
    ];

    public array $portLabelMap = [];

    public string $emailSubject = '';
    public string $emailBody = '';
    public string $mailtoUrl = '';
    public array $previewGroups = [];
    public $emailBodyPreview;

    public function mount(): void
    {
        $this->refreshTemplates();

        foreach ($this->portCatalog as $opt) {
            $this->portLabelMap[$opt['value']] = $opt['label'];
        }

        if ($this->templateId !== '') {
            $this->handleTemplateSelect();
            return;
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
        $this->templates = FirewallTemplate::orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function updatedTemplateId(): void
    {
        $this->handleTemplateSelect();
    }

    public function handleTemplateSelect(): void
    {
        if ($this->templateId === '') {
            $this->resetForm();
            $this->dispatch('flux-toast', title: 'Neu', description: 'Neues Verfahren – Felder zurückgesetzt.');
            return;
        }

        $tpl = FirewallTemplate::find((int) $this->templateId);

        if (! $tpl) {
            $this->resetForm();
            $this->dispatch('flux-toast', title: 'Nicht gefunden', description: 'Vorlage existiert nicht mehr.', variant: 'danger');
            return;
        }

        $this->name = (string) $tpl->name;

        $srcs = $tpl->sources ?? [];
        $dsts = $tpl->destinations ?? [];
        $prts = $tpl->ports ?? [];

        $isNested = static fn ($a) => is_array($a) && !empty($a) && is_array(reset($a));

        $srcGroups = $isNested($srcs) ? $srcs : (empty($srcs) ? [] : [$srcs]);
        $dstGroups = $isNested($dsts) ? $dsts : (empty($dsts) ? [] : [$dsts]);
        $prtGroups = $isNested($prts) ? $prts : (empty($prts) ? [] : [$prts]);

        $count = max(count($srcGroups), count($dstGroups), count($prtGroups));
        $this->ruleGroups = [];

        for ($i = 0; $i < max(1, $count); $i++) {
            $sources = $this->normalizeStoredList($srcGroups[$i] ?? []);
            $dests   = $this->normalizeStoredList($dstGroups[$i] ?? []);
            $ports   = array_values(array_map(fn ($p) => strtolower(trim((string) $p)), $this->normalizeStoredList($prtGroups[$i] ?? [])));

            $this->addRule($sources, $dests, $ports);
        }

        $this->expandedIndex = 0;
        $this->dispatch('flux-toast', title: 'Vorlage geladen', description: $tpl->name);
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->notes = '';
        $this->ruleGroups = [];
        $this->addRule();
        $this->emailSubject = '';
        $this->emailBody = '';
        $this->mailtoUrl = '';
        $this->previewGroups = [];
        $this->expandedIndex = 0;
    }

    public function addRule(array $sources = [], array $destinations = [], array $ports = []): void
    {
        if (empty($sources) && empty($destinations) && ! empty($this->ruleGroups)) {
            $prev = $this->ruleGroups[array_key_last($this->ruleGroups)];
            $sources = $this->normalizeArrayList($prev['destinations'] ?? []);
            $destinations = $this->normalizeArrayList($prev['sources'] ?? []);
        }

        $this->ruleGroups[] = [
            'sources' => $this->ensureAtLeastOne($this->normalizeArrayList($sources)),
            'destinations' => $this->ensureAtLeastOne($this->normalizeArrayList($destinations)),
            'ports' => array_values($ports),
            'portInput' => '',
            'portQuick' => '',
            'portSelect' => '',
        ];

        $this->expandedIndex = array_key_last($this->ruleGroups);
    }

    public function removeRule(int $index): void
    {
        if (!isset($this->ruleGroups[$index])) {
            return;
        }

        unset($this->ruleGroups[$index]);
        $this->ruleGroups = array_values($this->ruleGroups);

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

    public function addSource(int $ruleIndex): void
    {
        if (!isset($this->ruleGroups[$ruleIndex])) {
            return;
        }
        $this->ruleGroups[$ruleIndex]['sources'][] = '';
    }

    public function removeSource(int $ruleIndex, int $itemIndex): void
    {
        if (!isset($this->ruleGroups[$ruleIndex]['sources'][$itemIndex])) {
            return;
        }

        unset($this->ruleGroups[$ruleIndex]['sources'][$itemIndex]);
        $this->ruleGroups[$ruleIndex]['sources'] = $this->ensureAtLeastOne(array_values($this->ruleGroups[$ruleIndex]['sources']));
    }

    public function addDestination(int $ruleIndex): void
    {
        if (!isset($this->ruleGroups[$ruleIndex])) {
            return;
        }
        $this->ruleGroups[$ruleIndex]['destinations'][] = '';
    }

    public function removeDestination(int $ruleIndex, int $itemIndex): void
    {
        if (!isset($this->ruleGroups[$ruleIndex]['destinations'][$itemIndex])) {
            return;
        }

        unset($this->ruleGroups[$ruleIndex]['destinations'][$itemIndex]);
        $this->ruleGroups[$ruleIndex]['destinations'] = $this->ensureAtLeastOne(array_values($this->ruleGroups[$ruleIndex]['destinations']));
    }

    private function ensureAtLeastOne(array $items): array
    {
        $items = array_values($items);
        return empty($items) ? [''] : $items;
    }

    public function addPortFromSelect(int $index): void
    {
        if (!isset($this->ruleGroups[$index])) {
            return;
        }

        $val = (string) ($this->ruleGroups[$index]['portSelect'] ?? '');
        if ($val === '') {
            return;
        }

        $this->addPort($index, $val);
        $this->ruleGroups[$index]['portSelect'] = '';
    }

    public function addCustomPort(int $index): void
    {
        if (!isset($this->ruleGroups[$index])) {
            return;
        }

        $value = trim((string) ($this->ruleGroups[$index]['portInput'] ?? ''));
        if ($value === '') {
            return;
        }

        if (!$this->isValidPortOrRange($value)) {
            $this->dispatch('flux-toast', title: 'Ungültiger Port', description: 'Format NNN/(tcp|udp) oder A-B/(tcp|udp), 1–65535', variant: 'danger');
            return;
        }

        $this->addPort($index, $value);
        $this->ruleGroups[$index]['portInput'] = '';
    }

    public function removePort(int $index, string $value): void
    {
        if (!isset($this->ruleGroups[$index])) {
            return;
        }

        $this->ruleGroups[$index]['ports'] = array_values(array_filter(
            $this->ruleGroups[$index]['ports'],
            fn ($p) => $p !== $value
        ));
    }

    private function addPort(int $index, string $value): void
    {
        if (!isset($this->ruleGroups[$index])) {
            return;
        }

        $value = strtolower(trim($value));
        if (!$this->isValidPortOrRange($value)) {
            return;
        }

        if (!in_array($value, $this->ruleGroups[$index]['ports'], true)) {
            $this->ruleGroups[$index]['ports'][] = $value;
        } else {
            $this->dispatch('flux-toast', title: 'Duplikat', description: "$value bereits vorhanden");
        }
    }

    private function isValidPortOrRange(string $value): bool
    {
        if (!preg_match('/^\s*(\d{1,5})(?:\s*-\s*(\d{1,5}))?\s*\/\s*(tcp|udp)\s*$/i', $value, $m)) {
            return false;
        }

        $a = (int) $m[1];
        $b = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : null;

        if ($a < 1 || $a > 65535) {
            return false;
        }

        if ($b !== null) {
            if ($b < 1 || $b > 65535) {
                return false;
            }
            if ($b < $a) {
                return false;
            }
        }

        return true;
    }

    public function saveTemplate(): void
    {
        $ignoreId = $this->templateId !== '' ? (int) $this->templateId : null;
        $this->validateNameAndRules($ignoreId);

        [$sources, $destinations, $ports] = $this->ruleGroupsToArrays();

        if ($this->templateId === '') {
            $tpl = FirewallTemplate::create([
                'name' => $this->name,
                'sources' => $sources,
                'destinations' => $destinations,
                'ports' => $ports,
            ]);
            $this->templateId = (string) $tpl->id;
            $msg = 'Neue Vorlage wurde angelegt';
        } else {
            $tpl = FirewallTemplate::find((int) $this->templateId);
            if (!$tpl) {
                $this->resetForm();
                $this->dispatch('flux-toast', title: 'Nicht gefunden', description: 'Vorlage existiert nicht mehr.', variant: 'danger');
                return;
            }
            $tpl->update([
                'name' => $this->name,
                'sources' => $sources,
                'destinations' => $destinations,
                'ports' => $ports,
            ]);
            $msg = 'Vorlage wurde aktualisiert';
        }

        $this->refreshTemplates();
        Flux::toast('Gespeichert');
        $this->dispatch('flux-toast', title: 'Gespeichert', description: $msg);
    }

    public function saveAsTemplate(): void
    {
        $this->validateNameAndRules(null);

        [$sources, $destinations, $ports] = $this->ruleGroupsToArrays();

        $tpl = FirewallTemplate::create([
            'name' => $this->name,
            'sources' => $sources,
            'destinations' => $destinations,
            'ports' => $ports,
        ]);

        $this->templateId = (string) $tpl->id;
        $this->refreshTemplates();

        Flux::toast('Neue Vorlage wurde angelegt.');
        $this->dispatch('flux-toast', title: 'Gespeichert', description: 'Neue Vorlage wurde angelegt');
    }

    private function ruleGroupsToArrays(): array
    {
        $sources = [];
        $destinations = [];
        $ports = [];

        foreach ($this->ruleGroups as $r) {
            $sources[] = $this->normalizeArrayList($r['sources'] ?? []);
            $destinations[] = $this->normalizeArrayList($r['destinations'] ?? []);
            $ports[] = array_values(array_map(
                fn ($p) => strtolower(trim((string) $p)),
                $r['ports'] ?? []
            ));
        }

        return [$sources, $destinations, $ports];
    }

    private function validateNameAndRules(?int $ignoreId): void
    {
        $this->validate([
            'name' => [
                'required', 'string', 'min:3',
                Rule::unique('firewall_templates', 'name')->ignore($ignoreId),
            ],
        ]);

        if (empty($this->ruleGroups)) {
            $this->addError('ruleGroups', 'Mindestens eine Regel ist erforderlich.');
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages($this->getErrorBag()->toArray());
        }
    }

    public function generate(): void
    {
        $this->emailSubject = "Firewall-Antrag – {$this->name}";

        $blocks = [];
        $this->previewGroups = [];

        foreach ($this->ruleGroups as $r) {
            $sources = $this->normalizeArrayList($r['sources'] ?? []);
            $destinations = $this->normalizeArrayList($r['destinations'] ?? []);
            $ports = array_values($r['ports'] ?? []);

            $portsText = !empty($ports)
                ? implode(', ', $ports)
                : 'Port nicht angegeben';

            if (!empty($sources)) {
                $sourceLines = array_map(
                    fn ($s) => "    {$s}; {$portsText}",
                    $sources
                );
            } else {
                $sourceLines = ["    Quelle nicht angegeben"];
            }

            if (!empty($destinations)) {
                $destinationLines = array_map(
                    fn ($d) => "    {$d}; {$portsText}",
                    $destinations
                );
            } else {
                $destinationLines = ["    Ziel nicht angegeben"];
            }

            $blocks[] =
                "Quelle:\n".
                implode("\n", $sourceLines)."\n\n".
                "Ziele:\n".
                implode("\n", $destinationLines);

            $this->previewGroups[] = [[
                'src'  => !empty($sources) ? implode("\n", $sources) : 'Quelle nicht angegeben',
                'dst'  => !empty($destinations) ? implode("\n", $destinations) : 'Ziel nicht angegeben',
                'port' => $portsText,
            ]];
        }

        $divider = "\n\n------------------------------\n\n";

        $email =
            "Verfahren:\n".
            "    {$this->name}\n\n".
            implode($divider, $blocks);

        if (trim($this->notes) !== '') {
            $email .=
                "\n\nNotizen:\n".
                "    ".trim($this->notes);
        }

        $this->emailBody = $email;

        $this->mailtoUrl =
            'mailto:?subject='.rawurlencode($this->emailSubject).
            '&body='.rawurlencode($this->emailBody);

        $this->emailBodyPreview = $this->emailBody;
        $this->modal('preview-email')->show();
    }




    private function normalizeStoredList(mixed $value): array
    {
        if (is_array($value)) {
            return $this->normalizeArrayList($value);
        }

        if (is_string($value)) {
            $items = preg_split('/[\r\n,]+/', $value) ?: [];
            return array_values(array_filter(array_map('trim', $items), fn ($v) => $v !== ''));
        }

        return [];
    }

    private function normalizeArrayList(array $items): array
    {
        return array_values(array_filter(array_map(
            fn ($v) => trim((string) $v),
            $items
        ), fn ($v) => $v !== ''));
    }
}
