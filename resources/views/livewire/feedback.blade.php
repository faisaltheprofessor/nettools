<flux:dropdown name="anliegen">
    <flux:button icon="chat-bubble-oval-left" icon:variant="micro" icon:class="text-zinc-300">
        Anliegen
    </flux:button>

    <flux:popover
        class="min-w-[30rem] max-w-[32rem] flex flex-col gap-4"
        x-data="feedbackPopover()"
    >

        <div class="flex items-center justify-between">
            <flux:heading size="sm">Neues Anliegen</flux:heading>
            <flux:button
                variant="ghost"
                size="xs"
                icon="list-bullet"
                href="/feedbacks"
                wire:navigate
            >
                Alle Anliegen
            </flux:button>
        </div>

        <!-- Type -->
        <flux:radio.group variant="buttons" class="*:flex-1" wire:model="type">
            <flux:radio value="bug" icon="bug-ant">Fehler melden</flux:radio>
            <flux:radio value="feature" icon="light-bulb">Funktion vorschlagen</flux:radio>
            <flux:radio value="feedback" icon="chat-bubble-oval-left">Feedback</flux:radio>
        </flux:radio.group>

        <!-- Title -->
        <flux:field>
            <flux:label>Titel</flux:label>
            <flux:input wire:model.defer="title" placeholder="Kurzer, präziser Titel"/>
            <flux:error name="title"/>
        </flux:field>

        <!-- Description + Emoji & File buttons -->
        <div class="relative">
            <flux:textarea
                x-ref="textarea"
                wire:model.defer="description"
                rows="8"
                class="dark:bg-transparent!"
                placeholder="Bitte beschreibe dein Anliegen. Du kannst Bilder anhängen."
            />
            <flux:error name="description"/>

            <div class="absolute bottom-3 left-3 flex items-center gap-2">
                <!-- Emoji -->
                <flux:button
                    variant="filled"
                    size="xs"
                    icon="face-smile"
                    icon:variant="outline"
                    icon:class="text-zinc-400 dark:text-zinc-300"
                    x-on:click.prevent="toggleEmoji()"
                />
                <!-- Paper-clip triggers hidden input -->
                <flux:button
                    variant="filled"
                    size="xs"
                    icon="paper-clip"
                    icon:class="text-zinc-400 dark:text-zinc-300"
                    x-on:click.prevent="$refs.file.click()"
                />
            </div>

            <!-- Emoji picker (flat grid, no categories) -->
            <div
                x-show="emojiOpen"
                x-transition
                class="absolute bottom-12 left-3 z-20 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-2 shadow-lg w-[22rem] max-h-64 overflow-y-auto"
                @click.outside="emojiOpen=false"
            >
                <div class="grid grid-cols-10 gap-1 text-xl">
                    <template x-for="e in emojis" :key="e">
                        <button
                            type="button"
                            class="px-1 py-0.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded"
                            @click="insertEmoji(e)"
                            x-text="e"
                        ></button>
                    </template>
                </div>
            </div>

            <!-- Hidden image-only file input (Livewire upload) -->
            <input
                type="file"
                x-ref="file"
                class="hidden"
                multiple
                accept="image/*"
                wire:model="attachments"
            />
        </div>

        <!-- File validation errors -->
        @error('attachments.*')
        <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror

        <!-- Fixed-size, scrollable thumbnails with remove buttons -->
        @if (!empty($attachments))
            <div class="space-y-2">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ count($attachments) }} Bild(er) ausgewählt
                </div>
                <div class="max-h-40 overflow-y-auto overflow-x-hidden pr-1">
                    <div class="grid grid-cols-4 gap-2">
                        @foreach ($attachments as $i => $f)
                            <div class="relative w-20 h-20 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700">
                                @php
                                    $url = method_exists($f, 'temporaryUrl') ? $f->temporaryUrl() : null;
                                @endphp
                                @if ($url)
                                    <img src="{{ $url }}" alt="{{ $f->getClientOriginalName() }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-[10px] text-zinc-500 px-1 text-center">
                                        {{ $f->getClientOriginalName() }}
                                    </div>
                                @endif

                                <!-- Remove button -->
                                <button
                                    type="button"
                                    class="absolute top-1 right-1 inline-flex items-center justify-center w-5 h-5 rounded-full bg-black/60 text-white text-[10px] hover:bg-black/80 cursor-pointer"
                                    wire:click="removeAttachment({{ $i }})"
                                    wire:key="rm-{{ $i }}"
                                    title="Entfernen"
                                >
                                    ✖
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex gap-2 justify-end">
            <flux:button size="sm" variant="primary" color="green" class="w-28" wire:click="submit">
                Absenden
            </flux:button>
        </div>
    </flux:popover>
