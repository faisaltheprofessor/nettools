
    <flux:tab.group>
        <div class="flex justify-center">
            <flux:tabs variant="segmented">
                <flux:tab name="user-search" icon="user-round-search">Usersuche</flux:tab>
                <flux:tab name="user" icon="user">User PID</flux:tab>
                <flux:tab name="pid-gaps" icon="user">User PID Lücken</flux:tab>
                <flux:tab name="user-export" icon="file-up">PIDs Exportieren</flux:tab>
                <flux:tab name="mailbox" icon="envelope">Mailbox PID</flux:tab>
                @if(in_array(auth()->user()->username, config('users.ldap_raw')))
                    <flux:tab name="ldap-raw" icon="brackets">Raw</flux:tab>
                @endif

            </flux:tabs>
        </div>

        <flux:tab.panel name="mailbox">
            <livewire:ldap.next-mailbox-pid />
        </flux:tab.panel>

        <flux:tab.panel name="user">
            <livewire:ldap.next-user-pid />
        </flux:tab.panel>

        <flux:tab.panel name="pid-gaps">
            <livewire:ldap.user-pid-gap />
        </flux:tab.panel>

        <flux:tab.panel name="user-export">
            <livewire:ldap.user-export />
        </flux:tab.panel>

        <flux:tab.panel name="user-search">
            <livewire:ldap.user-search />
        </flux:tab.panel>

        @if(in_array(auth()->user()->username, config('users.ldap_raw')))
            <flux:tab.panel name="ldap-raw">
                <livewire:ldap.ldap-raw />
            </flux:tab.panel>
        @endif
    </flux:tab.group>
