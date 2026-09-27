<div class="h-screen flex flex-col bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950"
     x-data="{
        inactivityTimer: null,
        resetTimer() {
            clearTimeout(this.inactivityTimer);
            this.inactivityTimer = setTimeout(() => $wire.resetKiosk(), {{ $this->inactivityTimeout * 1000 }});
        }
     }"
     x-init="resetTimer()"
     @click="resetTimer()" @touchstart="resetTimer()">

    {{-- ═══════════ HEADER ═══════════ --}}
    <header class="flex items-center justify-between gap-3 px-4 sm:px-8 py-3 sm:py-4 bg-slate-900/80 backdrop-blur-xl border-b border-white/5">
        <div class="flex items-center gap-4">
            @if($step !== 'categories' && $step !== 'confirmation')
                <button wire:click="goBack" class="p-3 rounded-xl bg-white/5 hover:bg-white/10 transition-colors active:scale-95">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
            @endif
            <div>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">Yeboox<span class="text-emerald-400">POS</span></h1>
                <p class="hidden sm:block text-xs text-slate-400 font-medium">Commander ici • Libre-service</p>
            </div>
        </div>

        {{-- Cart Badge --}}
        @if($cartCount > 0 && $step !== 'cart' && $step !== 'payment' && $step !== 'confirmation')
            <button wire:click="goToCart" class="flex items-center gap-2 sm:gap-3 px-3 sm:px-6 py-2 sm:py-3 bg-emerald-500 hover:bg-emerald-400 text-white rounded-xl sm:rounded-2xl font-bold text-sm sm:text-lg transition-all active:scale-95 shadow-lg shadow-emerald-500/30">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                <span>{{ $cartCount }}</span>
                <span class="text-emerald-100">{{ number_format($cartTotal, 0, ',', '.') }} FC</span>
            </button>
        @endif
    </header>

    {{-- ═══════════ MAIN CONTENT ═══════════ --}}
    <main class="flex-1 overflow-y-auto p-3 sm:p-6">

        {{-- ── STEP: Categories ─────────────────────── --}}
        @if($step === 'categories')
            <div class="text-center mb-8">
                <h2 class="text-2xl sm:text-4xl font-black text-white mb-2">Que souhaitez-vous ?</h2>
                <p class="text-sm sm:text-lg text-slate-400">Touchez une catégorie pour commencer</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-5 max-w-5xl mx-auto">
                @foreach($categories as $category)
                    @php
                        $colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#f97316'];
                        $color = $colors[$loop->index % count($colors)];
                        $iconMap = [
                            'leaf' => '🥗', 'fire' => '🍖', 'sparkles' => '✨', 'cake' => '🍰',
                            'cup-soda' => '☕', 'beaker' => '🍺', 'plus-circle' => '🍟',
                            'squares-2x2' => '🍿', 'sun' => '🌮', 'heart' => '❤️',
                        ];
                        $emoji = $iconMap[$category->icon ?? ''] ?? '🍽️';
                    @endphp
                    <button wire:click="selectCategory('{{ $category->id }}')"
                            class="kiosk-product-card flex flex-col items-center justify-center gap-3 sm:gap-4 p-4 sm:p-6 rounded-2xl sm:rounded-3xl border-2 border-white/5 bg-white/5 backdrop-blur-sm hover:border-white/20 transition-all duration-200 active:scale-95"
                            style="--cat-color: {{ $color }}">
                        <span class="text-5xl">{{ $emoji }}</span>
                        <span class="text-lg font-bold text-white">{{ $category->name }}</span>
                        <span class="text-xs text-slate-400">{{ $category->products_count ?? '' }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Order Type Selector --}}
            <div class="grid grid-cols-1 sm:flex items-center justify-center gap-3 sm:gap-4 mt-8 sm:mt-10">
                <button wire:click="$set('orderType', 'dine_in')"
                        class="px-8 py-4 rounded-2xl font-bold text-lg transition-all active:scale-95 {{ $orderType === 'dine_in' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-white/5 text-slate-300 hover:bg-white/10' }}">
                    🍽️ Sur place
                </button>
                <button wire:click="$set('orderType', 'takeaway')"
                        class="px-8 py-4 rounded-2xl font-bold text-lg transition-all active:scale-95 {{ $orderType === 'takeaway' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30' : 'bg-white/5 text-slate-300 hover:bg-white/10' }}">
                    🥡 À emporter
                </button>
            </div>
        @endif

        {{-- ── STEP: Products ───────────────────────── --}}
        @if($step === 'products')
            <div class="mb-6">
                @php
                    $catIcon = $categories->firstWhere('id', $selectedCategoryId)?->icon ?? '';
                    $catEmoji = ['leaf'=>'🥗','fire'=>'🍖','sparkles'=>'✨','cake'=>'🍰','cup-soda'=>'☕','beaker'=>'🍺','plus-circle'=>'🍟','squares-2x2'=>'🍿'][$catIcon] ?? '📦';
                @endphp
                <h2 class="text-3xl font-black text-white">
                    {{ $catEmoji }}
                    {{ $categories->firstWhere('id', $selectedCategoryId)?->name ?? 'Produits' }}
                </h2>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                @forelse($products as $product)
                    <button wire:click="addToCart('{{ $product->id }}')"
                            class="kiosk-product-card flex flex-col rounded-3xl bg-white/5 border border-white/5 hover:border-emerald-500/30 hover:bg-white/10 transition-all duration-200 active:scale-95 overflow-hidden text-left">
                        {{-- Product Image or Placeholder --}}
                        <div class="h-32 bg-gradient-to-br from-slate-800 to-slate-700 flex items-center justify-center">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-4xl opacity-50">🍽️</span>
                            @endif
                        </div>
                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <h3 class="text-base font-bold text-white leading-tight">{{ $product->name }}</h3>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xl font-black text-emerald-400">{{ number_format($product->price, 0, ',', '.') }} <span class="text-sm font-medium text-emerald-500/70">FC</span></span>
                                <span class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xl">+</span>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full text-center py-20 text-slate-400">
                        <span class="text-6xl block mb-4">📭</span>
                        <p class="text-xl">Aucun produit disponible dans cette catégorie</p>
                    </div>
                @endforelse
            </div>
        @endif

        {{-- ── STEP: Cart ───────────────────────────── --}}
        @if($step === 'cart')
            <div class="max-w-3xl mx-auto">
                <h2 class="text-3xl font-black text-white mb-6">🛒 Votre commande</h2>

                <div class="space-y-3 mb-8">
                    @foreach($cart as $index => $item)
                        <div class="flex items-center gap-4 p-4 rounded-2xl bg-white/5 border border-white/5">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-white">{{ $item['name'] }}</h3>
                                <p class="text-sm text-slate-400">{{ number_format($item['price'], 0, ',', '.') }} FC / unité</p>
                            </div>

                            {{-- Quantity Controls --}}
                            <div class="flex items-center gap-3">
                                <button wire:click="decrementItem({{ $index }})" class="w-12 h-12 rounded-xl bg-red-500/20 text-red-400 flex items-center justify-center text-2xl font-bold hover:bg-red-500/30 active:scale-90 transition-all">−</button>
                                <span class="text-xl font-black text-white w-8 text-center">{{ $item['quantity'] }}</span>
                                <button wire:click="incrementItem({{ $index }})" class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl font-bold hover:bg-emerald-500/30 active:scale-90 transition-all">+</button>
                            </div>

                            <div class="text-right min-w-[100px]">
                                <span class="text-xl font-black text-emerald-400">{{ number_format($item['total'], 0, ',', '.') }} FC</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Customer Name (optional) --}}
                <div class="mb-6">
                    <label class="text-sm font-medium text-slate-400 block mb-2">Votre prénom (optionnel)</label>
                    <input type="text" wire:model="customerName"
                           class="w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-lg placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50"
                           placeholder="Ex: Jean">
                </div>

                {{-- Total & Pay Button --}}
                <div class="flex items-center justify-between p-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                    <div>
                        <p class="text-sm text-emerald-300/70 font-medium">TOTAL À PAYER</p>
                        <p class="text-4xl font-black text-emerald-400">{{ number_format($cartTotal, 0, ',', '.') }} <span class="text-2xl">FC</span></p>
                    </div>
                    <button wire:click="goToPayment" class="px-10 py-5 bg-emerald-500 hover:bg-emerald-400 text-white rounded-2xl font-black text-xl transition-all active:scale-95 shadow-2xl shadow-emerald-500/30">
                        Payer →
                    </button>
                </div>
            </div>
        @endif

        {{-- ── STEP: Payment ────────────────────────── --}}
        @if($step === 'payment')
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-black text-white mb-2">💳 Mode de paiement</h2>
                <p class="text-slate-400 mb-8 text-lg">Total: <span class="text-emerald-400 font-bold">{{ number_format($cartTotal, 0, ',', '.') }} FC</span></p>

                <div class="grid grid-cols-2 gap-5 mb-10">
                    @foreach($paymentMethods as $method)
                        <button wire:click="$set('paymentMethodId', '{{ $method->id }}')"
                                class="p-8 rounded-3xl border-2 transition-all duration-200 active:scale-95 {{ $paymentMethodId === $method->id ? 'bg-emerald-500/20 border-emerald-500 shadow-lg shadow-emerald-500/20' : 'bg-white/5 border-white/5 hover:border-white/20' }}">
                            <span class="text-4xl block mb-3">{{ $method->icon ?? '💰' }}</span>
                            <span class="text-lg font-bold text-white block">{{ $method->name }}</span>
                        </button>
                    @endforeach
                </div>

                <button wire:click="submitOrder"
                        @if(!$paymentMethodId) disabled @endif
                        class="w-full py-6 rounded-2xl font-black text-2xl transition-all active:scale-95 {{ $paymentMethodId ? 'bg-emerald-500 hover:bg-emerald-400 text-white shadow-2xl shadow-emerald-500/30' : 'bg-slate-700 text-slate-400 cursor-not-allowed' }}">
                    ✅ Confirmer la commande
                </button>
            </div>
        @endif

        {{-- ── STEP: Confirmation ───────────────────── --}}
        @if($step === 'confirmation')
            <div class="max-w-2xl mx-auto text-center py-12"
                 x-data="{ countdown: 15 }"
                 x-init="let timer = setInterval(() => { countdown--; if (countdown <= 0) { clearInterval(timer); $wire.resetKiosk(); } }, 1000)">
                <div class="w-32 h-32 mx-auto mb-8 rounded-full bg-emerald-500/20 flex items-center justify-center animate-bounce">
                    <span class="text-6xl">✅</span>
                </div>
                <h2 class="text-4xl font-black text-white mb-4">Merci !</h2>
                <p class="text-xl text-slate-300 mb-2">Votre commande a été envoyée en cuisine.</p>
                @if($customerName)
                    <p class="text-lg text-emerald-400 font-bold mb-6">{{ $customerName }}, nous vous appellerons !</p>
                @endif
                <p class="text-emerald-400 text-3xl font-black mb-8">{{ number_format($cartTotal, 0, ',', '.') }} FC</p>

                <div class="flex items-center justify-center gap-3 text-slate-400 mb-8">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span>Retour automatique dans <span class="font-bold text-white" x-text="countdown"></span> secondes</span>
                </div>

                <button wire:click="resetKiosk" class="px-10 py-4 bg-white/10 hover:bg-white/20 rounded-2xl text-white font-bold text-lg transition-all active:scale-95">
                    🔄 Nouvelle commande
                </button>
            </div>
        @endif
    </main>

    {{-- ═══════════ FOOTER ═══════════ --}}
    @if($step !== 'confirmation')
        <footer class="px-8 py-3 bg-slate-900/50 border-t border-white/5 flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Yeboox POS — Kiosque
            </div>
            <button wire:click="resetKiosk" class="text-xs text-slate-500 hover:text-white transition-colors px-3 py-1 rounded">
                Annuler tout
            </button>
        </footer>
    @endif
</div>