</flux:dropdown>

<script>
    function feedbackPopover() {
        return {
            emojiOpen: false,
            emojis: [
                '😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','😋','😎','😍','😘','🥰','😗','😙','😚','🙂','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😯','😪','😫','🥱','😴','😌','😛','😜','😝','🤤','😒','😓','😔','😕','🙃','🫠','😖','😞','😟','😤','😢','😭','😦','😧','😨','😩','🤯','😬','😰','😱','🥵','🥶','😳','🤪','😵','🥴','😠','😡','🤬','🤥','🤒','🤕','🤢','🤮','🤧','😇','🥳','🥸','🤠','🤡','🤫','🤭','🫢','🫶','🙏','👍','👎','👊','✊','👏','🙌','🤝','💪','🖐️','👌','✌️','🤞','🤟','🤘','👉','👈','👆','👇','☝️','✍️','🖕',
                '🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🐔','🐧','🐦','🐤','🦆','🦅','🦉','🦇','🐺','🐴','🦄','🐝','🪲','🐢','🐍','🦖','🐙','🦑','🦀','🐡','🐠','🐟','🐬','🐳','🦈','🐘','🦛','🦏','🐪','🐫','🦒','🐐','🦌','🦙','🐓','🦃','🕊️','🦤','🦦','🦫','🦔',
                '🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🍆','🥑','🥦','🌽','🥕','🥔','🍠','🥐','🍞','🥯','🥖','🥨','🥞','🧀','🍗','🍖','🥩','🥓','🍔','🍟','🍕','🌭','🥪','🌮','🌯','🥙','🥚','🍳','🍝','🍜','🍣','🍱','🍤','🍙','🍚','🍛','🍩','🍪','🎂','🍰','🧁','🍫','🍬','🍿','☕','🍵','🥤','🍺','🍻','🍷','🥂','🥃','🍸','🍹',
                '⚽','🏀','🏈','⚾','🎾','🏐','🥏','🎱','🏓','🏸','🥅','🥊','🥋','🎽','🛼','🛹','⛸️','🏂','🏄','🚣','🏊','🚴','🚵','🤸','🤼','🤺','🎯','🎳','🎮','🎲','🎼','🎤','🎧','🎷','🎸','🎹','🎺','🎻','🥁',
                '🚗','🚕','🚙','🚌','🏎️','🚓','🚑','🚒','🚚','🚜','🚲','🏍️','✈️','🚁','🚀','🚢','🛳️','⚓','🗼','🏰','🎡','🎢','🏖️','🏝️','🏔️','🌋','🏕️','🚉','🚆','🚄','🚅','🗺️','🧭',
                '⌚','📱','💻','🖥️','🖨️','📷','📸','🎥','🔍','🔬','🔭','💡','📖','📚','📎','📌','✂️','🖊️','🖋️','🖌️','📝','📦','💼','🔑','🔒','🔓','🔧','🔨','⚙️','🧰','💊','💉','🛏️','🚽','🛁',
                '❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','💤','💥','💦','💨','💬','🗨️','💭','♻️','✅','✔️','❌','⚠️','❗','❓','⭐','🌟','✨','⚡','🔥','💧','❄️','☀️','🌈'
            ],

            toggleEmoji() { this.emojiOpen = !this.emojiOpen; },

            insertEmoji(e) {
                const ta = this.$refs.textarea;
                const start = ta.selectionStart ?? ta.value.length;
                const end = ta.selectionEnd ?? ta.value.length;
                const before = ta.value.substring(0, start);
                const after  = ta.value.substring(end);
                ta.value = before + e + after;
                ta.setSelectionRange(start + e.length, start + e.length);
                ta.dispatchEvent(new Event('input')); // notify Livewire
                this.emojiOpen = false;
            },
        }
    }
</script>
