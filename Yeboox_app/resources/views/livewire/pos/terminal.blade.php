<div class="h-dvh flex flex-col bg-base-200 text-base-content overflow-hidden select-none"
     x-data="{ selectedCartKey: @entangle('currentCartKey'), mobileCart: false, sessionOpen: @entangle('currentSessionId') }"
     x-init="
         window.addEventListener('print-receipt', e => {
             window.open('/receipt/' + e.detail.orderId + '/print', '_blank', 'width=350,height=600');
         });
         window.addEventListener('print-provisional', e => window.open(e.detail.url, '_blank', 'width=380,height=680'));
         window.addEventListener('submit-pos-logout', () => { sessionOpen = null; document.getElementById('pos-logout-form')?.submit(); });
         window.addEventListener('leave-pos', e => { sessionOpen = null; window.location.assign(e.detail.url); });
         window.addEventListener('beforeunload', e => {
             if (sessionOpen) { e.preventDefault(); e.returnValue = ''; }
         });
         Livewire.hook('morph.updated', () => window.lucide?.createIcons());
     ">

    {{-- ══ ODOO STYLE HEADER ══ --}}
    <header class="h-14 bg-base-100 border-b border-base-300 flex items-center justify-between px-2 sm:px-4 flex-shrink-0 z-10 shadow-sm">
        <div class="flex min-w-0 items-center gap-2 sm:gap-4">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-primary text-primary-content rounded flex items-center justify-center font-bold text-lg">Y</div>
                <span class="font-bold text-lg tracking-tight hidden sm:block">Yeboox<span class="text-primary">POS</span></span>
            </div>
            
            <div class="hidden h-6 w-px bg-base-300 sm:block"></div>

            {{-- Wifi/Sync Status --}}
            <div class="hidden items-center gap-2 text-success sm:flex" title="Connecté">
                <i data-lucide="wifi" class="w-4 h-4"></i>
            </div>
        </div>

        {{-- Center: Search --}}
        <div class="flex-1 max-w-md mx-4 hidden md:block relative">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-base-content/50"></i>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Rechercher un produit..."
                   class="w-full bg-base-200 border-transparent rounded-lg h-9 pl-9 pr-4 text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:bg-base-100 transition-colors">
        </div>

        <div class="flex min-w-0 items-center gap-1.5 sm:gap-3">
            @if(in_array('CDF', $paymentCurrencies->pluck('code')->all(), true) && in_array('USD', $paymentCurrencies->pluck('code')->all(), true))
                <div class="join rounded-lg border border-base-300 bg-base-200 p-0.5" title="1 USD = {{ number_format($this->usd_cdf_rate, 2, ',', ' ') }} CDF">
                    @foreach(['CDF', 'USD'] as $currencyCode)
                        <button type="button" wire:click="switchDisplayCurrency('{{ $currencyCode }}')" class="btn btn-xs join-item {{ $selectedDisplayCurrency === $currencyCode ? 'btn-primary' : 'btn-ghost' }}">{{ $currencyCode }}</button>
                    @endforeach
                </div>
            @endif
            <span class="text-sm font-medium hidden sm:block">{{ auth()->user()->name ?? 'Admin' }}</span>
            <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">
                {{ \Illuminate\Support\Str::of(auth()->user()->name ?? 'A')->substr(0,2)->upper() }}
            </div>

            <flux:dropdown position="bottom" align="end">
                <button type="button" class="btn btn-ghost btn-sm btn-square" title="Changer le thème" aria-label="Changer le thème">
                    <flux:icon.sun class="size-4 dark:hidden" /><flux:icon.moon class="hidden size-4 dark:block" />
                </button>
                <flux:menu>
                    <flux:menu.item icon="sun" x-on:click="setTheme('light')">Clair <flux:icon.check x-show="themeMode === 'light'" class="ms-auto size-4" /></flux:menu.item>
                    <flux:menu.item icon="moon" x-on:click="setTheme('dark')">Sombre <flux:icon.check x-show="themeMode === 'dark'" class="ms-auto size-4" /></flux:menu.item>
                    <flux:menu.item icon="computer-desktop" x-on:click="setTheme('system')">Système <flux:icon.check x-show="themeMode === 'system'" class="ms-auto size-4" /></flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            
            @if(auth()->check() && !auth()->user()->isSeller())
                <button type="button" wire:click="requestExit('admin')" class="btn btn-ghost btn-sm btn-square" title="Administration — clôture requise">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                </button>
            @endif

            <button type="button" wire:click="requestExit('logout')" class="btn btn-error btn-sm text-error-content px-3 sm:px-4 rounded-lg" title="Clôturer et quitter">
                <span class="hidden sm:inline">Fermer</span><i data-lucide="log-out" class="size-4 sm:hidden"></i>
            </button>
            <form id="pos-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                @csrf
            </form>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        
        {{-- ══ LEFT PANEL: PRODUCTS (~65%) ══ --}}
        <main x-show="!mobileCart" class="flex-1 flex flex-col bg-base-200 overflow-hidden relative">
            <div class="shrink-0 bg-base-100 px-3 pb-1 pt-2 md:hidden">
                <label class="relative block">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-base-content/45" />
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Rechercher un produit…" class="input input-bordered h-10 w-full rounded-xl bg-base-200 pl-9 text-sm focus:bg-base-100" />
                </label>
            </div>
            
            {{-- Category Filters --}}
            <div class="relative flex-shrink-0 border-b border-base-300 bg-gradient-to-r from-base-100 via-base-100 to-base-200/70 shadow-sm">
                <div class="flex items-center gap-3 px-3 py-2.5 sm:px-4">
                    <div class="hidden shrink-0 items-center gap-2 pr-1 xl:flex">
                        <span class="grid size-9 place-items-center rounded-xl bg-primary/10 text-primary"><flux:icon.adjustments-horizontal class="size-5" /></span>
                        <div><p class="text-[10px] font-black uppercase tracking-widest text-base-content/45">Filtrer par</p><p class="text-sm font-black leading-none">Catégorie</p></div>
                    </div>
                    <div class="hidden h-9 w-px shrink-0 bg-base-300 xl:block"></div>

                    <div class="scrollbar-hide flex min-w-0 flex-1 items-center gap-2 overflow-x-auto py-0.5">
                        <button type="button" wire:click="$set('selectedCategory', '')"
                                class="group flex h-11 shrink-0 items-center gap-2 rounded-xl border px-3.5 text-sm font-black transition-all duration-200 active:scale-95
                                {{ ! $selectedCategory ? 'border-primary bg-primary text-primary-content shadow-lg shadow-primary/20' : 'border-base-300 bg-base-100 text-base-content hover:-translate-y-0.5 hover:border-primary/50 hover:bg-primary/5 hover:shadow-md' }}">
                            <span class="grid size-7 place-items-center rounded-lg {{ ! $selectedCategory ? 'bg-white/20' : 'bg-primary/10 text-primary' }}"><flux:icon.squares-2x2 class="size-4" /></span>
                            <span>Tous</span>
                            <span class="rounded-full px-2 py-0.5 text-[10px] {{ ! $selectedCategory ? 'bg-white/20 text-white' : 'bg-base-200 text-base-content/60' }}">{{ $categories->sum('products_count') }}</span>
                        </button>

                        @foreach($categories as $cat)
                            @php
                                $categoryColor = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $cat->color) ? $cat->color : '#64748B';
                                $categoryIcon = match ($cat->icon) {
                                    'fire' => 'fire',
                                    'cake' => 'cake',
                                    'beaker', 'cup-soda' => 'beaker',
                                    'plus-circle' => 'plus-circle',
                                    'squares-2x2' => 'squares-2x2',
                                    'sparkles', 'leaf' => 'sparkles',
                                    default => 'tag',
                                };
                                $categoryActive = $selectedCategory === $cat->id;
                            @endphp
                            <button type="button" wire:click="$set('selectedCategory', '{{ $cat->id }}')" wire:key="cat-{{ $cat->id }}"
                                    class="group flex h-11 shrink-0 items-center gap-2 rounded-xl border px-3 text-sm font-bold transition-all duration-200 active:scale-95 {{ $categoryActive ? 'text-white shadow-lg' : 'bg-base-100 text-base-content hover:-translate-y-0.5 hover:shadow-md' }}"
                                    style="border-color: {{ $categoryColor }}66; {{ $categoryActive ? 'background-color: '.$categoryColor.'; box-shadow: 0 8px 20px '.$categoryColor.'35' : 'background-image: linear-gradient(135deg, '.$categoryColor.'14, transparent 70%)' }}">
                                <span class="grid size-7 place-items-center rounded-lg transition-transform group-hover:scale-110 {{ $categoryActive ? 'bg-white/20 text-white' : '' }}" style="{{ $categoryActive ? '' : 'background-color: '.$categoryColor.'18; color: '.$categoryColor }}">
                                    <flux:icon :name="$categoryIcon" class="size-4" />
                                </span>
                                <span>{{ $cat->name }}</span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] {{ $categoryActive ? 'bg-white/20 text-white' : 'bg-base-200 text-base-content/60' }}">{{ $cat->products_count }}</span>
                                @if($categoryActive)<flux:icon.check class="size-3.5" />@endif
                            </button>
                        @endforeach
                    </div>

                    @if($selectedCategory)
                        <button type="button" wire:click="$set('selectedCategory', '')" class="btn btn-sm btn-circle shrink-0 border-base-300 bg-base-100 text-base-content/60 shadow-sm hover:border-error hover:bg-error/10 hover:text-error" title="Réinitialiser le filtre" aria-label="Réinitialiser le filtre"><flux:icon.x-mark class="size-4" /></button>
                    @endif
                </div>
            </div>

            {{-- Products Grid --}}
            <div class="flex-1 overflow-y-auto p-4 scroll-slim">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-3 auto-rows-max">
                    @forelse($products as $product)
                        @php
                            $onPromo = $product->promo_price && $product->promo_price < $product->selling_price;
                            $price = $onPromo ? $product->promo_price : $product->selling_price;
                        @endphp
                        <button type="button" wire:click="addToCart('{{ $product->id }}')" wire:key="prod-{{ $product->id }}"
                                class="group relative bg-base-100 rounded-lg shadow-sm border border-base-300 overflow-hidden hover:shadow-md hover:border-primary transition-all active:scale-95 flex flex-col text-left h-[140px]">
                            
                            {{-- Image Container --}}
                            <div class="h-20 w-full bg-base-200 flex items-center justify-center relative overflow-hidden flex-shrink-0">
                                @if($product->image_path)
                                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                @else
                                    <x-dynamic-component :component="'heroicon-o-'.($product->icon ?: $product->type?->icon() ?: 'cube')" class="h-8 w-8 text-base-content/20" />
                                @endif
                                
                                @if($onPromo)
                                    <div class="absolute top-1 right-1 bg-error text-error-content text-[10px] font-bold px-1.5 py-0.5 rounded shadow">Promo</div>
                                @endif
                            </div>

                            {{-- Product Info --}}
                            <div class="p-2 flex-1 flex flex-col justify-between">
                                <span class="text-[11px] font-medium leading-tight line-clamp-2 text-base-content">{{ $product->name }}</span>
                                <span class="text-sm font-bold text-base-content mt-1">{{ number_format($this->displayAmount((float) $price), $selectedDisplayCurrency === 'CDF' ? 0 : 2, ',', ' ') }} <span class="text-[9px] opacity-60">{{ $this->display_currency_symbol }}</span></span>
                            </div>
                        </button>
                    @empty
                        <div class="col-span-full py-16 flex flex-col items-center justify-center text-base-content/40">
                            <i data-lucide="search" class="w-12 h-12 mb-2"></i>
                            <p class="text-sm">Aucun produit trouvé</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="absolute bottom-4 inset-x-4 z-30 lg:hidden">
                <button type="button" @click="mobileCart = true" class="btn btn-primary h-14 w-full rounded-2xl shadow-2xl">
                    <i data-lucide="shopping-cart" class="size-5"></i>
                    Panier · {{ $this->cart_count }} article(s)
                    <span class="ml-auto font-black">{{ number_format($this->displayAmount($this->total), $selectedDisplayCurrency === 'CDF' ? 0 : 2, ',', ' ') }} {{ $this->display_currency_symbol }}</span>
                </button>
            </div>
        </main>

        {{-- ══ RIGHT PANEL: CART & NUMPAD (~35%) ══ --}}
        <aside class="hidden w-full lg:flex lg:w-[360px] xl:w-[400px] flex-shrink-0 bg-base-100 border-l border-base-300 flex-col shadow-xl z-20" :class="{ '!flex': mobileCart }">
            <div class="flex items-center justify-between border-b border-base-300 p-2 lg:hidden"><p class="font-bold">Votre panier</p><button type="button" @click="mobileCart = false" class="btn btn-sm btn-ghost btn-square"><i data-lucide="x" class="size-5"></i></button></div>
            
            {{-- Order Type / Header --}}
            <div class="p-2 border-b border-base-300 bg-base-200 flex items-center gap-1">
                @foreach(['dine_in' => 'Sur place', 'takeaway' => 'Emporter', 'delivery' => 'Livraison'] as $type => $label)
                    <button wire:click="$set('orderType', '{{ $type }}')"
                            class="flex-1 py-2 text-[11px] font-bold rounded shadow-sm transition-colors border
                            {{ $orderType === $type ? 'bg-primary text-primary-content border-primary' : 'bg-base-100 border-base-300 hover:bg-base-200 text-base-content/70' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Context Actions (Customer / Table / Livreur) --}}
            <div class="p-2 border-b border-base-300 grid grid-cols-2 gap-2 text-xs font-medium bg-base-100">
                @if($orderType === 'dine_in')
                    <button wire:click="$set('showTableSelector', true)" class="btn btn-sm btn-outline btn-block h-10 border-base-300 bg-base-100">
                        <i data-lucide="grid" class="w-4 h-4 mr-1 text-primary"></i> 
                        <span class="truncate">{{ $selectedTable ? 'Table ' . $selectedTable->name : 'Choisir Table' }}</span>
                    </button>
                @else
                    <input wire:model.live="customerName" type="text" placeholder="Nom du client"
                           class="input input-sm h-10 bg-base-100 border-base-300 w-full focus:outline-primary font-normal">
                @endif
                
                <button wire:click="$set('showCustomerSelector', true)" class="btn btn-sm btn-outline btn-block h-10 border-base-300 bg-base-100">
                    <i data-lucide="user" class="w-4 h-4 mr-1 text-secondary"></i>
                    <span class="truncate">{{ $selectedCustomer ? $selectedCustomer->full_name : 'Client' }}</span>
                </button>
            </div>

            {{-- Cart Items List (Order Lines) --}}
            <div class="flex-1 overflow-y-auto scroll-slim bg-base-100 p-0 relative"
                 x-data="{ activeLine: null }">
                @if(count($cart) === 0)
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-base-content/30">
                        <i data-lucide="shopping-cart" class="w-16 h-16 mb-4"></i>
                        <p>Le panier est vide</p>
                    </div>
                @else
                    <table class="w-full text-left text-sm">
                        <tbody>
                            @foreach($cart as $key => $item)
                                <tr wire:key="cart-{{ $key }}" 
                                    @click="activeLine = '{{ $key }}'; selectedCartKey = '{{ $key }}'"
                                    class="border-b border-base-200 cursor-pointer transition-colors relative
                                    {{ $currentCartKey === $key ? 'bg-primary/10 border-l-4 border-l-primary' : 'hover:bg-base-200 border-l-4 border-l-transparent' }}">
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-[13px] leading-tight text-base-content">{{ $item['name'] }}</div>
                                        <div class="text-[11px] text-base-content/60 mt-1 flex items-center gap-3">
                                            <span><span class="font-bold text-base-content">{{ $item['qty'] }}</span> Units</span>
                                            <span>à {{ number_format($this->displayAmount((float) $item['price']), $selectedDisplayCurrency === 'CDF' ? 0 : 2, ',', ' ') }} {{ $this->display_currency_symbol }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-right font-black text-[13px] text-base-content align-top">
                                        {{ number_format($this->displayAmount((float) $item['total']), $selectedDisplayCurrency === 'CDF' ? 0 : 2, ',', ' ') }} {{ $this->display_currency_symbol }}
                                    </td>
                                    
                                    {{-- Inline quantity controls --}}
                                    <td class="py-3 pr-2 w-16 align-top">
                                        <div class="flex flex-col items-center gap-1 opacity-0 hover:opacity-100" :class="activeLine === '{{ $key }}' ? 'opacity-100' : ''">
                                            <button wire:click.stop="incrementQty('{{ $key }}')" class="btn btn-xs btn-square btn-ghost text-success"><i data-lucide="plus" class="w-4 h-4"></i></button>
                                            <button wire:click.stop="decrementQty('{{ $key }}')" class="btn btn-xs btn-square btn-ghost text-error"><i data-lucide="minus" class="w-4 h-4"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Global Totals --}}
            <div class="px-4 py-3 bg-base-100 border-t border-base-300 text-sm shadow-[0_-4px_6px_-1px_rgb(0,0,0,0.05)] z-10">
                @if($this->discount_value > 0)
                    <div class="flex justify-between text-error font-medium mb-1">
                        <span>Remise</span>
                        <span>-{{ number_format($this->displayAmount($this->discount_value), $selectedDisplayCurrency === 'CDF' ? 0 : 2, ',', ' ') }} {{ $this->display_currency_symbol }}</span>
                    </div>
                @endif
                <div class="flex justify-between items-baseline">
                    <span class="font-bold text-base-content/70">Total</span>
                    <span class="text-3xl font-black text-base-content">{{ number_format($this->displayAmount($this->total), $selectedDisplayCurrency === 'CDF' ? 0 : 2, ',', ' ') }} <span class="text-sm font-bold opacity-60">{{ $this->display_currency_symbol }}</span></span>
                </div>
            </div>

            {{-- Numpad (Integrated, Odoo Style) --}}
            <div class="p-2 bg-base-200 border-t border-base-300 grid grid-cols-4 gap-1 h-[220px] flex-shrink-0"
                 x-data="{
                     mode: 'qty',
                     notImplemented() { alert('Cette fonction du pavé numérique nécessite une mise à jour du backend.'); }
                 }">
                
                {{-- Modes --}}
                <div class="col-span-1 flex flex-col gap-1">
                    <button type="button" @click="mode = 'qty'" class="btn btn-square w-full h-full text-sm font-bold rounded" :class="mode === 'qty' ? 'btn-neutral' : 'btn-ghost bg-base-100 border border-base-300'">Qté</button>
                    <button type="button" @click="mode = 'disc'" class="btn btn-square w-full h-full text-sm font-bold rounded" :class="mode === 'disc' ? 'btn-neutral' : 'btn-ghost bg-base-100 border border-base-300'">Remise</button>
                    <button type="button" @click="mode = 'price'" class="btn btn-square w-full h-full text-sm font-bold rounded" :class="mode === 'price' ? 'btn-neutral' : 'btn-ghost bg-base-100 border border-base-300'">Prix</button>
                    <button type="button" @click="notImplemented()" class="btn btn-square w-full h-full bg-base-100 border border-base-300 text-error hover:bg-error hover:text-error-content rounded"><i data-lucide="delete" class="w-5 h-5"></i></button>
                </div>

                {{-- Digits --}}
                <div class="col-span-3 grid grid-cols-3 gap-1">
                    @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9] as $d)
                        <button type="button" @click="notImplemented()" class="btn btn-square w-full h-full bg-base-100 border border-base-300 text-lg font-bold rounded hover:bg-base-200">{{ $d }}</button>
                    @endforeach
                    <button type="button" @click="notImplemented()" class="btn btn-square w-full h-full bg-base-100 border border-base-300 text-lg font-bold rounded hover:bg-base-200">+/-</button>
                    <button type="button" @click="notImplemented()" class="btn btn-square w-full h-full bg-base-100 border border-base-300 text-lg font-bold rounded hover:bg-base-200">0</button>
                    <button type="button" @click="notImplemented()" class="btn btn-square w-full h-full bg-base-100 border border-base-300 text-lg font-bold rounded hover:bg-base-200">.</button>
                </div>
            </div>

            {{-- Action Buttons (Pay, Delete) --}}
            <div class="flex gap-1 p-2 bg-base-200 border-t border-base-300">
                <button wire:click="removeFromCart('{{ $currentCartKey ?? '' }}')" class="btn btn-error btn-square w-14 rounded" title="Supprimer la ligne" @if(empty($cart)) disabled @endif>
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
                <button wire:click="requestClearCart" class="btn btn-outline btn-square w-14 border-base-300 bg-base-100 rounded text-error" title="Vider tout" @if(count($cart) === 0) disabled @endif>
                    <i data-lucide="file-x" class="w-5 h-5"></i>
                </button>
                @if($provisionalReceiptEnabled)
                    <button wire:click="printProvisional" class="btn btn-outline btn-square w-14 border-cyan-300 bg-cyan-50 text-cyan-700 dark:border-cyan-800 dark:bg-cyan-950/40 dark:text-cyan-300 rounded" title="Imprimer {{ $provisionalReceiptType === 'addition' ? 'l’addition' : 'le reçu proforma' }}" @if(count($cart) === 0) disabled @endif>
                        <i data-lucide="receipt-text" class="w-5 h-5"></i>
                    </button>
                @endif
                <button wire:click="openPaymentModal" class="btn btn-success flex-1 rounded text-success-content text-xl font-bold border-b-4 border-b-success/50 active:border-b-0 active:translate-y-1 transition-all" @if(count($cart) === 0) disabled @endif>
                    Paiement <i data-lucide="chevron-right" class="w-6 h-6 ml-1"></i>
                </button>
            </div>
        </aside>
    </div>

    {{-- ══ MODALS ══ --}}
    
    {{-- MODAL PAIEMENT --}}
    @if($showPayment)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-neutral/60 backdrop-blur-sm p-4">
            <div class="bg-base-100 rounded-lg shadow-2xl w-full max-w-4xl flex flex-col md:flex-row overflow-y-auto md:overflow-hidden max-h-[96vh]">
                
                {{-- Payment Left: Numpad --}}
                <div class="w-full md:w-[320px] bg-base-200 p-4 md:p-6 border-r border-base-300 flex flex-col max-h-[48vh] md:max-h-none">
                    <h3 class="text-xl font-bold mb-6">Paiement</h3>
                    
                    <div class="flex-1 flex flex-col"
                         x-data="{
                             buf: '',
                             push(d) { if (this.buf.length < 9) { this.buf += d; this.sync(); } },
                             back()  { this.buf = this.buf.slice(0, -1); this.sync(); },
                             preset(v) { this.buf = String(v); this.sync(); },
                             sync()  { $wire.set('cashReceived', Number(this.buf || 0)); }
                         }">
                        
                        <div class="bg-base-100 p-4 rounded-lg border border-base-300 mb-6 text-right shadow-sm">
                            <p class="text-xs font-bold text-base-content/50 uppercase">Montant Reçu</p>
                            <p class="text-3xl font-black mt-1 text-primary">{{ number_format((float) $cashReceived, $selectedPaymentCurrency === 'CDF' ? 0 : 2, ',', ' ') }} {{ $selectedPaymentCurrency === 'CDF' ? 'FC' : $selectedPaymentCurrency }}</p>
                        </div>

                        {{-- Quick Presets --}}
                        <div class="grid grid-cols-2 gap-2 mb-6">
                            @foreach($selectedPaymentCurrency === 'CDF' ? [5000, 10000, 20000, 50000] : [5, 10, 20, 50] as $amount)
                                <button type="button" @click="preset({{ $amount }})" class="btn btn-outline border-base-300 bg-base-100 font-black">{{ number_format($amount, 0, ',', ' ') }}</button>
                            @endforeach
                        </div>

                        {{-- Digits --}}
                        <div class="grid grid-cols-3 gap-2 flex-1 auto-rows-fr">
                            @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9] as $d)
                                <button type="button" @click="push('{{ $d }}')" class="btn btn-square w-full h-full bg-base-100 border-base-300 text-2xl font-black hover:bg-base-300">{{ $d }}</button>
                            @endforeach
                            <button type="button" @click="push('00')" class="btn btn-square w-full h-full bg-base-100 border-base-300 text-xl font-black hover:bg-base-300">00</button>
                            <button type="button" @click="push('0')" class="btn btn-square w-full h-full bg-base-100 border-base-300 text-2xl font-black hover:bg-base-300">0</button>
                            <button type="button" @click="back()" class="btn btn-square w-full h-full bg-base-100 border-base-300 text-error hover:bg-error hover:text-error-content"><i data-lucide="delete" class="w-8 h-8"></i></button>
                        </div>
                    </div>
                </div>

                {{-- Payment Right: Summary & Methods --}}
                <div class="flex-1 p-5 md:p-8 flex flex-col bg-base-100 relative min-w-0">
                    <button wire:click="$set('showPayment', false)" class="absolute top-6 right-6 btn btn-square btn-ghost"><i data-lucide="x" class="w-6 h-6"></i></button>

                    <div class="mb-6 mt-8 flex justify-center gap-2">
                        @foreach($paymentCurrencies as $currency)
                            <button type="button" wire:click="$set('selectedPaymentCurrency', '{{ $currency->code }}')" class="btn btn-sm {{ $selectedPaymentCurrency === $currency->code ? 'btn-primary' : 'btn-outline' }}">{{ $currency->code }}</button>
                        @endforeach
                    </div>

                    <div class="mb-10 text-center">
                        <p class="text-sm font-bold text-base-content/50 uppercase">Reste à payer</p>
                        <p class="text-6xl font-black text-primary mt-4">{{ number_format($this->payment_currency_total, $selectedPaymentCurrency === 'CDF' ? 0 : 2, ',', ' ') }} <span class="text-2xl opacity-60">{{ $selectedPaymentCurrency === 'CDF' ? 'FC' : $selectedPaymentCurrency }}</span></p>
                        @if($selectedPaymentCurrency !== 'CDF')
                            <p class="mt-2 text-xs text-base-content/50">Taux : 1 {{ $selectedPaymentCurrency }} = {{ number_format($this->payment_currency_rate, 2, ',', ' ') }} CDF</p>
                        @endif
                    </div>

                    @if($this->change_amount > 0)
                        <div class="bg-success/10 border-2 border-success/30 rounded-xl p-6 text-center mb-10">
                            <p class="text-sm font-bold text-success uppercase">Monnaie à rendre</p>
                            <p class="text-5xl font-black text-success mt-2">{{ number_format($this->change_amount, $selectedPaymentCurrency === 'CDF' ? 0 : 2, ',', ' ') }} <span class="text-xl">{{ $selectedPaymentCurrency === 'CDF' ? 'FC' : $selectedPaymentCurrency }}</span></p>
                        </div>
                    @endif

                    <div class="flex-1"></div>

                    <p class="text-sm font-bold text-base-content/50 uppercase mb-4">Valider avec :</p>
                    <div class="grid grid-cols-2 gap-4">
                        @foreach($paymentMethods as $method)
                            <button wire:click="processPayment('{{ $method->code }}')" wire:key="pm-{{ $method->code }}"
                                    class="btn h-20 text-lg font-bold border-2 rounded-xl transition-all active:scale-95
                                    {{ $method->code === 'cash' ? 'btn-success text-success-content border-success' : 'btn-outline border-base-300 bg-base-100 hover:border-primary hover:bg-primary/5' }}">
                                @switch($method->code)
                                    @case('cash')<i data-lucide="banknote" class="w-8 h-8 mr-3"></i>@break
                                    @case('card')<i data-lucide="credit-card" class="w-8 h-8 mr-3"></i>@break
                                    @default<i data-lucide="smartphone" class="w-8 h-8 mr-3"></i>
                                @endswitch
                                {{ $method->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <flux:modal wire:model.self="showClearCartModal" class="md:w-[28rem]">
        <div class="space-y-6"><div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#ef4444"><flux:icon.shopping-cart class="size-5"/></div><div><flux:heading size="lg">Vider le panier ?</flux:heading><flux:text>Tous les articles et les informations de cette commande seront retirés.</flux:text></div></div><div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button wire:click="$set('showClearCartModal', false)">Conserver</flux:button><flux:button wire:click="confirmClearCart" variant="danger" icon="trash">Vider le panier</flux:button></div></div>
    </flux:modal>

    {{-- MODAL TABLES --}}
    @if($showTableSelector)
        <div class="fixed inset-0 z-50 flex flex-col bg-base-200">
            @php
                $occupiedTablesCount = $tables->filter(fn ($table) => $table->status->value === 'occupied')->count();
                $tablesWithOrdersCount = $tables->filter(function ($table) use ($tablesCarts) {
                    $tableKey = 'table_'.$table->id;
                    return ! empty($tablesCarts[$tableKey]['cart'] ?? []) || (int) $table->active_orders_count > 0;
                })->count();
            @endphp
            <header class="flex min-h-16 items-center justify-between gap-3 bg-base-100 px-4 py-3 shadow-sm sm:px-6">
                <div class="flex min-w-0 items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"><flux:icon.table-cells class="size-5" /></span><div class="min-w-0"><h3 class="truncate text-lg font-black sm:text-xl">Sélection de table</h3><p class="text-xs text-base-content/50">{{ $tablesWithOrdersCount }} commande(s) en cours · {{ $occupiedTablesCount }} occupée(s)</p></div></div>
                <button wire:click="$set('showTableSelector', false)" class="btn btn-circle btn-ghost"><i data-lucide="x" class="w-6 h-6"></i></button>
            </header>
            
            <div class="bg-base-100 px-4 pb-4 shadow-sm sm:px-6">
                <div class="scrollbar-hide flex gap-2 overflow-x-auto py-2">
                    <button wire:click="$set('selectedZone', '')" class="btn btn-sm shrink-0 rounded-xl {{ !$selectedZone ? 'btn-primary' : 'btn-outline border-base-300 bg-base-100' }}">Toutes les zones</button>
                    @foreach($zones as $zone)
                        <button wire:click="$set('selectedZone', '{{ $zone->id }}')" class="btn btn-sm shrink-0 rounded-xl {{ $selectedZone === $zone->id ? 'btn-primary' : 'btn-outline border-base-300 bg-base-100' }}">
                            <span class="size-2.5 rounded-full" style="background: {{ $zone->color }}"></span>{{ $zone->name }}
                        </button>
                    @endforeach
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-2 text-[11px] font-bold text-base-content/60" aria-label="Légende des tables">
                    <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-success"></span>Libre</span>
                    <span class="inline-flex items-center gap-1.5"><span class="grid size-5 place-items-center rounded-full bg-error text-error-content"><flux:icon.lock-closed class="size-3" /></span>Occupée</span>
                    <span class="inline-flex items-center gap-1.5"><span class="grid size-5 place-items-center rounded-md bg-amber-500 text-white"><flux:icon.receipt-percent class="size-3" /></span>Commande en cours</span>
                    <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-violet-500"></span>Réservée</span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-3 sm:p-5 lg:p-6">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6 xl:grid-cols-8">
                    @foreach($tables as $tbl)
                        @php
                            $tblKey = 'table_' . $tbl->id;
                            $hasCartInProgress = ! empty($tablesCarts[$tblKey]['cart'] ?? []);
                            $activeOrdersCount = (int) $tbl->active_orders_count;
                            $hasOrderInProgress = $hasCartInProgress || $activeOrdersCount > 0;
                            $isOccupied = $tbl->status->value === 'occupied';
                            $isReserved = $tbl->status->value === 'reserved';
                            $needsCleaning = $tbl->status->value === 'cleaning';
                        @endphp
                        <button wire:click="selectTable('{{ $tbl->id }}')"
                                class="pos-table-card {{ $hasOrderInProgress ? 'pos-table-card--order' : ($isOccupied ? 'pos-table-card--occupied' : ($isReserved ? 'pos-table-card--reserved' : ($needsCleaning ? 'pos-table-card--cleaning' : 'pos-table-card--free'))) }} {{ $selectedTableId === $tbl->id ? 'pos-table-card--selected' : '' }}">
                            @if($isOccupied)
                                <span class="absolute right-2 top-2 grid size-7 place-items-center rounded-full bg-error text-error-content shadow-lg shadow-error/25" title="Table occupée" aria-label="Table occupée"><flux:icon.lock-closed class="size-3.5" /></span>
                            @endif
                            <span class="text-[10px] font-black uppercase tracking-widest text-base-content/40">Table</span>
                            <span class="mt-0.5 text-2xl font-black sm:text-3xl">{{ $tbl->number }}</span>
                            <span class="mt-1 flex items-center gap-1 text-[11px] font-bold text-base-content/55"><flux:icon.user-group class="size-3.5" />{{ $tbl->capacity }} pers.</span>
                            @if($hasOrderInProgress)
                                <span class="mt-2 inline-flex max-w-full items-center gap-1 rounded-lg bg-amber-500 px-2 py-1 text-[9px] font-black uppercase tracking-wide text-white shadow-sm" title="Commande en cours" aria-label="Commande en cours"><flux:icon.receipt-percent class="size-3" /><span>En cours</span>@if($activeOrdersCount > 1)<span class="rounded bg-white/20 px-1">{{ $activeOrdersCount }}</span>@endif</span>
                            @elseif($isReserved)
                                <span class="mt-2 rounded-lg bg-violet-500/12 px-2 py-1 text-[9px] font-black uppercase text-violet-600 dark:text-violet-300">Réservée</span>
                            @elseif($needsCleaning)
                                <span class="mt-2 rounded-lg bg-slate-500/12 px-2 py-1 text-[9px] font-black uppercase text-slate-600 dark:text-slate-300">À nettoyer</span>
                            @elseif($isOccupied)
                                <span class="mt-2 rounded-lg bg-error/10 px-2 py-1 text-[9px] font-black uppercase text-error">Occupée</span>
                            @else
                                <span class="mt-2 rounded-lg bg-success/10 px-2 py-1 text-[9px] font-black uppercase text-success">Libre</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL CUSTOMERS --}}
    @if($showCustomerSelector)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-neutral/60 backdrop-blur-sm p-4">
            <div class="bg-base-100 rounded-xl shadow-2xl w-full max-w-2xl flex flex-col max-h-[85vh]">
                <div class="p-6 border-b border-base-300 flex justify-between items-center bg-base-200 rounded-t-xl">
                    <h3 class="text-xl font-bold">Sélectionner un client</h3>
                    <button wire:click="$set('showCustomerSelector', false)" class="btn btn-circle btn-ghost"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <div class="p-4 border-b border-base-300 bg-base-100">
                    <div class="relative">
                        <i data-lucide="search" class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 opacity-50"></i>
                        <input wire:model.live.debounce.300ms="customerSearch" type="text" placeholder="Rechercher par nom, téléphone..."
                               class="input input-bordered input-lg w-full pl-12 focus:border-primary focus:ring-primary text-base">
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-4 bg-base-100 rounded-b-xl">
                    @forelse($customers as $cust)
                        <button wire:click="selectCustomer('{{ $cust->id }}')"
                                class="w-full p-4 mb-2 flex items-center gap-4 bg-base-100 border border-base-300 hover:border-primary hover:bg-primary/5 rounded-lg transition-all text-left">
                            <div class="w-12 h-12 bg-primary/10 text-primary font-black text-lg rounded-lg flex items-center justify-center flex-shrink-0">
                                {{ $cust->initials }}
                            </div>
                            <div class="flex-1">
                                <div class="font-bold text-base">{{ $cust->full_name }}</div>
                                <div class="text-sm opacity-60 mt-0.5"><i data-lucide="phone" class="w-3 h-3 inline"></i> {{ $cust->phone }}</div>
                            </div>
                            <i data-lucide="chevron-right" class="w-5 h-5 opacity-30"></i>
                        </button>
                    @empty
                        <div class="p-12 text-center opacity-50 flex flex-col items-center">
                            <i data-lucide="users" class="w-16 h-16 mb-4"></i>
                            <p class="text-lg font-medium">Aucun client trouvé.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL SUCCESS --}}
    @if($showSuccess)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-neutral/80 backdrop-blur-sm">
            <div class="bg-base-100 rounded-2xl shadow-2xl p-10 w-full max-w-md text-center">
                <div class="w-24 h-24 bg-success/20 text-success rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="check" class="w-12 h-12"></i>
                </div>
                <h3 class="text-3xl font-black mb-3">Paiement validé</h3>
                <p class="text-base-content/60 mb-8 text-lg">Commande <span class="font-black text-primary">{{ $lastOrderNumber }}</span></p>
                
                <button wire:click="closeSuccess" class="btn btn-primary btn-block btn-lg rounded-xl">
                    Nouvelle Commande
                </button>
            </div>
        </div>
    @endif

    {{-- MODAL OPEN SESSION --}}
    @if($showOpenSession)
        <div class="pos-session-overlay" style="z-index: 70">
            <div class="pos-session-modal pos-session-modal--open max-w-4xl"
                 role="dialog" aria-modal="true" aria-labelledby="open-session-title"
                 x-data="{
                     buf: '',
                     push(d) { if (this.buf.length < 9) { this.buf += d; this.sync(); } },
                     back()  { this.buf = this.buf.slice(0, -1); this.sync(); },
                     sync()  { $wire.set('openingAmount', Number(this.buf || 0)); }
                 }">
                
                <div class="pos-session-modal__header pos-session-modal__header--open">
                    <svg aria-hidden="true" class="pos-session-modal__art" viewBox="0 0 420 180" fill="none"><circle cx="338" cy="54" r="76" stroke="currentColor" stroke-width="2" stroke-dasharray="8 12"/><circle cx="338" cy="54" r="46" stroke="currentColor" stroke-width="13" opacity=".3"/><path d="M15 154c96-75 167 27 247-39s126 12 178-33" stroke="currentColor" stroke-width="2"/></svg>
                    <div class="relative flex min-w-0 items-center gap-4">
                        <div class="pos-session-modal__hero-icon"><flux:icon.lock-open class="size-7 sm:size-8" /></div>
                        <div class="min-w-0">
                            <span class="pos-session-modal__eyebrow">Début de service</span>
                            <h3 id="open-session-title" class="mt-1 truncate text-2xl font-black sm:text-3xl">Ouverture de caisse</h3>
                            <p class="mt-1 flex items-center gap-2 truncate text-sm text-white/80"><flux:icon.user-circle class="size-4 shrink-0" />{{ auth()->user()->name }} · {{ now()->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    <div class="relative hidden rounded-2xl bg-white/12 px-4 py-3 text-right shadow-inner sm:block"><span class="block text-[10px] font-black uppercase tracking-widest text-white/65">Taux actif</span><strong class="text-sm">1 USD = {{ number_format($this->usd_cdf_rate, 2, ',', ' ') }} CDF</strong></div>
                </div>

                <div class="pos-session-modal__body grid md:grid-cols-[minmax(0,1fr)_280px]">
                    <div class="space-y-5 p-4 sm:p-6">
                        <label class="form-control w-full">
                            <span class="label-text mb-2 flex items-center gap-2 font-bold"><span class="pos-session-field-icon"><flux:icon.computer-desktop class="size-4" /></span>Caisse à ouvrir</span>
                            <select wire:model="selectedCashRegisterId" class="select select-bordered pos-session-input w-full">
                                <option value="">Choisir une caisse</option>
                                @foreach($cashRegisters as $register)<option value="{{ $register->id }}">{{ $register->name }}</option>@endforeach
                            </select>
                        </label>
                        @error('selectedCashRegisterId')<p class="text-sm text-error">{{ $message }}</p>@enderror
                        <div class="pos-session-amount-card">
                            <div class="flex items-center justify-center gap-2 text-xs font-black uppercase tracking-wider text-base-content/50"><flux:icon.wallet class="size-4 text-primary" />Fond de caisse initial</div>
                            <p class="mt-2 text-3xl font-black text-primary sm:text-4xl">{{ number_format((float) $openingAmount, 0, ',', ' ') }} <span class="text-lg sm:text-xl">FC</span></p>
                            <p class="mt-2 text-xs text-base-content/45">Saisissez le montant avec le pavé numérique.</p>
                        </div>
                        <label class="form-control w-full">
                            <span class="label-text mb-2 flex items-center gap-2 font-bold"><span class="pos-session-field-icon"><flux:icon.pencil-square class="size-4" /></span>Note d’ouverture <span class="badge badge-sm badge-ghost">Facultatif</span></span>
                            <textarea wire:model="openingNote" class="textarea textarea-bordered pos-session-input w-full" rows="3" placeholder="Ex. fond remis par le responsable"></textarea>
                        </label>
                        @error('openingAmount')<p class="text-sm text-error">{{ $message }}</p>@enderror
                        @error('openingNote')<p class="text-sm text-error">{{ $message }}</p>@enderror
                        <div class="pos-session-info"><flux:icon.shield-check class="size-5 shrink-0 text-primary" /><p><strong class="block">Session sécurisée</strong><span class="text-xs text-base-content/55">Toutes les opérations seront rattachées à cette caisse et à cet utilisateur.</span></p></div>
                    </div>
                    <div class="pos-session-keypad">
                        <div class="col-span-3 mb-1 flex items-center justify-between"><span class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-base-content/55"><flux:icon.calculator class="size-4" />Pavé numérique</span><button type="button" @click="buf = ''; sync()" class="btn btn-xs btn-ghost text-error">Effacer</button></div>
                        @foreach([1,2,3,4,5,6,7,8,9] as $d)<button type="button" @click="push('{{ $d }}')" class="pos-session-key">{{ $d }}</button>@endforeach
                        <button type="button" @click="push('00')" class="pos-session-key">00</button>
                        <button type="button" @click="push('0')" class="pos-session-key">0</button>
                        <button type="button" @click="back()" class="pos-session-key pos-session-key--danger"><flux:icon.backspace class="size-6" /></button>
                    </div>
                </div>

                <div class="pos-session-modal__footer">
                    <p class="hidden items-center gap-2 text-xs text-base-content/50 sm:flex"><flux:icon.information-circle class="size-4" />Vérifiez le fond avant de commencer.</p>
                    <button wire:click="openSession" wire:loading.attr="disabled" class="btn btn-primary h-12 w-full rounded-xl px-6 font-black shadow-lg shadow-primary/25 sm:w-auto">
                        <span wire:loading.remove wire:target="openSession" class="flex items-center gap-2"><flux:icon.play class="size-5" />Ouvrir la caisse <flux:icon.arrow-right class="size-4" /></span>
                        <span wire:loading wire:target="openSession" class="items-center gap-2"><span class="loading loading-spinner loading-sm"></span>Ouverture…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL CLOSE SESSION --}}
    @if($showCloseSession)
        <div class="pos-session-overlay" style="z-index: 80">
            <div class="pos-session-modal pos-session-modal--close max-w-5xl" role="dialog" aria-modal="true" aria-labelledby="close-session-title">
                <svg aria-hidden="true" class="pos-session-modal__art" viewBox="0 0 320 180" fill="none">
                    <circle cx="160" cy="160" r="124" stroke="currentColor" stroke-width="2" stroke-dasharray="10 12"/>
                    <circle cx="160" cy="160" r="88" stroke="currentColor" stroke-width="18" opacity=".45"/>
                    <path d="M126 158h68M160 124v68" stroke="currentColor" stroke-width="10" stroke-linecap="round"/>
                </svg>
                <div class="pos-session-modal__header pos-session-modal__header--close">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="pos-session-modal__hero-icon">
                            <flux:icon.lock-closed class="size-7 sm:size-8" />
                        </div>
                        <div class="min-w-0">
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <span class="pos-session-modal__eyebrow">Fin de service</span>
                                <span class="flex items-center gap-1 text-xs text-white/80"><flux:icon.clock class="size-3.5" />{{ $this->active_cash_session?->opened_at?->diffForHumans(short: true) }}</span>
                            </div>
                            <h3 id="close-session-title" class="truncate text-2xl font-black sm:text-3xl">Clôture de caisse</h3>
                            <p class="mt-1 flex items-center gap-2 truncate text-sm text-white/80"><flux:icon.computer-desktop class="size-4 shrink-0" />{{ $this->active_cash_session?->cashRegister?->name }} · ouverte à {{ $this->active_cash_session?->opened_at?->format('H:i') }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showCloseSession', false)" class="btn btn-circle shrink-0 border-white/25 bg-white/15 text-white shadow-sm hover:rotate-90 hover:bg-white/25" title="Fermer la modal" aria-label="Fermer la modal"><flux:icon.x-mark class="size-6" /></button>
                </div>
                <div class="pos-session-modal__body grid gap-5 p-4 sm:p-6 md:grid-cols-2">
                    <div class="space-y-3">
                        <h4 class="flex items-center gap-2 font-black uppercase tracking-wide text-base-content/60"><flux:icon.chart-bar class="size-5 text-primary" />Résumé de session</h4>
                        <div class="pos-session-summary space-y-2.5 p-3 text-sm sm:p-4">
                            <div class="pos-session-summary-row">
                                <span class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400"><flux:icon.wallet class="size-4" /></span><span>Fond initial</span></span>
                                <strong class="whitespace-nowrap">{{ number_format((float) $this->active_cash_session?->opening_amount, 0, ',', ' ') }} FC</strong>
                            </div>
                            <div class="pos-session-summary-row bg-emerald-500/5">
                                <span class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-emerald-500/15 text-emerald-600 dark:text-emerald-400"><flux:icon.banknotes class="size-4" /></span><span>Ventes en espèces</span></span>
                                <strong class="whitespace-nowrap text-success">+ {{ number_format($this->session_cash_sales, 0, ',', ' ') }} FC</strong>
                            </div>
                            <div class="pos-session-summary-row bg-violet-500/5">
                                <span class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-violet-500/15 text-violet-600 dark:text-violet-400"><flux:icon.credit-card class="size-4" /></span><span>Paiements non espèces</span></span>
                                <strong class="whitespace-nowrap">{{ number_format($this->session_non_cash_sales, 0, ',', ' ') }} FC</strong>
                            </div>
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-gradient-to-r from-primary to-blue-600 p-4 text-primary-content shadow-lg shadow-primary/20">
                                <span class="flex items-center gap-3 font-bold"><span class="grid size-10 place-items-center rounded-xl bg-white/15"><flux:icon.building-library class="size-5" /></span>Espèces attendues</span>
                                <strong class="whitespace-nowrap text-lg sm:text-xl">{{ number_format($this->session_expected_cash, 0, ',', ' ') }} FC</strong>
                            </div>
                        </div>
                        <div class="pos-session-info bg-warning/10"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-warning/20 text-warning-content"><flux:icon.shield-exclamation class="size-5" /></span><p><strong class="block">Session protégée</strong><span class="text-xs opacity-70">La sortie du point de vente sera autorisée uniquement après cette clôture.</span></p></div>
                    </div>
                    <div class="min-w-0 space-y-4">
                        <h4 class="flex items-center gap-2 font-black uppercase tracking-wide text-base-content/60"><flux:icon.calculator class="size-5 text-primary" />Comptage physique</h4>
                        <label class="flex min-w-0 flex-col gap-2"><span class="label-text flex items-center gap-2 font-bold"><span class="pos-session-field-icon"><flux:icon.banknotes class="size-4" /></span>Espèces CDF</span><div class="join flex w-full shadow-sm"><input wire:model.live.debounce.250ms="closingCdfAmount" type="number" min="0" step="1" class="input input-bordered pos-session-input join-item min-w-0 flex-1 text-lg font-black"><span class="join-item grid shrink-0 place-items-center bg-blue-600 px-4 font-black text-white">FC</span></div></label>
                        <label class="flex min-w-0 flex-col gap-2"><span class="label-text flex items-center gap-2 font-bold"><span class="pos-session-field-icon !bg-emerald-500/10 !text-emerald-600"><flux:icon.currency-dollar class="size-4" /></span>Espèces USD</span><div class="join flex w-full shadow-sm"><input wire:model.live.debounce.250ms="closingUsdAmount" type="number" min="0" step="0.01" class="input input-bordered pos-session-input join-item min-w-0 flex-1 text-lg font-black"><span class="join-item grid shrink-0 place-items-center bg-emerald-600 px-4 font-black text-white">USD</span></div><span class="flex items-center gap-1.5 text-xs opacity-60"><flux:icon.arrows-right-left class="size-3.5" />Converti au taux 1 USD = {{ number_format($this->usd_cdf_rate, 2, ',', ' ') }} CDF</span></label>
                        <div class="relative overflow-hidden rounded-2xl border-2 p-4 {{ abs($this->closing_difference) < 1 ? 'border-success/30 bg-success/10' : 'border-error/30 bg-error/10' }}">
                            @if(abs($this->closing_difference) < 1)<flux:icon.check-circle class="absolute -bottom-3 -right-3 size-20 opacity-10" />@else<flux:icon.exclamation-triangle class="absolute -bottom-3 -right-3 size-20 opacity-10" />@endif
                            <div class="relative flex justify-between gap-3"><span class="flex items-center gap-2"><flux:icon.banknotes class="size-4" />Total compté</span><strong>{{ number_format($this->closing_total, 0, ',', ' ') }} FC</strong></div>
                            <div class="relative mt-3 flex items-center justify-between gap-3 border-t border-current/10 pt-3 text-lg"><span class="flex items-center gap-2 font-bold"><flux:icon.scale class="size-5" />Écart</span><strong class="text-xl">{{ $this->closing_difference > 0 ? '+' : '' }}{{ number_format($this->closing_difference, 0, ',', ' ') }} FC</strong></div>
                        </div>
                        <label class="flex flex-col gap-2"><span class="label-text flex items-center gap-2 font-bold"><span class="pos-session-field-icon"><flux:icon.pencil-square class="size-4" /></span>Note de clôture <span class="badge badge-sm {{ abs($this->closing_difference) >= 1 ? 'badge-error' : 'badge-ghost' }}">{{ abs($this->closing_difference) >= 1 ? 'Obligatoire' : 'Facultatif' }}</span></span><textarea wire:model="closingNote" rows="3" class="textarea textarea-bordered pos-session-input w-full" placeholder="Expliquez tout écart constaté"></textarea></label>
                        @error('closingCdfAmount')<p class="text-sm text-error">{{ $message }}</p>@enderror
                        @error('closingUsdAmount')<p class="text-sm text-error">{{ $message }}</p>@enderror
                        @error('closingNote')<p class="text-sm text-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="pos-session-modal__footer flex-col-reverse sm:flex-row sm:justify-end">
                    <button type="button" wire:click="$set('showCloseSession', false)" class="btn h-12 rounded-xl border-base-300 bg-base-100 px-6 shadow-sm hover:-translate-y-0.5 hover:border-primary hover:text-primary"><flux:icon.arrow-left class="size-5" />Continuer les ventes</button>
                    <button type="button" wire:click="closeSession" wire:loading.attr="disabled" class="btn h-12 rounded-xl border-0 bg-gradient-to-r from-rose-600 to-red-500 px-6 text-white shadow-lg shadow-red-500/25 hover:-translate-y-0.5 hover:from-rose-700 hover:to-red-600">
                        <span wire:loading.remove wire:target="closeSession" class="flex items-center gap-2"><flux:icon.shield-check class="size-5" />Clôturer {{ $exitAfterClose === 'logout' ? 'et se déconnecter' : ($exitAfterClose === 'admin' ? 'et ouvrir l’administration' : 'la caisse') }}<flux:icon.arrow-right class="size-4" /></span>
                        <span wire:loading wire:target="closeSession" class="items-center gap-2"><span class="loading loading-spinner loading-sm"></span>Clôture en cours…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
