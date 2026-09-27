@php
    $elapsed = $order->created_at->diffInMinutes(now());
    $isUrgent = $elapsed > 15 && $phase !== 'served';
    $isWarning = $elapsed > 10 && $elapsed <= 15 && $phase !== 'served';

    $borderColor = match($phase) {
        'pending' => $isUrgent ? 'border-red-500 animate-pulse' : ($isWarning ? 'border-amber-500' : 'border-amber-500/30'),
        'preparing' => $isUrgent ? 'border-red-500' : 'border-orange-500/30',
        'ready' => 'border-emerald-500/50',
        'served' => 'border-teal-500/20',
        default => 'border-slate-700',
    };

    $headerBg = match($phase) {
        'pending' => $isUrgent ? 'bg-red-500/20' : 'bg-amber-500/10',
        'preparing' => $isUrgent ? 'bg-red-500/10' : 'bg-orange-500/10',
        'ready' => 'bg-emerald-500/10',
        'served' => 'bg-teal-500/5',
        default => 'bg-slate-800',
    };
@endphp

<div class="rounded-xl border-2 {{ $borderColor }} bg-slate-900 overflow-hidden transition-all" wire:key="kds-{{ $order->id }}">

    {{-- Order Header --}}
    <div class="{{ $headerBg }} px-3 py-2 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-sm font-mono font-bold text-white">{{ $order->order_number }}</span>
            @if($order->table)
                <span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 text-[10px] font-bold">
                    {{ $order->table->name }}
                </span>
            @endif
            @if($order->type->value === 'takeaway')
                <span class="px-1.5 py-0.5 rounded bg-purple-500/20 text-purple-400 text-[10px] font-bold">Emporter</span>
            @elseif($order->type->value === 'delivery')
                <span class="px-1.5 py-0.5 rounded bg-sky-500/20 text-sky-400 text-[10px] font-bold">Livraison</span>
            @endif
        </div>

        {{-- Timer --}}
        <div class="flex items-center gap-1 text-[10px] font-mono font-bold {{ $isUrgent ? 'text-red-400' : ($isWarning ? 'text-amber-400' : 'text-slate-500') }}">
            <x-heroicon-o-clock class="w-3 h-3"/>
            @if($elapsed < 60)
                {{ $elapsed }}min
            @else
                {{ floor($elapsed / 60) }}h{{ str_pad($elapsed % 60, 2, '0', STR_PAD_LEFT) }}
            @endif
        </div>
    </div>

    {{-- Order Items --}}
    <div class="px-3 py-2 space-y-1.5">
        @foreach($order->items as $item)
            @php
                $itemStatusColor = match($item->kitchen_status) {
                    'pending' => 'bg-amber-500/10 border-amber-500/20',
                    'preparing' => 'bg-orange-500/10 border-orange-500/20',
                    'ready' => 'bg-emerald-500/10 border-emerald-500/20',
                    'served' => 'bg-teal-500/5 border-teal-500/10 opacity-50',
                    default => 'bg-slate-800 border-slate-700',
                };
                $textColor = match($item->kitchen_status) {
                    'served' => 'text-slate-500 line-through',
                    default => 'text-white',
                };
            @endphp
            <div class="flex items-center justify-between p-2 rounded-lg border {{ $itemStatusColor }}">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <span class="w-6 h-6 rounded-md bg-slate-700 flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0">
                        {{ (int) $item->quantity }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold {{ $textColor }} truncate">{{ $item->name }}</p>
                        @if($item->comment)
                            <p class="text-[10px] text-amber-400 mt-0.5 truncate">⚠ {{ $item->comment }}</p>
                        @endif
                    </div>
                </div>

                {{-- Per-item action button --}}
                <div class="flex-shrink-0 ml-2">
                    @if($item->kitchen_status === 'pending')
                        <button wire:click="startPreparing('{{ $item->id }}')"
                                class="px-2 py-1 rounded-md bg-orange-500/20 text-orange-400 text-[10px] font-bold hover:bg-orange-500/30 transition-colors"
                                title="Commencer">
                            <x-heroicon-o-fire class="w-3.5 h-3.5"/>
                        </button>
                    @elseif($item->kitchen_status === 'preparing')
                        <button wire:click="markReady('{{ $item->id }}')"
                                class="px-2 py-1 rounded-md bg-emerald-500/20 text-emerald-400 text-[10px] font-bold hover:bg-emerald-500/30 transition-colors"
                                title="Prêt">
                            <x-heroicon-o-check class="w-3.5 h-3.5"/>
                        </button>
                    @elseif($item->kitchen_status === 'ready')
                        <button wire:click="markServed('{{ $item->id }}')"
                                class="px-2 py-1 rounded-md bg-teal-500/20 text-teal-400 text-[10px] font-bold hover:bg-teal-500/30 transition-colors"
                                title="Servi">
                            <x-heroicon-o-hand-thumb-up class="w-3.5 h-3.5"/>
                        </button>
                    @else
                        <span class="px-2 py-1 text-[10px] text-slate-600">✓</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Notes --}}
    @if($order->notes)
        <div class="px-3 pb-2">
            <p class="text-[10px] text-amber-400/80 bg-amber-500/5 rounded-lg px-2 py-1 border border-amber-500/10">
                📝 {{ $order->notes }}
            </p>
        </div>
    @endif

    {{-- Order Footer Actions --}}
    <div class="px-3 py-2 border-t border-slate-800 flex items-center gap-1.5">
        @if($phase === 'pending')
            <button wire:click="startOrderPreparing('{{ $order->id }}')"
                    class="flex-1 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-[10px] font-bold transition-colors flex items-center justify-center gap-1 active:scale-95">
                <x-heroicon-o-fire class="w-3.5 h-3.5"/> Tout préparer
            </button>
        @elseif($phase === 'preparing')
            <button wire:click="markOrderReady('{{ $order->id }}')"
                    class="flex-1 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold transition-colors flex items-center justify-center gap-1 active:scale-95">
                <x-heroicon-o-bell-alert class="w-3.5 h-3.5"/> Tout prêt
            </button>
        @elseif($phase === 'ready')
            <button wire:click="markOrderServed('{{ $order->id }}')"
                    class="flex-1 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-[10px] font-bold transition-colors flex items-center justify-center gap-1 active:scale-95">
                <x-heroicon-o-check-circle class="w-3.5 h-3.5"/> Servie
            </button>
        @endif

        @if($phase !== 'served')
            <button wire:click="recallOrder('{{ $order->id }}')" wire:confirm="Rappeler cette commande ?"
                    class="py-1.5 px-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 text-[10px] transition-colors"
                    title="Rappeler">
                <x-heroicon-o-arrow-uturn-left class="w-3.5 h-3.5"/>
            </button>
        @endif

        {{-- Cashier info --}}
        <span class="text-[10px] text-slate-600 ml-auto">{{ $order->user?->name }}</span>
    </div>
</div>
