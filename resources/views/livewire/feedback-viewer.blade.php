<flux:card class="mt-4 space-y-4">
    {{-- Table container with fixed height --}}
    <div class="overflow-auto" style="min-height: 300px;">
        <flux:table :paginate="$feedbacks">
            <flux:table.columns>
                <flux:table.column
                    wire:click="sortBy('type')"
                    sortable
                    :direction="$sortField === 'type' ? $sortDirection : null" class="px-2">
                    Type
                </flux:table.column>

                <flux:table.column
                    wire:click="sortBy('user.name')"
                    sortable
                    :direction="$sortField === 'user.name' ? $sortDirection : null">
                    User
                </flux:table.column>

                <flux:table.column
                    wire:click="sortBy('created_at')"
                    sortable
                    :direction="$sortField === 'created_at' ? $sortDirection : null">
                    Date
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($feedbacks as $feedback)
                    <flux:table.row
                        wire:click="selectFeedback({{ $feedback->id }})"
                        class="cursor-pointer hover:bg-gray-100">
                        <flux:table.cell>{{ ucfirst($feedback->type) }}</flux:table.cell>
                        <flux:table.cell>{{ $feedback->user->name ?? 'Unknown' }}</flux:table.cell>
                        <flux:table.cell>{{ $feedback->created_at->format('Y-m-d H:i') }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Selected Feedback Display --}}
    @if ($selectedFeedback)
        <flux:card>
            {{-- Meta Info --}}
            <div class="mb-3 text-sm  space-y-1">
                <div><span class="font-semibold">Type:</span> {{ ucfirst($selectedFeedback->type) }}</div>
                <div><span class="font-semibold">User:</span> {{ $selectedFeedback->user->name ?? 'Unknown' }}</div>
                <div><span class="font-semibold">Date:</span> {{ $selectedFeedback->created_at->format('Y-m-d H:i') }}</div>
            </div>

            {{-- Title & Description --}}
            <h2 class="text-xl font-bold mb-1">{{ $selectedFeedback->title }}</h2>
            <p class="mb-4 ">{{ $selectedFeedback->description }}</p>

            {{-- Attachments --}}
            @php
                $attachments = $selectedFeedback->attachments;
                if (!is_array($attachments)) {
                    $attachments = json_decode($attachments ?? '[]', true) ?: [];
                }
            @endphp

            @if (count($attachments) > 0)
                <div class="flex flex-wrap justify-center gap-4">
                    @foreach ($attachments as $file)
                        <div class="p-2 bg-gray-50 border rounded shadow-sm">
                            <img
                                src="{{ asset('storage/feedback/' . basename($file)) }}"
                                alt="Attachment"
                                class="w-64 object-cover rounded-md border shadow-sm">
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 italic text-center">No attachments</p>
            @endif
        </flux:card>
    @endif
</flux:card>
