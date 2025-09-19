<div class="w-[80%] md:w-[70%] mx-auto">
    @php
        $colors = ['green','emerald','teal','cyan','sky','blue','indigo','violet','purple','fuchsia','orange','amber','yellow','lime'];
        $namePid = fn($info) => isset($titleCaseName)
            ? $titleCaseName($info)
            : ( (trim(($info['givenname'] ?? '') . ' ' . ($info['surname'] ?? '')) !== '')
                ? trim(($info['givenname'] ?? '') . ' ' . ($info['surname'] ?? '')) . ' (' . ($info['pid'] ?? '—') . ')'
                : ($info['pid'] ?? '—') );
        $groupClasses = 'w-fit max-w-sm truncate hover:whitespace-normal focus:whitespace-normal break-words';
        $b64 = fn (?string $s) => $s === null ? '' : base64_encode($s);
    @endphp

    <flux:card>
        <div class="flex flex-col items-center gap-2">
            <flux:icon.square-user-round class="size-12"/>
            <p>User Suchen</p>

            <div class="flex justify-center w-1/2 space-x-2">
                <flux:input.group class="flex-1">
                    <flux:select wire:model.live="searchAttribute" placeholder="Attribute..." required class="max-w-fit">
                        <flux:select.option value="PID">PID</flux:select.option>
                        <flux:select.option value="Nachname">Nachname</flux:select.option>
                        <flux:select.option value="Vollst. Name">Vollst. Name</flux:select.option>
                        <flux:select.option value="Titel">Stellenzeichen</flux:select.option>
                    </flux:select>

                    @if ($searchAttribute === 'PID')
                        <flux:input.group>
                            <flux:input.group.prefix class="rounded-l-none border-l-0">p</flux:input.group.prefix>
                            <flux:input wire:model.defer="searchTerm" wire:keydown.enter="search" placeholder="12345" inputmode="numeric" pattern="[0-9]*"/>
                        </flux:input.group>
                    @else
                        <div class="relative w-full">
                            <flux:input
                                wire:model.defer="searchTerm"
                                wire:keydown.enter="search"
                                placeholder="{{ $searchAttribute === 'Titel' ? 'z. B. FM IKT 1*' : 'Suchbegriff eingeben...' }}"
                            />
                            <button type="button"
                                    @click="$wire.set('searchTerm','')"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex items-center justify-center rounded px-1.5 py-0.5 text-sm text-gray-500 hover:text-black">
                                ×
                            </button>
                        </div>
                    @endif
                </flux:input.group>
            </div>

            @if ($error)
                <p class="text-red-600 mt-2">{{ $error }}</p>
            @endif

            <flux:button variant="primary" color="green" wire:click="search" type="button" class="cursor-pointer mt-4">
                Suchen
            </flux:button>
        </div>
    </flux:card>

    @if ($searchResults && $searchResults->count() > 0)
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-6">
            <table class="w-full text-sm text-left rtl:text-right text-gray-600 dark:text-gray-200 table-auto max-h-72 overflow-auto bg-gray-50 dark:bg-gray-900/20 rounded p-2 border border-gray-300 dark:border-gray-700">
                <thead class="text-xs text-gray-700 dark:text-gray-100 uppercase bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 w-auto">PID</th>
                    <th class="px-4 py-3 w-auto">Nachname</th>
                    <th class="px-4 py-3 w-auto">Vorname</th>
                    <th class="px-4 py-3 w-auto">Email</th>
                    <th class="px-4 py-3 w-auto">Aktion</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($searchResults as $user)
                    <tr class="{{ $loop->odd ? 'bg-white dark:bg-gray-800/70' : 'bg-gray-50 dark:bg-gray-800/40' }} hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border-b border-gray-200 dark:border-gray-700">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $user['pid'] }}</td>
                        <td class="px-4 py-3">{{ $user['surname'] ?? '–' }}</td>
                        <td class="px-4 py-3">{{ $user['givenname'] ?? '–' }}</td>
                        <td class="px-4 py-3">
                            @if(!empty($user['email']))
                                <flux:badge variant="pill" class="mt-1" color="green">{{ $user['email'] }}</flux:badge>
                            @else
                                <flux:text variant="subtle">nicht vorhanden</flux:text>
                            @endif

                            @if(!empty($user['external_email']))
                                <flux:badge variant="pill" class="mt-1" color="teal">
                                    {{ $user['external_email'] }}
                                    <flux:tooltip toggleable>
                                        <flux:button icon="information-circle" size="sm" variant="ghost"/>
                                        <flux:tooltip.content class="max-w-[20rem] space-y-2">extern</flux:tooltip.content>
                                    </flux:tooltip>
                                </flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap flex gap-2">
                            <flux:button size="xs" variant="primary" color="green" class="cursor-pointer"
                                         wire:click="loadGroupsAndInfo('{{ $user['pid'] }}')">
                                Anzeigen
                            </flux:button>

                            <flux:button size="xs" variant="primary" color="blue" class="cursor-pointer"
                                         wire:click="openCompare('{{ $user['pid'] }}')">
                                Vergleichen
                            </flux:button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <flux:modal name="groups" class="w-content max-w-content max-h-full" :dismissible="false">
        @if ($selectedUserInfo)
            <flux:heading class="flex justify-center">{{ $selectedUserInfo['pid'] }}</flux:heading>

            <flux:text class="mt-2">Nachname: {{ $selectedUserInfo['surname'] ?? '—' }}</flux:text>
            <flux:text class="mt-2">Vorname: {{ $selectedUserInfo['givenname'] ?? '—' }}</flux:text>
            <flux:text class="mt-2">Titel: {{ $selectedUserInfo['title'] ?? '—' }}</flux:text>
            <flux:text class="mt-2">Info: {{ $selectedUserInfo['info'] ?? '—' }}</flux:text>
            <flux:text class="mt-2">
                Letzter Login:
                @php
                    $lastLogin = $selectedUserInfo['lastLogin'] ?? null;
                    try { $loginTime = $lastLogin ? \Carbon\Carbon::parse($lastLogin)->setTimezone('Europe/Berlin') : null; }
                    catch (\Exception $e) { $loginTime = null; }
                @endphp
                {{ $loginTime ? $loginTime->format('d.m.Y H:i') . ' (' . $loginTime->diffForHumans() . ')' : '--' }}
            </flux:text>
            <flux:text class="mt-2">Kontext: {{ $selectedUserInfo['context'] ?? '—' }}</flux:text>
        @endif

        <flux:separator class="mt-3 mb-3">
            Gruppenzugehörigkeiten
            @if($selectedUserGroups != null)
                <flux:badge size="sm" color="lime">{{ count($selectedUserGroups) }}</flux:badge>
            @endif
        </flux:separator>

        @php
            $groupDn = fn($g) => $selectedUserGroupMap[$g] ?? null;
        @endphp

        @if ($selectedUserGroups !== null)
            @if(count($selectedUserGroups) > 0)
                <flux:div copyable class="grid gap-1 mt-2 min-w-fit">
                    @foreach ($selectedUserGroups as $index => $group)
                        @php
                            $dn = $groupDn($group);
                            $dn64 = $b64($dn);
                        @endphp
                        <span class="inline-flex items-center gap-2">
                            <flux:button
                                size="xs"
                                variant="ghost"
                                class="p-0"
                                title="Mitglieder anzeigen"
                                wire:click="openMembersModal('{{ $dn64 }}')">
                                <flux:icon.user-group class="size-4 shrink-0"/>
                            </flux:button>

                            <flux:badge2 copyable variant="pill"
                                         color="{{ $colors[$index % count($colors)] }}"
                                         class="{{ $groupClasses }}"
                                         title="{{ $group }}">
                                {{ $group }}
                            </flux:badge2>
                        </span>
                    @endforeach
                </flux:div>
            @else
                <p>Keine Gruppen gefunden.</p>
            @endif
        @endif
    </flux:modal>

    @php
        $dn = $memberListForDn ?? null;
        $dnKey = $dn ? substr(md5($dn),0,10) : 'none';
        $state = $dn ? ($memberState[$dn] ?? null) : null;
        $page  = $state['page'] ?? null;
        $displayGroupName = null;
        if ($dn) {
            $found = array_search($dn, $selectedUserGroupMap ?? [], true);
            $displayGroupName = $found !== false ? $found : $dn;
        }
        $isSorted = $state['sorted'] ?? false;
        $sortBy = $state['sortBy'] ?? null;
        $sortDir = $state['sortDir'] ?? 'asc';
        $memberCount = is_array($page) && isset($page['rows']) ? count($page['rows']) : 0;
    @endphp

    <flux:modal name="groupMembers" class="w-[92vw] max-w-4xl" :dismissible="true">
        <div class="space-y-3" wire:key="gm-wrap-{{ $dnKey }}-{{ $gmNonce }}" x-data="{ showCopy:false }">
            <div class="flex items-center justify-between gap-3 pr-12">
                <div class="flex items-center gap-2">
                    <flux:heading size="lg" class="flex items-center gap-2">
                        Gruppenmitglieder
                        @if($displayGroupName)
                            <span class="text-gray-500 dark:text-gray-400 font-normal">— {{ $displayGroupName }}</span>
                        @endif
                        <flux:badge size="sm" color="sky">{{ $memberCount }}</flux:badge>
                    </flux:heading>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Kopier-Modus</flux:text>
                    <flux:switch @change="showCopy = $event.target.checked"/>
                </div>
            </div>

            <div class="mb-2 flex items-center gap-2">
                <div class="relative w-full">
                    <flux:input
                        wire:model.live.debounce.300ms="memberSearch"
                        wire:key="gm-search-{{ $dnKey }}"
                        x-ref="memberSearch"
                        x-init="$nextTick(()=> $refs.memberSearch?.focus())"
                        placeholder="Suchen: PID, Stellenzeichen, Vorname, Nachname, Telefon"
                    />
                    <button type="button"
                            @click="$wire.set('memberSearch',''); $nextTick(()=> $refs.memberSearch?.focus())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex items-center justify-center rounded px-2 py-1 text-base leading-none text-gray-500 hover:text-black">
                        ×
                    </button>
                </div>
            </div>

            @if (!$dn)
                <flux:text variant="subtle">Keine Gruppe ausgewählt.</flux:text>
            @elseif (!$page)
                <flux:text variant="subtle">Lade Mitglieder…</flux:text>
            @elseif (($page['rows'] ?? []) === [])
                <flux:text variant="subtle">Keine Mitglieder gefunden.</flux:text>
            @else
                <div
                    x-data="{
                        copied:false,
                        colCopied:{},
                        rowCopied:{},
                        widths:[],
                        clean(s){ return (s ?? '').toString().replace(/\s+/g,' ').trim(); },
                        visibleCellNodes(row){ return [...row.querySelectorAll('th,td')].filter(c => !c.classList.contains('no-copy')); },
                        extractCells(row){ return this.visibleCellNodes(row).map(c => this.clean(c.textContent)); },
                        extractVisibleRows(){
                            const t = $refs.gmTable;
                            if(!t) return { header:[], rows:[], rowEls:[] };
                            const head = t.querySelector('thead tr');
                            const header = head ? this.extractCells(head) : [];
                            const rowEls = [...t.querySelectorAll('tbody tr')].filter(r => r.offsetParent !== null);
                            const rows = rowEls.map(r => this.extractCells(r));
                            return { header, rows, rowEls };
                        },
                        computeWidths(header, rows){
                            const all = [header, ...rows];
                            this.widths = header.map((_,i)=> Math.max(...all.map(r => (r[i] ?? '').length)));
                        },
                        smartJoin(row){
                            return row.map((c,i)=>{
                                const len = (c||'').length;
                                const gap = (this.widths[i]||len) - len;
                                const tabs = gap > 8 ? 2 : 1;
                                return (c||'') + '\t'.repeat(tabs);
                            }).join('');
                        },
                        async buildTableText(header, rows){
                            this.computeWidths(header, rows);
                            const out = [];
                            out.push(this.smartJoin(header));
                            for (let i=0;i<rows.length;i++){
                                out.push(this.smartJoin(rows[i]));
                                if (i % 200 === 199) { await new Promise(requestAnimationFrame); }
                            }
                            return out.join('\n');
                        },
                        async copyTable(){
                            const {header, rows} = this.extractVisibleRows();
                            const text = await this.buildTableText(header, rows);
                            await navigator.clipboard.writeText(text);
                            this.copied = true; setTimeout(()=> this.copied = false, 1200);
                        },
                        async copyRowByKey(key){
                            const tr = document.querySelector(`[data-row-key='${key}']`);
                            if(!tr) return;
                            const {header, rows} = this.extractVisibleRows();
                            this.computeWidths(header, rows);
                            const cols = this.extractCells(tr);
                            const text = this.smartJoin(cols);
                            await navigator.clipboard.writeText(text);
                            this.rowCopied[key] = true; setTimeout(()=> this.rowCopied[key] = false, 1200);
                        },
                        async copyColumnByIndex(idx){
                            const t = $refs.gmTable;
                            if(!t) return;
                            const headRow = t.querySelector('thead tr');
                            const headCells = this.visibleCellNodes(headRow);
                            const header = headCells[idx] ? this.clean(headCells[idx].textContent) : '';
                            const bodyRows = [...t.querySelectorAll('tbody tr')].filter(r => r.offsetParent !== null);
                            const out = [header];
                            for (let i=0;i<bodyRows.length;i++){
                                const cells = this.visibleCellNodes(bodyRows[i]);
                                out.push(cells[idx] ? this.clean(cells[idx].textContent) : '');
                                if (i % 300 === 299) { await new Promise(requestAnimationFrame); }
                            }
                            await navigator.clipboard.writeText(out.join('\n'));
                            this.colCopied[idx] = true; setTimeout(()=> this.colCopied[idx] = false, 1200);
                        }
                    }"
                    class="border border-gray-300 dark:border-gray-700 rounded-md overflow-hidden"
                >
                    <div class="max-h-[65vh] overflow-y-auto">
                        <table class="w-full text-sm" x-ref="gmTable">
                            <thead class="sticky top-0 z-10 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-100">
                                <tr>
                                    <th class="!pl-10 pr-2 py-2 text-left">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1"
                                                    wire:click="setMemberSort('{{ base64_encode($dn) }}','pid')"
                                                    wire:target="setMemberSort"
                                                    wire:loading.attr="disabled">
                                                PID
                                                @if($isSorted && $sortBy === 'pid')
                                                    @if($sortDir === 'asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(0)"
                                                  :data-copyable-copied="colCopied[0] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="px-2 py-2 text-left">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1"
                                                    wire:click="setMemberSort('{{ base64_encode($dn) }}','title')"
                                                    wire:target="setMemberSort"
                                                    wire:loading.attr="disabled">
                                                Stellenzeichen
                                                @if($isSorted && $sortBy === 'title')
                                                    @if($sortDir === 'asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(1)"
                                                  :data-copyable-copied="colCopied[1] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="px-2 py-2 text-left">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1"
                                                    wire:click="setMemberSort('{{ base64_encode($dn) }}','givenname')"
                                                    wire:target="setMemberSort"
                                                    wire:loading.attr="disabled">
                                                Vorname
                                                @if($isSorted && $sortBy === 'givenname')
                                                    @if($sortDir === 'asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(2)"
                                                  :data-copyable-copied="colCopied[2] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="px-2 py-2 text-left">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1"
                                                    wire:click="setMemberSort('{{ base64_encode($dn) }}','surname')"
                                                    wire:target="setMemberSort"
                                                    wire:loading.attr="disabled">
                                                Nachname
                                                @if($isSorted && $sortBy === 'surname')
                                                    @if($sortDir === 'asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(3)"
                                                  :data-copyable-copied="colCopied[3] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="px-2 py-2 text-left">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1"
                                                    wire:click="setMemberSort('{{ base64_encode($dn) }}','tel')"
                                                    wire:target="setMemberSort"
                                                    wire:loading.attr="disabled">
                                                Telefon
                                                @if($isSorted && $sortBy === 'tel')
                                                    @if($sortDir === 'asc') <flux:icon.arrow-up-wide-narrow class="size-3.5"/> @else <flux:icon.arrow-down-wide-narrow class="size-3.5"/> @endif
                                                @endif
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(4)"
                                                  :data-copyable-copied="colCopied[4] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="px-3 py-2 text-left no-copy w-16">
                                        <span :class="showCopy ? 'opacity-100' : 'opacity-0'" class="text-xs text-gray-500 transition-opacity">Aktionen</span>
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="bg-white dark:bg-gray-800/60">
                            @foreach (($page['rows'] ?? []) as $i => $row)
                                @php
                                    $pid = $row['pid'] ?? '—';
                                    $title = ($row['title'] ?? '') !== '' ? $row['title'] : '—';
                                    $v = $row['givenname'] ?: '—';
                                    $n = $row['surname'] ?: '—';
                                    $tel = trim((string)($row['tel'] ?? ''));
                                    $tshow = $tel !== '' ? $tel : '—';
                                    $rk = $dnKey.'-'.($row['pid'] ?? ('i'.$i));
                                @endphp
                                <tr class="{{ $loop->odd ? 'bg-gray-50 dark:bg-gray-800/80' : 'bg-gray-100 dark:bg-gray-800/55' }} hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                                    wire:key="gm-row-{{ $dnKey }}-{{ $row['pid'] ?? ('i'.$i) }}"
                                    data-row-key="{{ $rk }}">
                                    <td class="!pl-10 pr-4 py-2 whitespace-nowrap">
                                        <span x-data="{label: @js($pid)}" x-transition.opacity>
                                            <span @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                                  class="cursor-pointer select-text" x-text="label"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span x-data="{label: @js($title)}" x-transition.opacity>
                                            <span @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                                  class="cursor-pointer select-text" x-text="label"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span x-data="{label: @js($v)}" x-transition.opacity>
                                            <span @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                                  class="cursor-pointer select-text" x-text="label"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span x-data="{label: @js($n)}" x-transition.opacity>
                                            <span @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                                  class="cursor-pointer select-text" x-text="label"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span x-data="{label: @js($tshow)}" x-transition.opacity>
                                            <span @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                                  class="cursor-pointer select-text" x-text="label"></span>
                                        </span>
                                    </td>
                                    <th class="px-3 py-2 whitespace-nowrap no-copy w-16 font-normal">
                                        <span role="button" tabindex="0" title="Zeile kopieren"
                                              @click="copyRowByKey('{{ $rk }}')"
                                              :data-copyable-copied="rowCopied['{{ $rk }}'] ? '' : null"
                                              :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                              class="inline-flex w-5 justify-center items-center cursor-pointer text-gray-500/80 hover:text-black transition-colors">
                                            <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                            <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                        </span>
                                    </th>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-2 p-2 border-t border-gray-200 dark:border-gray-700">
                        <span role="button" tabindex="0"
                              title="Tabelle kopieren"
                              @click="copyTable()"
                              :data-copyable-copied="copied ? '' : null"
                              class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs bg-white/80 dark:bg-gray-800/80 hover:bg-white dark:hover:bg-gray-800 border border-gray-300 dark:border-gray-700 cursor-pointer text-gray-700 hover:text-black transition-colors">
                            <flux:icon.clipboard-document-check variant="mini" class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                            <flux:icon.clipboard-document variant="mini" class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                            <span class="whitespace-nowrap">Tabelle kopieren</span>
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>

    <flux:modal name="compare" class="w-[92vw] max-w-5xl" :dismissible="false">
        <div class="space-y-4 pr-12">
            <div class="flex items-center gap-3 text-center min-w-0">
                <flux:heading size="lg" class="leading-none">Gruppenvergleich</flux:heading>
                <flux:badge variant="pill" class="truncate max-w-[60%] leading-none">Basis: {{ $compareBasePid ?? '—' }}</flux:badge>
            </div>

            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <flux:text class="mb-1">Zweite PID</flux:text>
                    <flux:input.group>
                        <flux:input.group.prefix>p</flux:input.group.prefix>
                        <flux:input
                            wire:model.defer="compareOtherPidInput"
                            wire:keydown.enter="runCompare"
                            placeholder="12345"
                            inputmode="numeric"
                            pattern="[0-9]*"
                        />
                    </flux:input.group>
                </div>
                <flux:button variant="primary" color="blue" class="cursor-pointer" wire:click="runCompare">
                    Vergleichen
                </flux:button>
            </div>

            @if ($compareError)
                <p class="text-red-600">{{ $compareError }}</p>
            @endif

            @if ($compareGroups)
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <flux:button size="sm" :variant="$compareView === 'user1' ? 'primary' : 'ghost'" color="lime" class="cursor-pointer" wire:click="setCompareView('user1')">
                        Benutzer&nbsp;1: {{ $namePid($compareBaseInfo) }}
                        <flux:badge size="xs" class="ml-2">{{ $compareGroups['count_first'] }}</flux:badge>
                    </flux:button>

                    <flux:button size="sm" :variant="$compareView === 'user2' ? 'primary' : 'ghost'" color="sky" class="cursor-pointer" wire:click="setCompareView('user2')">
                        Benutzer&nbsp;2: {{ $namePid($compareOtherInfo ?: ['pid' => $compareOtherPid]) }}
                        <flux:badge size="xs" class="ml-2">{{ $compareGroups['count_second'] }}</flux:badge>
                    </flux:button>

                    <flux:button size="sm" :variant="$compareView === 'common' ? 'primary' : 'ghost'" color="violet" class="cursor-pointer" wire:click="setCompareView('common')">
                        Gemeinsam
                        <flux:badge size="xs" class="ml-2">{{ count($compareGroups['common']) }}</flux:badge>
                    </flux:button>

                    <flux:button size="sm" :variant="$compareView === 'diffs' ? 'primary' : 'ghost'" color="orange" class="cursor-pointer" wire:click="setCompareView('diffs')">
                        Unterschiede
                        <flux:badge size="xs" class="ml-2">{{ count($compareGroups['only_first']) + count($compareGroups['only_second']) }}</flux:badge>
                    </flux:button>
                </div>

                <div class="mt-4">
                    @if ($compareView === 'user1')
                        <flux:card>
                            <flux:heading size="sm">{{ $namePid($compareBaseInfo) }}</flux:heading>
                            <flux:div copyable class="mt-2 space-x-2 space-y-2">
                                @forelse ($compareGroups['all_first'] as $i => $g)
                                    <flux:badge2 variant="pill" color="{{ $colors[$i % count($colors)] }}" class="{{ $groupClasses }}" title="{{ $g }}">
                                        {{ $g }}
                                    </flux:badge2>
                                @empty
                                    <flux:text variant="subtle">—</flux:text>
                                @endforelse
                            </flux:div>
                        </flux:card>
                    @elseif ($compareView === 'user2')
                        <flux:card>
                            <flux:heading size="sm">{{ $namePid($compareOtherInfo ?: ['pid' => $compareOtherPid]) }}</flux:heading>
                            <flux:div copyable class="mt-2 space-x-2 space-y-2">
                                @forelse ($compareGroups['all_second'] as $i => $g)
                                    <flux:badge2 variant="pill" color="{{ $colors[$i % count($colors)] }}" class="{{ $groupClasses }}" title="{{ $g }}">
                                        {{ $g }}
                                    </flux:badge2>
                                @empty
                                    <flux:text variant="subtle">—</flux:text>
                                @endforelse
                            </flux:div>
                        </flux:card>
                    @elseif ($compareView === 'common')
                        <flux:card>
                            <flux:heading size="sm">Gemeinsame Gruppen</flux:heading>
                            <flux:div copyable class="mt-2 space-x-2 space-y-2">
                                @forelse ($compareGroups['common'] as $i => $g)
                                    <flux:badge2 variant="pill" color="{{ $colors[$i % count($colors)] }}" class="{{ $groupClasses }}" title="{{ $g }}">
                                        {{ $g }}
                                    </flux:badge2>
                                @empty
                                    <flux:text variant="subtle">—</flux:text>
                                @endforelse
                            </flux:div>
                        </flux:card>
                    @elseif ($compareView === 'diffs')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:card>
                                <flux:heading size="sm">{{ $namePid($compareBaseInfo) }}</flux:heading>
                                <flux:div copyable class="mt-2 space-x-2 space-y-2">
                                    @forelse ($compareGroups['only_first'] as $i => $g)
                                        <flux:badge2 variant="pill" color="{{ $colors[$i % count($colors)] }}" class="{{ $groupClasses }}" title="{{ $g }}">
                                            {{ $g }}
                                        </flux:badge2>
                                    @empty
                                        <flux:text variant="subtle">—</flux:text>
                                    @endforelse
                                </flux:div>
                            </flux:card>

                            <flux:card>
                                <flux:heading size="sm">{{ $namePid($compareOtherInfo ?: ['pid' => $compareOtherPid]) }}</flux:heading>
                                <flux:div copyable class="mt-2 space-x-2 space-y-2">
                                    @forelse ($compareGroups['only_second'] as $i => $g)
                                        <flux:badge2 variant="pill" color="{{ $colors[$i % count($colors)] }}" class="{{ $groupClasses }}" title="{{ $g }}">
                                            {{ $g }}
                                        </flux:badge2>
                                    @empty
                                        <flux:text variant="subtle">—</flux:text>
                                    @endforelse
                                </flux:div>
                            </flux:card>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </flux:modal>
</div>
