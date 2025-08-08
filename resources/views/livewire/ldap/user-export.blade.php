<div class="w-[80%] md:w-[70%] mx-auto">
    <flux:card>
        <div class="flex flex-col items-center gap-2">
            <flux:icon.file-up class="size-12"/>
            <p>P-ID Export</p>

            <div class="flex justify-center">
                <flux:input.group>
                    <flux:select disabled placeholder="Sortierung" class="max-w-fit">
                        <flux:select.option selected>letzte</flux:select.option>
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
                    Alle P-ID Kartelieichen exportieren
                </flux:button>
                <flux:tooltip toggleable>
                    <flux:button icon="information-circle" size="sm" variant="ghost"/>
                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                        <p>
                            Folgende Filter werden für die Ausgabe der Karteileichen herangezogen: keine Benutzersperre, letzter Login älter als 180 Tage (ab aktuelles Datum), Ausschluss "DeaktivierteUser", User-ID Range p10000-p19999.
                        </p>
                        <p>
                            Vor der endgültigen Deaktivierung dieser Benutzer ist dringend Rücksprache mit den jeweiligen Fachbereichen zu halten. Die Ausgabe ist darüber hinaus bitte immer mit der "User-ID" nachzuprüfen!
                        </p>
                    </flux:tooltip.content>
                </flux:tooltip>
            </div>

            @if (!empty($exportOutput) && $exportMode === 'table')
                <div class="overflow-x-auto mt-4 w-full">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>P-ID</flux:table.column>
                            @foreach ($selectedFields as $field)
                                <flux:table.column>
                                    {{ $fieldDisplayNames[$field] ?? ucfirst($field) }}
                                </flux:table.column>
                            @endforeach
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($exportOutput as $row)
                                <flux:table.row>
                                    <flux:table.cell>{{ $row['uid'] }}</flux:table.cell>
                                    @foreach ($selectedFields as $field)
                                        <flux:table.cell>{{ $row[$field] ?? '' }}</flux:table.cell>
                                    @endforeach
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif

        </div>
    </flux:card>
</div>
