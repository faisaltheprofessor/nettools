<div class="w-[80%] md:w-[70%] mx-auto h-screen">
    <flux:card class="h-full max-h-screen flex flex-col pb-4">
        <div class="flex flex-col items-center gap-2">
            <flux:icon.file-up class="size-12"/>
            <p>P-ID Export</p>

            <div class="flex justify-center">
                <flux:input.group>
                    <flux:select wire:model="sortDirection" placeholder="Sortierung" class="max-w-fit">
                        <flux:select.option value="desc">letzte</flux:select.option>
                        <flux:select.option value="asc">erste</flux:select.option>
                    </flux:select>

                    <flux:select wire:model="pidCount" placeholder="Anzahl..." required class="max-w-fit">
                        <flux:select.option value="20">20</flux:select.option>
                        <flux:select.option value="50">50</flux:select.option>
                        <flux:select.option value="100">100</flux:select.option>
                        <flux:select.option value="250">250</flux:select.option>
                        <flux:select.option value="Alle">Alle</flux:select.option>
                    </flux:select>

                    <flux:select wire:model="exportMode" variant="listbox" placeholder="Export Modus">
                        <flux:select.option value="table">
                            <div class="flex items-center gap-2">
                                <flux:icon.table-cells variant="mini" class="text-zinc-400"/>
                                als Tabelle
                            </div>
                        </flux:select.option>
                        <flux:select.option value="txt">
                            <div class="flex items-center gap-2">
                                <flux:icon.file-text variant="mini" class="text-zinc-400"/>
                                als Text-Datei
                            </div>
                        </flux:select.option>
                        <flux:select.option value="csv">
                            <div class="flex items-center gap-2">
                                <flux:icon.sheet variant="mini" class="text-zinc-400"/>
                                als CSV-Datei
                            </div>
                        </flux:select.option>
                    </flux:select>

                    <flux:select
                        variant="listbox"
                        multiple
                        clearable
                        searchable
                        placeholder="LDAP-Felder auswählen..."
                        wire:model="selectedFields"
                        class="max-w-fit"
                    >
                        <flux:select.option value="givenname">Vorname</flux:select.option>
                        <flux:select.option value="surname">Nachname</flux:select.option>
                        <flux:select.option value="dn">Kontext</flux:select.option>
                        <flux:select.option value="logintime">Letzter Login</flux:select.option>
                        <flux:select.option value="mail">E-Mail</flux:select.option>
                    </flux:select>

                    <flux:button
                        variant="primary"
                        color="green"
                        wire:click="exportPids"
                        type="button"
                        class="cursor-pointer"
                    >
                        Fortfahren
                    </flux:button>
                </flux:input.group>
            </div>

            @if ($error)
                <p class="text-red-600 text-sm mt-2">{{ $error }}</p>
            @endif

            <flux:separator text="oder" class="mt-4 mb-4"/>

            <div class="flex items-center justify-center">
                <flux:button
                    variant="primary"
                    color="teal"
                    type="button"
                    class="cursor-pointer"
                    wire:click="getInactiveUsers"
                >
                    Alle P-ID Karteileichen exportieren
                </flux:button>
                <flux:tooltip toggleable>
                    <flux:button icon="information-circle" size="sm" variant="ghost"/>
                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                        <p>Folgende Filter werden für die Ausgabe der Karteileichen herangezogen: keine Benutzersperre,
                            letzter Login älter als 180 Tage (ab aktuelles Datum), Ausschluss "DeaktivierteUser",
                            User-ID Range p10000-p19999.</p>
                        <p>Vor der endgültigen Deaktivierung dieser Benutzer ist dringend Rücksprache mit den jeweiligen
                            Fachbereichen zu halten. Die Ausgabe ist darüber hinaus bitte immer mit der "User-ID"
                            nachzuprüfen!</p>
                    </flux:tooltip.content>
                </flux:tooltip>
            </div>
        </div>

        @if (!empty($exportOutput) && $exportMode === 'table')
            <div
                class="mt-6 flex-1 min-h-0 flex flex-col"
                wire:key="exp-{{ md5(implode('|',$selectedFields).'|'.$pidCount.'|'.$sortDirection.'|'.count($exportOutput)) }}"
                x-data="exportTable({
                    rows: @entangle('exportOutput'),
                    cols: @entangle('selectedFields'),
                    names: @js($fieldDisplayNames)
                })"
            >
                <div class="flex items-center justify-between gap-3">
                    <flux:heading size="sm" class="text-gray-700 dark:text-gray-100">Ergebnis</flux:heading>

                    <div class="flex items-center gap-2 shrink-0">
                        <div class="">
                            <div class="flex justify-end">
                        <span role="button" tabindex="0"
                              title="Tabelle kopieren"
                              @click="copyTable()"
                              :data-copyable-copied="copied ? '' : null"
                              class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-xs bg-white/90 dark:bg-gray-800/90 hover:bg-white dark:hover:bg-gray-800 border border-gray-300 dark:border-gray-700 cursor-pointer text-gray-700 hover:text-black shadow-sm">
                            <flux:icon.clipboard-document-check variant="mini"
                                                                class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                            <flux:icon.clipboard-document variant="mini"
                                                          class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                            <span class="whitespace-nowrap">Tabelle kopieren</span>
                        </span>
                            </div>
                        </div>
                        <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Kopier-Modus</flux:text>
                        <flux:switch @change="showCopy = $event.target.checked"/>
                    </div>
                </div>

                <div class="mb-2 mt-2 flex items-center gap-2">
                    <div class="relative w-full">
                        <flux:input
                            x-model.debounce.250ms="q"
                            x-ref="expSearch"
                            placeholder="Suchen: P-ID, Vorname, Nachname, E-Mail, Kontext, Letzter Login"
                        />
                        <button type="button"
                                @click="q=''; $nextTick(()=> $refs.expSearch?.focus())"
                                class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex items-center justify-center rounded px-2 py-1 text-base leading-none text-gray-500 hover:text-black">
                            ×
                        </button>
                    </div>
                </div>

                <div
                    class="relative shadow-md sm:rounded-lg border border-gray-300 dark:border-gray-700 flex-1 min-h-0 flex flex-col">
                    <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto">
                        <table x-ref="expTable"
                               class="w-full text-sm text-left text-gray-600 dark:text-gray-200 bg-gray-50 dark:bg-gray-900/20">
                            <thead
                                class="sticky top-0 z-10 text-xs text-gray-700 dark:text-gray-100 uppercase bg-gray-100 dark:bg-gray-800">
                            <tr>
                                <th class="!pl-10 pr-2 py-3">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                                x-on:click="toggle('uid')">
                                            P-ID
                                            <template x-if="sortKey==='uid' && asc">
                                                <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                            </template>
                                            <template x-if="sortKey==='uid' && !asc">
                                                <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                            </template>
                                        </button>
                                        <span role="button" tabindex="0" title="Spalte kopieren"
                                              @click="copyColumnByIndex(0)"
                                              :data-copyable-copied="colCopied[0] ? '' : null"
                                              :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                              class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                            <flux:icon.clipboard-document-check variant="mini"
                                                                                class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                            <flux:icon.clipboard-document variant="mini"
                                                                          class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                        </span>
                                    </div>
                                </th>

                                <template x-for="(c, i) in cols" :key="'h-'+c">
                                    <th class="px-2 py-3">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer"
                                                    x-on:click="toggle(c)">
                                                <span x-text="names[c] ?? c"></span>
                                                <template x-if="sortKey===c && asc">
                                                    <flux:icon.arrow-up-wide-narrow class="size-3.5"/>
                                                </template>
                                                <template x-if="sortKey===c && !asc">
                                                    <flux:icon.arrow-down-wide-narrow class="size-3.5"/>
                                                </template>
                                            </button>
                                            <span role="button" tabindex="0" title="Spalte kopieren"
                                                  @click="copyColumnByIndex(i+1)"
                                                  :data-copyable-copied="colCopied[i+1] ? '' : null"
                                                  :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                                  class="inline-flex w-5 justify-center cursor-pointer no-copy text-gray-500/80 hover:text-black transition-colors">
                                                <flux:icon.clipboard-document-check variant="mini"
                                                                                    class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                                <flux:icon.clipboard-document variant="mini"
                                                                              class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                            </span>
                                        </div>
                                    </th>
                                </template>

                                <th class="px-3 py-3 no-copy w-16">
                                    <span :class="showCopy ? 'opacity-100' : 'opacity-0'"
                                          class="text-xs text-gray-500 transition-opacity">Aktionen</span>
                                </th>
                            </tr>
                            </thead>

                            <tbody class="bg-white dark:bg-gray-800/60">
                            <template x-for="(r, ri) in displayed()" :key="(r.uid || '')+'-'+ri">
                                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    :class="ri%2 ? 'bg-gray-50 dark:bg-gray-800/40' : 'bg-white dark:bg-gray-800/70'"
                                    :data-exp-row="'rk-'+ri+'-'+(r.uid||ri)">
                                    <td class="!pl-10 pr-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                        <span x-data="{label: r.uid || '—'}" x-transition.opacity>
                                            <span
                                                @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                                class="cursor-pointer select-text" x-text="label"></span>
                                        </span>
                                    </td>

                                    <template x-for="c in cols" :key="'c-'+c+'-'+(r.uid||ri)">
                                        <td class="px-4 py-3">
                                            <span x-data="{label: (r[c] ?? '—')}" x-transition.opacity>
                                                <span
                                                    @click="if (label !== '—') { navigator.clipboard.writeText(label); const o=label; label='Kopiert 💐'; setTimeout(()=>label=o,1200); }"
                                                    class="cursor-pointer select-text" x-text="label"></span>
                                            </span>
                                        </td>
                                    </template>

                                    <td class="px-3 py-3 whitespace-nowrap no-copy w-16">
                                        <span role="button" tabindex="0" title="Zeile kopieren"
                                              @click="copyRowByKey('rk-'+ri+'-'+(r.uid||ri))"
                                              :data-copyable-copied="rowCopied['rk-'+ri+'-'+(r.uid||ri)] ? '' : null"
                                              :class="showCopy ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                              class="inline-flex w-5 justify-center items-center cursor-pointer text-gray-500/80 hover:text-black transition-colors">
                                            <flux:icon.clipboard-document-check variant="mini"
                                                                                class="hidden size-4 [[data-copyable-copied]>&]:block"/>
                                            <flux:icon.clipboard-document variant="mini"
                                                                          class="block size-4 [[data-copyable-copied]>&]:hidden"/>
                                        </span>
                                    </td>
                                </tr>
                            </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </flux:card>
