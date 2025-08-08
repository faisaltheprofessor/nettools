<div class="w-[80%] md:w-[70%] mx-auto">
    <flux:card>
        <div class="flex flex-col items-center gap-2">
            <flux:icon.user-x class="size-12"/>
            <p>User-ID Karteileichen</p>
            <flux:text class="mt-2">Folgende Filter werden für die Ausgabe der Karteileichen herangezogen:
                keine Benutzersperre, letzter Login älter als 180 Tage (ab aktuelles Datum),
                Ausschluss "DeaktivierteUser", User-ID Range p10000-p19999.
            </flux:text>

            <flux:text class="mt-2">
                Vor der endgültigen Deaktivierung dieser Benutzer ist dringend Rücksprache
                mit den jeweiligen Fachbereichen zu halten. Die Ausgabe ist darüber hinaus bitte
                immer mit der "User-ID" nachzuprüfen!
            </flux:text>


            <flux:button
                variant="primary"
                color="green"
                type="button"
                class="cursor-pointer"
                wire:click="getInactiveUsers"
            >
                Exportieren
            </flux:button>

        </div>
    </flux:card>
</div>
