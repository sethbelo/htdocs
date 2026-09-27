<div class="h-screen flex flex-col bg-slate-950" wire:poll.4s>

    {{-- ═══ TOP BAR ═══ --}}
    <header class="min-h-12 bg-slate-900 border-b border-slate-800 flex items-center justify-between gap-2 px-2 sm:px-4 flex-shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center text-white font-bold text-xs">K</div>
            <h1 class="text-sm font-bold text-white">Yeboox<span class="text-orange-400">KDS</span></h1>
        </div>

        {{-- Stats Badges --}}
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-500/20 text-amber-400 text-xs font-bold">
                <x-heroicon-o-clock class="w-3.5 h-3.5"/>
                <span>{{ $stats['pending'] }}</span>
                <span class="hidden sm:inline text-[10px] font-normal opacity-70">en attente</span>
            </div>
            <div class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-orange-500/20 text-orange-400 text-xs font-bold">
                <x-heroicon-o-fire class="w-3.5 h-3.5"/>
                <span>{{ $stats['preparing'] }}</span>
                <span class="hidden sm:inline text-[10px] font-normal opacity-70">en cours</span>
            </div>
            <div class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-400 text-xs font-bold">
                <x-heroicon-o-bell-alert class="w-3.5 h-3.5"/>
                <span>{{ $stats['ready'] }}</span>
                <span class="hidden sm:inline text-[10px] font-normal opacity-70">prêtes</span>
            </div>
            <div class="hidden md:flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-700 text-slate-300 text-xs">
                <span>{{ $stats['total_today'] }} cmds aujourd'hui</span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            {{-- Show Completed Toggle --}}
            <label class="hidden sm:flex items-center gap-1.5 text-xs text-slate-400 cursor-pointer">
                <input wire:model.live="showCompleted" type="checkbox" class="rounded border-slate-600 bg-slate-800 text-orange-500 focus:ring-orange-500/20 w-3.5 h-3.5">
                Servies
            </label>

            {{-- Clock --}}
            <span class="text-xs text-slate-500" x-data x-text="new Date().toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'})" x-init="setInterval(() => $el.textContent = new Date().toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'}), 30000)"></span>

            <a href="{{ route('admin.dashboard') }}" class="p-1.5 rounded-lg hover:bg-slate-800 text-slate-500 transition-colors" title="Admin">
                <x-heroicon-o-cog-6-tooth class="w-4 h-4"/>
            </a>
        </div>
    </header>

    {{-- ═══ KANBAN COLUMNS ═══ --}}
    <div class="flex-1 flex overflow-x-auto lg:overflow-hidden snap-x snap-mandatory lg:snap-none">

        {{-- ── Column: EN ATTENTE (Pending) ── --}}
        <div class="flex flex-col border-r border-slate-800 min-w-[88vw] sm:min-w-[24rem] lg:min-w-0 lg:flex-1 snap-start">
            <div class="h-10 bg-amber-500/10 border-b border-slate-800 flex items-center justify-center gap-2 flex-shrink-0">
                <x-heroicon-o-clock class="w-4 h-4 text-amber-400"/>
                <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">En Attente</span>
                @if($pendingOrders->count() > 0)
                    <span class="bg-amber-500 text-slate-900 text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $pendingOrders->count() }}</span>
                @endif
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-2">
                @foreach($pendingOrders as $order)
                    @include('livewire.kitchen._order-card', ['order' => $order, 'phase' => 'pending'])
                @endforeach
                @if($pendingOrders->isEmpty())
                    <div class="flex flex-col items-center justify-center h-full text-slate-600">
                        <x-heroicon-o-inbox class="w-10 h-10 mb-2 opacity-30"/>
                        <p class="text-xs">Aucune commande</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Column: EN PRÉPARATION (Preparing) ── --}}
        <div class="flex flex-col border-r border-slate-800 min-w-[88vw] sm:min-w-[24rem] lg:min-w-0 lg:flex-1 snap-start">
            <div class="h-10 bg-orange-500/10 border-b border-slate-800 flex items-center justify-center gap-2 flex-shrink-0">
                <x-heroicon-o-fire class="w-4 h-4 text-orange-400"/>
                <span class="text-xs font-bold text-orange-400 uppercase tracking-wider">En Préparation</span>
                @if($preparingOrders->count() > 0)
                    <span class="bg-orange-500 text-slate-900 text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $preparingOrders->count() }}</span>
                @endif
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-2">
                @foreach($preparingOrders as $order)
                    @include('livewire.kitchen._order-card', ['order' => $order, 'phase' => 'preparing'])
                @endforeach
                @if($preparingOrders->isEmpty())
                    <div class="flex flex-col items-center justify-center h-full text-slate-600">
                        <x-heroicon-o-fire class="w-10 h-10 mb-2 opacity-30"/>
                        <p class="text-xs">Rien en cours</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Column: PRÊTES (Ready) ── --}}
        <div class="flex flex-col border-r border-slate-800 min-w-[88vw] sm:min-w-[24rem] lg:min-w-0 lg:flex-1 snap-start">
            <div class="h-10 bg-emerald-500/10 border-b border-slate-800 flex items-center justify-center gap-2 flex-shrink-0">
                <x-heroicon-o-bell-alert class="w-4 h-4 text-emerald-400"/>
                <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Prêtes</span>
                @if($readyOrders->count() > 0)
                    <span class="bg-emerald-500 text-slate-900 text-[10px] font-bold px-1.5 py-0.5 rounded-full animate-pulse">{{ $readyOrders->count() }}</span>
                @endif
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-2">
                @foreach($readyOrders as $order)
                    @include('livewire.kitchen._order-card', ['order' => $order, 'phase' => 'ready'])
                @endforeach
                @if($readyOrders->isEmpty())
                    <div class="flex flex-col items-center justify-center h-full text-slate-600">
                        <x-heroicon-o-bell-alert class="w-10 h-10 mb-2 opacity-30"/>
                        <p class="text-xs">Rien de prêt</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Column: SERVIES (Served) — optional ── --}}
        @if($showCompleted)
        <div class="flex flex-col min-w-[88vw] sm:min-w-[24rem] lg:min-w-0 lg:flex-1 snap-start">
            <div class="h-10 bg-teal-500/10 border-b border-slate-800 flex items-center justify-center gap-2 flex-shrink-0">
                <x-heroicon-o-check-circle class="w-4 h-4 text-teal-400"/>
                <span class="text-xs font-bold text-teal-400 uppercase tracking-wider">Servies</span>
                @if($servedOrders->count() > 0)
                    <span class="bg-teal-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $servedOrders->count() }}</span>
                @endif
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-2">
                @foreach($servedOrders as $order)
                    @include('livewire.kitchen._order-card', ['order' => $order, 'phase' => 'served'])
                @endforeach
                @if($servedOrders->isEmpty())
                    <div class="flex flex-col items-center justify-center h-full text-slate-600">
                        <x-heroicon-o-check-circle class="w-10 h-10 mb-2 opacity-30"/>
                        <p class="text-xs">Aucune commande servie</p>
                    </div>
                @endif
            </div>
        </div>
        @endif

    </div>
</div>