</div>

<script>
    function exportTable({rows, cols, names}) {
        return {
            rows, cols, names,
            q: '',
            showCopy: false, copied: false, colCopied: {}, rowCopied: {},
            sortKey: 'uid', asc: true,
            init() {
                this.$watch('rows', (v) => {
                    if (Array.isArray(v) && v.length) {
                        requestAnimationFrame(() => window.scrollTo({
                            top: document.documentElement.scrollHeight,
                            behavior: 'smooth'
                        }));
                    }
                });
                if (Array.isArray(this.rows) && this.rows.length) {
                    requestAnimationFrame(() => window.scrollTo({
                        top: document.documentElement.scrollHeight,
                        behavior: 'smooth'
                    }));
                }
            },
            toggle(k) {
                this.sortKey === k ? this.asc = !this.asc : (this.sortKey = k, this.asc = true)
            },
            val(v, k) {
                if (k === 'uid' && typeof v === 'string') {
                    const m = v.match(/^p(\d+)$/i);
                    return m ? parseInt(m[1], 10) : v
                }
                if (k === 'logintime' && typeof v === 'string') {
                    const t = v.split('.');
                    if (t.length === 3) {
                        return new Date(t[2], t[1] - 1, t[0]).getTime()
                    }
                }
                return (v ?? '').toString().toLowerCase()
            },
            filtered() {
                const q = (this.q || '').toString().trim().toLowerCase()
                if (!q) return Array.isArray(this.rows) ? [...this.rows] : []
                const keys = ['uid', ...this.cols]
                return (Array.isArray(this.rows) ? this.rows : []).filter(r => {
                    for (const k of keys) {
                        const val = (r?.[k] ?? '').toString().toLowerCase()
                        if (val.includes(q)) return true
                    }
                    return false
                })
            },
            displayed() {
                const a = this.filtered()
                a.sort((x, y) => {
                    const vx = this.val(x[this.sortKey] ?? (this.sortKey === 'uid' ? x.uid : null), this.sortKey)
                    const vy = this.val(y[this.sortKey] ?? (this.sortKey === 'uid' ? y.uid : null), this.sortKey)
                    if (vx < vy) return this.asc ? -1 : 1
                    if (vx > vy) return this.asc ? 1 : -1
                    return 0
                })
                return a
            },
            clean(s) {
                return (s ?? '').toString().replace(/\s+/g, ' ').trim()
            },
            visibleCellNodes(row) {
                return [...row.querySelectorAll('th,td')].filter(c => !c.classList.contains('no-copy'))
            },
            extractCells(row) {
                return this.visibleCellNodes(row).map(c => this.clean(c.textContent))
            },
            extractVisibleRows() {
                const t = this.$refs.expTable
                if (!t) return {header: [], rows: [], rowEls: []}
                const head = t.querySelector('thead tr')
                const header = head ? this.extractCells(head) : []
                const rowEls = [...t.querySelectorAll('tbody tr')].filter(r => r.offsetParent !== null)
                const rows = rowEls.map(r => this.extractCells(r))
                return {header, rows, rowEls}
            },
            widths: [],
            computeWidths(header, rows) {
                const all = [header, ...rows]
                this.widths = header.map((_, i) => Math.max(...all.map(r => (r[i] ?? '').length)))
            },
            smartJoin(row) {
                return row.map((c, i) => {
                    const len = (c || '').length
                    const gap = (this.widths[i] || len) - len
                    const tabs = gap > 8 ? 2 : 1
                    return (c || '') + '\t'.repeat(tabs)
                }).join('')
            },
            async buildTableText(header, rows) {
                this.computeWidths(header, rows)
                const out = []
                out.push(this.smartJoin(header))
                for (let i = 0; i < rows.length; i++) {
                    out.push(this.smartJoin(rows[i]))
                    if (i % 200 === 199) {
                        await new Promise(requestAnimationFrame)
                    }
                }
                return out.join('\n')
            },
            async copyTable() {
                const {header, rows} = this.extractVisibleRows()
                const text = await this.buildTableText(header, rows)
                await navigator.clipboard.writeText(text)
                this.copied = true;
                setTimeout(() => this.copied = false, 1200)
            },
            async copyRowByKey(key) {
                const tr = document.querySelector(`[data-exp-row='${key}']`)
                if (!tr) return
                const {header, rows} = this.extractVisibleRows()
                this.computeWidths(header, rows)
                const cols = this.extractCells(tr)
                const text = this.smartJoin(cols)
                await navigator.clipboard.writeText(text)
                this.rowCopied[key] = true;
                setTimeout(() => this.rowCopied[key] = false, 1200)
            },
            async copyColumnByIndex(idx) {
                const t = this.$refs.expTable
                if (!t) return
                const headRow = t.querySelector('thead tr')
                const headCells = this.visibleCellNodes(headRow)
                const header = headCells[idx] ? this.clean(headCells[idx].textContent) : ''
                const bodyRows = [...t.querySelectorAll('tbody tr')].filter(r => r.offsetParent !== null)
                const out = [header]
                for (let i = 0; i < bodyRows.length; i++) {
                    const cells = this.visibleCellNodes(bodyRows[i])
                    out.push(cells[idx] ? this.clean(cells[idx].textContent) : '')
                    if (i % 300 === 299) {
                        await new Promise(requestAnimationFrame)
                    }
                }
                await navigator.clipboard.writeText(out.join('\n'))
                this.colCopied[idx] = true;
                setTimeout(() => this.colCopied[idx] = false, 1200)
            }
        }
    }
</script>
