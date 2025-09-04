{{-- resources/views/livewire/firewall-vorlagen.blade.php --}}
<div class="max-w-3xl mx-auto py-8" xmlns:flux="http://www.w3.org/1999/html">
    <flux:card>
        <flux:heading size="xl">Firewall-Vorlagen</flux:heading>

        {{-- Verfahren auswählen --}}
        <div class="mt-6">
            <flux:field>
                <flux:label>Verfahren auswählen</flux:label>
                <flux:select
                    variant="listbox"
                    searchable
                    placeholder="Vorlage suchen oder wählen…"
                    wire:model="templateId"
                    wire:change="handleTemplateSelect"
                >
                    <flux:select.option value="">➕ Neues Verfahren anlegen…</flux:select.option>
                    @foreach($this->templates as $t)
                        <flux:select.option value="{{ (string)$t['id'] }}">{{ $t['name'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:text size="sm" class="opacity-70 mt-1">
                    Tipp: Tippe zum Suchen. Wenn nicht vorhanden, „Neues Verfahren anlegen…“ wählen und unten Namen setzen.
                </flux:text>
            </flux:field>
        </div>

        {{-- Name des Verfahrens --}}
        <div class="mt-4">
            <flux:field>
                <flux:label>Name des Verfahrens</flux:label>
                <flux:input wire:model.defer="name" placeholder="Nettools" />
            </flux:field>
        </div>

        {{-- Regeln (Accordion, dynamisch) --}}
        <div class="mt-6">
            <flux:accordion>
                @foreach($this->ruleGroups as $i => $rule)
                    {{-- Only the item where index matches $expandedIndex is expanded --}}
                    <flux:accordion.item expanded>
                        <flux:accordion.heading class="flex items-center gap-2">
    <flux:badge
        variant="pill"
        icon="brick-wall-fire"
        :color="['teal','sky','indigo'][$i % 3]">
        Regel {{ $i + 1 }}
    </flux:badge>
</flux:accordion.heading>

                        <flux:accordion.content>
                            <div class="space-y-6">
                                {{-- Quellen (optional) --}}
                                <flux:field>
                                    <flux:label>Quelle(n)</flux:label>
                                    <flux:textarea2
                                        copyable
                                        wire:model.defer="ruleGroups.{{ $i }}.sourcesText"
                                        rows="4"
                                        placeholder="10.93.xx.xx"
                                    />
                                    <flux:text size="sm" class="opacity-70 mt-1">
                                        Ein Eintrag pro Zeile (oder durch Kommas getrennt).
                                    </flux:text>
                                </flux:field>

                                {{-- Ziele --}}
                                <flux:field>
                                    <flux:label>Ziel(e)</flux:label>
                                    <flux:textarea2
                                        copyable
                                        wire:model.defer="ruleGroups.{{ $i }}.destinationsText"
                                        rows="4"
                                        placeholder="10.93.15.14&#10;vs508"
                                    />
                                </flux:field>

                                {{-- Ports / Protokolle  --}}
                                <flux:field label="Ports &amp; Protokolle">
                                    {{-- Searchable select that adds on change --}}
                                    <div class="max-w-full">
                                        <flux:select
                                            variant="listbox"
                                            searchable
                                            placeholder="Port suchen (HTTP, HTTPS, DNS, …)"
                                            wire:model="ruleGroups.{{ $i }}.portSelect"
                                            wire:change="addPortFromSelect({{ $i }})"
                                            class="w-full"
                                        >
                                            @foreach($this->portCatalog as $opt)
                                                <flux:select.option value="{{ $opt['value'] }}">
                                                    {{ $opt['label'] }} ({{ $opt['value'] }})
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>

                                    {{-- Benutzerdefiniert hinzufügen --}}
                                    <div class="mt-3 flex gap-2">
                                        <flux:input
                                            wire:model.defer="ruleGroups.{{ $i }}.portInput"
                                            placeholder="z. B. 8443/tcp"
                                            class="flex-1"
                                        />
                                        <flux:button wire:click="addCustomPort({{ $i }})" icon="plus">Hinzufügen</flux:button>
                                    </div>

                                    {{-- Ausgewählte Ports --}}
                                    <div class="mt-3 flex flex-wrap gap-2 max-h-40 overflow-auto pr-1">
                                        @forelse($this->ruleGroups[$i]['ports'] as $p)
                                            @php $label = $this->portLabelMap[$p] ?? null; @endphp
                                            <flux:badge>
                                                <span class="mr-2">
                                                    {{ $label ? ($label.' ('.$p.')') : $p }}
                                                </span>
                                                <flux:button
                                                    variant="ghost"
                                                    size="sm"
                                                    wire:click="removePort({{ $i }}, '{{ $p }}')"
                                                    title="Entfernen: {{ $p }}"
                                                >✕</flux:button>
                                            </flux:badge>
                                        @empty
                                            <flux:text size="sm" class="opacity-70">Noch keine Ports hinzugefügt.</flux:text>
                                        @endforelse
                                    </div>
                                </flux:field>

                                @if(count($this->ruleGroups) > 1)
                                    <div class="flex justify-end">
                                        <flux:button variant="ghost" icon="trash" wire:click="removeRule({{ $i }})">
                                            Regel {{ $i + 1 }} entfernen
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        </flux:accordion.content>
                    </flux:accordion.item>
                @endforeach
            </flux:accordion>
        </div>

        {{-- Regel hinzufügen --}}
        <div class="mt-6">
            <flux:button icon="plus" wire:click="addRule">Regel hinzufügen</flux:button>
        </div>

        {{-- Aktionen --}}
        <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <flux:button wire:click="saveTemplate" icon="save">Speichern</flux:button>
            <flux:button wire:click="saveAsTemplate" icon="save">Speichern als neu</flux:button>
            <flux:button wire:click="generate" variant="primary" color="green" icon="paper-airplane">E-Mail erzeugen</flux:button>
        </div>
    </flux:card>

    {{-- Vorschau: E-Mail --}}
    <flux:modal name="preview-email" class="md:w-[820px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">E-Mail-Vorschau</flux:heading>
                <flux:text class="mt-1 opacity-70">
                    Kopiere den Text unten oder öffne direkt deinen E-Mail-Client.
                </flux:text>
            </div>

            <flux:textarea2 copyable rows="18" readonly class="font-mono">{{  $emailBodyPreview }} </flux:textarea2>
            {{-- Tabellen je Regel --}}
            <div class="space-y-6">
                @forelse($this->previewGroups as $idx => $rows)
                    <div>
                        <flux:heading size="sm">Regel {{ $idx + 1 }}</flux:heading>
                        <div class="overflow-x-auto mt-2">
                            <table class="min-w-full text-sm border border-gray-300 rounded">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="text-left p-2 border-b">Quelle(n)</th>
                                        <th class="text-left p-2 border-b">Ziel(e)</th>
                                        <th class="text-left p-2 border-b">Port/Protokoll</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($rows as $r)
                                    <tr>
                                        <td class="p-2 border-b align-top">{{ $r['src'] }}</td>
                                        <td class="p-2 border-b align-top">{{ $r['dst'] }}</td>
                                        <td class="p-2 border-b align-top">{{ $r['port'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="p-2" colspan="3">Keine Einträge</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <flux:text class="opacity-70">Keine Regeln vorhanden.</flux:text>
                @endforelse
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:button as="a" href="{{ $this->mailtoUrl }}" color="green" target="_blank" rel="noopener" variant="primary" icon="paper-airplane">
                    Im E-Mail-Client öffnen
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
