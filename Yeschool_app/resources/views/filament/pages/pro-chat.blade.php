<div class="h-[calc(100vh-140px)] flex bg-white dark:bg-[#0c0c0c] rounded-3xl border border-slate-200 dark:border-[#1a1a1a] shadow-xl overflow-hidden font-sans" 
     wire:poll.4s
     x-data="{ activeUserSelected: @entangle('activeUserId') }"
>
    <!-- Left Pane: Contacts/Users List -->
    <div class="w-full md:w-80 flex flex-col border-r border-slate-200 dark:border-[#1a1a1a] h-full bg-slate-50/30 dark:bg-[#080808]/30 flex-shrink-0"
         :class="{ 'hidden md:flex': activeUserSelected, 'flex': !activeUserSelected }"
    >
        <!-- Search bar -->
        <div class="p-4 border-b border-slate-200 dark:border-[#1a1a1a]">
            <h3 class="text-lg font-bold font-space bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-purple-500 mb-3">
                Discussions
            </h3>
            <div class="relative">
                <input 
                    type="text" 
                    wire:model.live="searchQuery" 
                    placeholder="Rechercher un utilisateur..." 
                    class="w-full pl-10 pr-4 py-2 text-sm bg-white dark:bg-[#141414] border border-slate-200 dark:border-[#222] rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-slate-800 dark:text-slate-100"
                />
                <div class="absolute left-3 top-2.5 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Users List -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-[#161616]">
            @forelse($this->users as $user)
                <button 
                    wire:click="selectUser({{ $user->id }})" 
                    class="w-full p-4 flex items-center gap-3 hover:bg-slate-100/50 dark:hover:bg-[#111]/50 transition-all text-left group border-l-4 {{ $this->activeUserId === $user->id ? 'bg-indigo-50/40 dark:bg-indigo-950/10 border-indigo-500 dark:border-indigo-400' : 'border-transparent' }}"
                >
                    <!-- User Avatar with Online status -->
                    <div class="relative flex-shrink-0">
                        @if($user->avatar)
                            <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="w-11 h-11 rounded-full object-cover border-2 border-slate-200 dark:border-[#222]">
                        @else
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                        @endif

                        <!-- Online indicator dot -->
                        @if($user->isOnline())
                            <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-[#0c0c0c]">
                                <span class="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-75"></span>
                            </span>
                        @else
                            <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full bg-slate-400 ring-2 ring-white dark:ring-[#0c0c0c]"></span>
                        @endif
                    </div>

                    <!-- User Meta details -->
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-baseline mb-1">
                            <h4 class="text-sm font-semibold truncate text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                {{ $user->name }}
                            </h4>
                            @if($user->last_message)
                                <span class="text-[10px] text-slate-400 flex-shrink-0">
                                    {{ $user->last_message->created_at->format('H:i') }}
                                </span>
                            @endif
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-slate-400 dark:text-slate-500 truncate pr-2">
                                @if($user->last_message)
                                    @if($user->last_message->sender_id === Auth::id())
                                        <span class="font-medium text-slate-500 dark:text-slate-400">Vous:</span>
                                    @endif
                                    
                                    @if($user->last_message->attachment_path)
                                        <span class="italic text-indigo-500 flex items-center gap-0.5 inline-flex">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                            Pièce jointe
                                        </span>
                                    @else
                                        {{ $user->last_message->message }}
                                    @endif
                                @else
                                    <span class="italic text-slate-400 dark:text-slate-600">Aucun message</span>
                                @endif
                            </p>

                            <!-- Unread messages badge count -->
                            @if($user->unread_count > 0)
                                <span class="bg-indigo-600 dark:bg-indigo-500 text-white font-bold text-[10px] px-1.5 py-0.5 rounded-full flex-shrink-0 min-w-5 text-center shadow-md animate-bounce">
                                    {{ $user->unread_count }}
                                </span>
                            @endif
                        </div>
                    </div>
                </button>
            @empty
                <div class="p-8 text-center text-slate-400 dark:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 005.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="text-xs">Aucun autre utilisateur trouvé</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Right Pane: Active Conversation Container -->
    <div class="flex-1 flex flex-col h-full bg-slate-50/50 dark:bg-[#0a0a0a]"
         :class="{ 'flex': activeUserSelected, 'hidden md:flex': !activeUserSelected }"
    >
        @if($this->activeUser)
            <!-- Active Conversation Header -->
            <div class="p-4 border-b border-slate-200 dark:border-[#1a1a1a] flex justify-between items-center bg-white dark:bg-[#0c0c0c] shadow-sm z-10">
                <div class="flex items-center gap-3">
                    <!-- Back button on Mobile -->
                    <button 
                        @click="activeUserSelected = null; $wire.set('activeUserId', null)" 
                        class="md:hidden p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-[#1a1a1a] text-slate-500"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Profile Avatar -->
                    <div class="relative flex-shrink-0">
                        @if($this->activeUser->avatar)
                            <img src="{{ Storage::url($this->activeUser->avatar) }}" alt="{{ $this->activeUser->name }}" class="w-10 h-10 rounded-full object-cover border-2 border-slate-200 dark:border-[#222]">
                        @else
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                                {{ strtoupper(substr($this->activeUser->name, 0, 2)) }}
                            </div>
                        @endif

                        <!-- status dot -->
                        @if($this->activeUser->isOnline())
                            <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-[#0c0c0c]"></span>
                        @else
                            <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-slate-400 ring-2 ring-white dark:ring-[#0c0c0c]"></span>
                        @endif
                    </div>

                    <!-- Name & Status description -->
                    <div>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">
                            {{ $this->activeUser->name }}
                        </h4>
                        <p class="text-[10px] font-medium">
                            @if($this->activeUser->isOnline())
                                <span class="text-emerald-500">En ligne</span>
                            @else
                                <span class="text-slate-400 dark:text-slate-500">
                                    Hors ligne 
                                    @if($this->activeUser->last_seen_at)
                                        · vu {{ $this->activeUser->last_seen_at->diffForHumans() }}
                                    @endif
                                </span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Icons bar -->
                <div class="flex items-center gap-1">
                    <span class="badge badge-outline text-slate-400 border-slate-200 dark:border-slate-800 uppercase tracking-widest text-[9px] font-semibold py-2 px-2.5">
                        {{ $this->activeUser->type }}
                    </span>
                </div>
            </div>

            <!-- Scrollable Messages Area -->
            <div id="chat-box" 
                 class="flex-1 overflow-y-auto p-6 space-y-6"
                 x-data="{
                     scrollToBottom() {
                         $nextTick(() => {
                             const el = document.getElementById('chat-box');
                             if (el) {
                                 el.scrollTop = el.scrollHeight;
                             }
                         });
                     }
                 }"
                 x-init="scrollToBottom()"
                 @scroll-to-bottom.window="scrollToBottom()"
            >
                @forelse($this->messages as $message)
                    @php
                        $isMe = $message->sender_id === Auth::id();
                    @endphp
                    
                    <div class="chat {{ $isMe ? 'chat-end' : 'chat-start' }} group animate-fade-in">
                        <!-- User avatar in chat bubble -->
                        <div class="chat-image avatar hidden sm:block">
                            <div class="w-8 rounded-full border border-slate-100 dark:border-slate-800">
                                @if($isMe)
                                    @if(Auth::user()->avatar)
                                        <img src="{{ Storage::url(Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
                                    @else
                                        <div class="w-full h-full bg-slate-300 dark:bg-slate-700 text-slate-800 dark:text-slate-100 font-bold flex items-center justify-center text-xs">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                    @endif
                                @else
                                    @if($this->activeUser->avatar)
                                        <img src="{{ Storage::url($this->activeUser->avatar) }}" alt="{{ $this->activeUser->name }}">
                                    @else
                                        <div class="w-full h-full bg-indigo-500 text-white font-bold flex items-center justify-center text-xs">
                                            {{ strtoupper(substr($this->activeUser->name, 0, 1)) }}
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <!-- Message timestamp -->
                        <div class="chat-header text-[10px] opacity-50 mb-1 px-1 flex gap-1 items-center">
                            <span>{{ $isMe ? 'Vous' : $this->activeUser->name }}</span>
                            <time class="text-[9px]">{{ $message->created_at->format('H:i') }}</time>
                        </div>

                        <!-- Chat bubble body -->
                        <div class="chat-bubble shadow-md rounded-2xl max-w-md text-sm px-4 py-3 {{ $isMe ? 'bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-tr-none' : 'bg-white dark:bg-[#141414] text-slate-800 dark:text-slate-100 border border-slate-200/50 dark:border-[#222] rounded-tl-none' }}">
                            <!-- Message text if any -->
                            @if(trim($message->message) !== '')
                                <p class="leading-relaxed whitespace-pre-line">{{ $message->message }}</p>
                            @endif

                            <!-- Attachment Display -->
                            @if($message->attachment_path)
                                <div class="{{ trim($message->message) !== '' ? 'mt-3 border-t border-white/10 pt-2' : '' }}">
                                    @if($message->attachment_type === 'image')
                                        <a href="{{ Storage::url($message->attachment_path) }}" target="_blank" class="block rounded-lg overflow-hidden border border-slate-200/30 max-w-[260px] hover:opacity-90 transition-opacity">
                                            <img src="{{ Storage::url($message->attachment_path) }}" alt="{{ $message->attachment_name }}" class="object-cover w-full max-h-[180px]">
                                        </a>
                                    @else
                                        <a href="{{ Storage::url($message->attachment_path) }}" target="_blank" download="{{ $message->attachment_name }}" class="flex items-center gap-2.5 p-2 rounded-xl bg-slate-900/10 hover:bg-slate-900/20 dark:bg-white/5 dark:hover:bg-white/10 text-xs border border-slate-500/10 max-w-[280px] transition-all overflow-hidden">
                                            <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="overflow-hidden flex-1">
                                                <p class="font-bold truncate text-slate-700 dark:text-slate-300">{{ $message->attachment_name }}</p>
                                                <p class="opacity-60 text-[9px]">Télécharger la pièce jointe</p>
                                            </div>
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="h-full flex flex-col items-center justify-center text-slate-400 py-12">
                        <div class="w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-950/20 text-indigo-500 flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold">Commencez la discussion</p>
                        <p class="text-xs text-slate-400 mt-1">Envoyez un message pour démarrer la conversation avec {{ $this->activeUser->name }}.</p>
                    </div>
                @endforelse
            </div>

            <!-- Message Input Form Panel -->
            <div class="p-4 border-t border-slate-200 dark:border-[#1a1a1a] bg-white dark:bg-[#0c0c0c] z-10 space-y-3">
                <!-- Attachment indicator panel -->
                @if ($attachment)
                    <div class="bg-slate-50 dark:bg-[#141414] p-2.5 rounded-xl flex items-center justify-between border border-slate-200/50 dark:border-[#222] animate-slide-in">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[200px]">
                                {{ $attachment->getClientOriginalName() }}
                            </span>
                            <span class="text-[10px] text-slate-400">
                                ({{ round($attachment->getSize() / 1024, 1) }} KB)
                            </span>
                        </div>
                        <button type="button" wire:click="deleteAttachment" class="text-rose-500 hover:bg-rose-500/10 p-1 rounded-lg transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                <!-- Attachment upload errors -->
                @error('attachment')
                    <p class="text-xs text-rose-500 px-1 font-semibold animate-pulse">{{ $message }}</p>
                @enderror

                <!-- Input actions bar -->
                <form wire:submit.prevent="sendMessage" class="flex items-end gap-2">
                    <!-- Attachment button -->
                    <div class="relative flex-shrink-0">
                        <label for="attachment-input" class="btn btn-circle btn-ghost text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-[#1a1a1a] shadow-sm flex items-center justify-center cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                        </label>
                        <input 
                            type="file" 
                            id="attachment-input" 
                            wire:model="attachment" 
                            class="hidden" 
                            accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain"
                        />
                    </div>

                    <!-- Alpine Emoji picker panel -->
                    <div class="relative flex-shrink-0"
                         x-data="{ 
                             open: false, 
                             emojis: ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🥸','🤩','🥳','😏','😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🫣','🤭','🤫','🫡','✍️','👋','👏','👍','👎','🙌','❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❤️‍🔥','❣️','💕','💞','💓','💗','💖','💘','💝','🔥','🎉','✨','🚀','💡','🔔','💬','📂','📎','📍','✅'],
                             selectEmoji(emoji) {
                                 const input = document.getElementById('chat-input-textarea');
                                 if (input) {
                                     const start = input.selectionStart;
                                     const end = input.selectionEnd;
                                     const text = input.value;
                                     input.value = text.substring(0, start) + emoji + text.substring(end);
                                     input.selectionStart = input.selectionEnd = start + emoji.length;
                                     input.focus();
                                     $wire.set('newMessage', input.value);
                                 } else {
                                     $wire.set('newMessage', $wire.newMessage + emoji);
                                 }
                                 this.open = false;
                             }
                         }"
                         @click.away="open = false"
                    >
                        <button 
                            type="button" 
                            @click="open = !open" 
                            class="btn btn-circle btn-ghost text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-[#1a1a1a] shadow-sm flex items-center justify-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>

                        <!-- Emoji Panel -->
                        <div x-show="open" 
                             x-cloak
                             class="absolute bottom-14 left-0 bg-white dark:bg-[#111] border border-slate-200 dark:border-[#222] rounded-2xl p-3 shadow-xl w-64 max-h-72 overflow-y-auto z-50 transition-all"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                        >
                            <div class="grid grid-cols-6 gap-2 text-xl">
                                <template x-for="emoji in emojis" :key="emoji">
                                    <button 
                                        type="button" 
                                        @click="selectEmoji(emoji)" 
                                        class="hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg p-1 text-center active:scale-90 transition-transform"
                                        x-text="emoji"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Text input box -->
                    <div class="flex-1 relative">
                        <textarea 
                            id="chat-input-textarea"
                            wire:model="newMessage"
                            placeholder="Écrivez votre message..." 
                            rows="1"
                            class="w-full bg-slate-50 dark:bg-[#141414] border border-slate-200 dark:border-[#222] rounded-2xl pl-4 pr-12 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-slate-800 dark:text-slate-100 resize-none max-h-24 overflow-y-auto"
                            @keydown.enter.prevent="if($event.shiftKey) { $el.value += '\n' } else { $wire.sendMessage() }"
                        ></textarea>
                    </div>

                    <!-- Upload state loading indicator -->
                    <div wire:loading wire:target="attachment" class="absolute bottom-20 left-6 text-[10px] text-slate-400 flex items-center gap-1.5 bg-white dark:bg-[#0c0c0c] border border-slate-100 dark:border-[#1a1a1a] py-1 px-2.5 rounded-full shadow-sm">
                        <svg class="animate-spin h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Téléversement en cours...
                    </div>

                    <!-- Send button -->
                    <button 
                        type="submit" 
                        class="btn btn-circle bg-gradient-to-br from-indigo-500 to-purple-600 border-none text-white hover:opacity-95 shadow-md shadow-indigo-500/20 active:scale-95 flex-shrink-0 flex items-center justify-center w-11 h-11"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </form>
            </div>
        @else
            <!-- Chat Default Blank state -->
            <div class="flex-1 flex flex-col items-center justify-center text-center p-8 bg-slate-50/50 dark:bg-[#0a0a0a]">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-500/10 to-purple-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-4 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold font-space text-slate-800 dark:text-slate-200">Messagerie Yeschool</h3>
                <p class="text-slate-400 dark:text-slate-500 max-w-sm mt-2 text-sm leading-relaxed">
                    Sélectionnez un membre du personnel ou un utilisateur dans la liste de gauche pour démarrer une conversation sécurisée.
                </p>
            </div>
        @endif
    </div>
</div>
