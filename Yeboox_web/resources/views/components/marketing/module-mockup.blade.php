@props([
    'type',
    'title',
])

<div {{ $attributes->merge(['class' => 'yb-desktop-mockup']) }}>
    <div class="flex h-7 items-center gap-1.5 border-b border-slate-200 bg-slate-100 px-3 dark:border-white/10 dark:bg-slate-800">
        <span class="size-2 rounded-full bg-[#ff695f]"></span>
        <span class="size-2 rounded-full bg-[#f8c34b]"></span>
        <span class="size-2 rounded-full bg-[#4fc66a]"></span>
        <span class="ml-3 rounded-md bg-white px-3 py-1 text-[6px] font-bold text-slate-400 shadow-sm dark:bg-slate-700 dark:text-slate-300">app.yeboox.com / {{ $type }}</span>
    </div>

    <div class="grid min-h-[250px] grid-cols-[18%_82%] bg-[#f5f7fb] dark:bg-[#090e1e] sm:min-h-[350px]">
        <aside class="flex flex-col bg-[#101a46] p-[7%] text-white">
            <div class="flex items-center gap-1.5">
                <img src="{{ asset('images/brand/yeboox-icon.png') }}" alt="" class="size-5 rounded-md bg-white p-0.5 sm:size-7">
                <span class="text-[7px] font-black sm:text-[10px]">Yeboox</span>
            </div>
            <div class="mt-[22%] space-y-[7%]">
                @foreach (['Vue générale', $title, 'Rapports', 'Paramètres'] as $item)
                    <div wire:key="mock-nav-{{ $type }}-{{ $loop->index }}" @class(['flex items-center gap-1.5 rounded-md px-[8%] py-[7%] text-[5px] font-bold sm:text-[8px]', 'bg-blue-500 text-white' => $loop->index === 1, 'text-blue-100/55' => $loop->index !== 1])>
                        <span class="size-1 rounded-full bg-current sm:size-1.5"></span><span class="truncate">{{ $item }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-auto rounded-lg bg-white/7 p-[9%] text-[5px] text-blue-100 sm:text-[7px]">
                <span class="block font-black text-white">Boutique Centrale</span>
                Kinshasa · En ligne
            </div>
        </aside>

        <div class="min-w-0 p-[3.5%]">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[5px] font-bold uppercase tracking-[.16em] text-blue-600 sm:text-[7px]">Yeboox Desktop</p>
                    <h3 class="mt-0.5 text-[10px] font-black text-[#101a46] dark:text-white sm:text-base">{{ $title }}</h3>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-[5px] font-black text-emerald-700 sm:text-[7px]">● Synchronisé</span>
                    <span class="grid size-5 place-items-center rounded-full bg-[#ffb547] text-[6px] font-black text-[#101a46] sm:size-7 sm:text-[8px]">AM</span>
                </div>
            </div>

            @if ($type === 'pos')
                <div class="mt-[3%] grid grid-cols-[1.45fr_.7fr] gap-[2.5%]">
                    <div>
                        <div class="flex gap-1.5 text-[5px] font-bold text-slate-500 sm:text-[7px]">
                            @foreach (['Tout', 'Boissons', 'Repas', 'Services'] as $category)
                                <span wire:key="mock-category-{{ $loop->index }}" @class(['rounded-full px-2 py-1', 'bg-[#3154f4] text-white' => $loop->first, 'bg-white dark:bg-white/10 dark:text-slate-300' => ! $loop->first])>{{ $category }}</span>
                            @endforeach
                        </div>
                        <div class="mt-[3%] grid grid-cols-3 gap-[2.5%]">
                            @foreach ([['Café Congo', '$3.50', '☕'], ['Jus Bissap', '$2.00', '🥤'], ['Poulet mayo', '$8.50', '🍗'], ['Madesu', '$6.00', '🥘'], ['Beignet', '$1.25', '◉'], ['Menu midi', '$12.00', '✦']] as [$product, $price, $emoji])
                                <div wire:key="mock-product-{{ $loop->index }}" class="rounded-lg bg-white p-[7%] shadow-sm dark:bg-white/7">
                                    <div class="grid aspect-[2/1] place-items-center rounded-md bg-gradient-to-br from-amber-50 to-orange-100 text-[12px] sm:text-xl">{{ $emoji }}</div>
                                    <p class="mt-[6%] truncate text-[5px] font-black text-slate-800 dark:text-white sm:text-[8px]">{{ $product }}</p>
                                    <p class="text-[5px] font-bold text-blue-600 sm:text-[7px]">{{ $price }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex flex-col rounded-xl bg-white p-[7%] shadow-sm dark:bg-white/7">
                        <p class="text-[7px] font-black text-[#101a46] dark:text-white sm:text-[10px]">Commande #1048</p>
                        <div class="mt-[8%] flex-1 space-y-[7%] text-[5px] text-slate-500 sm:text-[7px]">
                            <div class="flex justify-between"><span>Poulet mayo × 1</span><b class="text-slate-800 dark:text-white">$8.50</b></div>
                            <div class="flex justify-between"><span>Jus Bissap × 2</span><b class="text-slate-800 dark:text-white">$4.00</b></div>
                        </div>
                        <div class="border-t border-dashed border-slate-200 pt-[7%] dark:border-white/10"><div class="flex justify-between text-[7px] font-black text-[#101a46] dark:text-white sm:text-[10px]"><span>Total</span><span>$12.50</span></div><div class="mt-[7%] rounded-md bg-[#3154f4] py-[6%] text-center text-[5px] font-black text-white sm:text-[8px]">Encaisser</div></div>
                    </div>
                </div>
            @elseif (in_array($type, ['inventory', 'catalog'], true))
                <div class="mt-[3%] grid grid-cols-3 gap-[2%]">
                    @foreach ([['1 248', 'Articles'], ['34', 'Stock faible'], ['$18.4K', 'Valeur du stock']] as [$value, $label])
                        <div wire:key="mock-stock-stat-{{ $loop->index }}" class="rounded-lg bg-white p-[6%] shadow-sm dark:bg-white/7"><p class="text-[10px] font-black text-[#101a46] dark:text-white sm:text-lg">{{ $value }}</p><p class="text-[5px] text-slate-500 sm:text-[7px]">{{ $label }}</p></div>
                    @endforeach
                </div>
                <div class="mt-[3%] overflow-hidden rounded-xl bg-white shadow-sm dark:bg-white/7">
                    <div class="grid grid-cols-[1.5fr_.7fr_.7fr_.6fr] bg-slate-50 px-[3%] py-[2%] text-[5px] font-black uppercase text-slate-400 dark:bg-white/5 sm:text-[7px]"><span>Produit</span><span>Catégorie</span><span>Disponible</span><span>État</span></div>
                    @foreach ([['Café Congo 250g', 'Épicerie', '164', 'En stock'], ['Jus Bissap 33cl', 'Boissons', '18', 'Stock faible'], ['Savon naturel', 'Maison', '82', 'En stock'], ['Poulet frais', 'Cuisine', '9', 'À commander']] as $row)
                        <div wire:key="mock-stock-row-{{ $loop->index }}" class="grid grid-cols-[1.5fr_.7fr_.7fr_.6fr] items-center border-t border-slate-100 px-[3%] py-[2.5%] text-[5px] text-slate-500 dark:border-white/5 sm:text-[8px]"><b class="truncate text-slate-800 dark:text-white">{{ $row[0] }}</b><span>{{ $row[1] }}</span><span>{{ $row[2] }}</span><span @class(['font-bold', 'text-emerald-600' => $loop->index % 2 === 0, 'text-amber-600' => $loop->index % 2 !== 0])>{{ $row[3] }}</span></div>
                    @endforeach
                </div>
            @elseif (in_array($type, ['customers', 'teams'], true))
                <div class="mt-[3%] grid grid-cols-[.75fr_1.25fr] gap-[2.5%]">
                    <div class="rounded-xl bg-white p-[6%] shadow-sm dark:bg-white/7">
                        <p class="text-[7px] font-black text-[#101a46] dark:text-white sm:text-[10px]">{{ $type === 'customers' ? 'Clients récents' : 'Membres actifs' }}</p>
                        <div class="mt-[7%] space-y-[7%]">
                            @foreach ([['AM', 'Amina M.', 'Fidèle'], ['JM', 'Jean M.', 'Nouveau'], ['KL', 'Kenza L.', 'VIP'], ['PN', 'Patrick N.', 'Actif']] as $person)
                                <div wire:key="mock-person-{{ $loop->index }}" class="flex items-center gap-[5%]"><span class="grid size-5 shrink-0 place-items-center rounded-full bg-blue-100 text-[5px] font-black text-blue-700 sm:size-8 sm:text-[7px]">{{ $person[0] }}</span><span class="min-w-0 flex-1"><b class="block truncate text-[5px] text-slate-800 dark:text-white sm:text-[8px]">{{ $person[1] }}</b><span class="block text-[4px] text-slate-400 sm:text-[6px]">{{ $person[2] }}</span></span><span class="text-[5px] text-slate-300">›</span></div>
                            @endforeach
                        </div>
                    </div>
                    <div class="rounded-xl bg-[#101a46] p-[6%] text-white shadow-sm">
                        <p class="text-[5px] font-bold text-blue-200 sm:text-[7px]">Engagement ce mois</p><p class="mt-[2%] text-[15px] font-black sm:text-2xl">+18,6%</p>
                        <svg viewBox="0 0 300 105" class="mt-[5%] w-full" fill="none" aria-hidden="true"><path d="M2 86C33 79 48 44 78 57s44 31 70 2 39-42 67-16 45-7 83-32" stroke="#7ca3ff" stroke-width="5" stroke-linecap="round"/><path d="M2 86C33 79 48 44 78 57s44 31 70 2 39-42 67-16 45-7 83-32V105H2Z" fill="url(#customer-area)"/><defs><linearGradient id="customer-area" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#3154f4" stop-opacity=".55"/><stop offset="1" stop-color="#3154f4" stop-opacity="0"/></linearGradient></defs></svg>
                        <div class="mt-[4%] grid grid-cols-2 gap-[4%] text-[5px] sm:text-[7px]"><div class="rounded-lg bg-white/8 p-[7%]"><b class="block text-[9px] sm:text-sm">842</b>profils actifs</div><div class="rounded-lg bg-white/8 p-[7%]"><b class="block text-[9px] sm:text-sm">12 490</b>points accordés</div></div>
                    </div>
                </div>
            @elseif (in_array($type, ['invoicing', 'accounting'], true))
                <div class="mt-[3%] grid grid-cols-3 gap-[2%]">
                    @foreach ([['$32 450', 'Chiffre d’affaires'], ['$24 820', 'Paiements reçus'], ['$7 630', 'À recevoir']] as [$value, $label])
                        <div wire:key="mock-account-stat-{{ $loop->index }}" class="rounded-lg bg-white p-[6%] shadow-sm dark:bg-white/7"><p class="text-[9px] font-black text-[#101a46] dark:text-white sm:text-base">{{ $value }}</p><p class="text-[5px] text-slate-500 sm:text-[7px]">{{ $label }}</p></div>
                    @endforeach
                </div>
                <div class="mt-[3%] rounded-xl bg-white p-[3%] shadow-sm dark:bg-white/7">
                    <div class="flex items-center justify-between"><p class="text-[7px] font-black text-[#101a46] dark:text-white sm:text-[10px]">{{ $type === 'accounting' ? 'Journal des opérations' : 'Factures récentes' }}</p><span class="rounded-md bg-blue-600 px-2 py-1 text-[5px] font-bold text-white sm:text-[7px]">+ Nouveau</span></div>
                    <div class="mt-[3%] space-y-[2%]">
                        @foreach ([['YBX-2026-0841', 'Maison Kivu', '$1 250', 'Payée'], ['YBX-2026-0840', 'Congo Market', '$820', 'En attente'], ['YBX-2026-0839', 'Bistro Fleuve', '$2 140', 'Payée']] as $invoice)
                            <div wire:key="mock-invoice-{{ $loop->index }}" class="grid grid-cols-[1fr_1fr_.6fr_.6fr] border-t border-slate-100 pt-[2%] text-[5px] text-slate-500 dark:border-white/5 sm:text-[8px]"><b class="text-slate-800 dark:text-white">{{ $invoice[0] }}</b><span>{{ $invoice[1] }}</span><b>{{ $invoice[2] }}</b><span @class(['font-bold', 'text-emerald-600' => $loop->even, 'text-amber-600' => $loop->odd])>{{ $invoice[3] }}</span></div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="mt-[3%] grid grid-cols-4 gap-[2%]">
                    @foreach ([['$8.4K', 'Ventes'], ['1 284', 'Transactions'], ['36%', 'Marge'], ['98.7%', 'Disponibilité']] as [$value, $label])
                        <div wire:key="mock-dashboard-stat-{{ $loop->index }}" class="rounded-lg bg-white p-[7%] shadow-sm dark:bg-white/7"><p class="text-[8px] font-black text-[#101a46] dark:text-white sm:text-sm">{{ $value }}</p><p class="truncate text-[4px] text-slate-500 sm:text-[7px]">{{ $label }}</p></div>
                    @endforeach
                </div>
                <div class="mt-[3%] grid grid-cols-[1.35fr_.65fr] gap-[2.5%]">
                    <div class="rounded-xl bg-white p-[4%] shadow-sm dark:bg-white/7">
                        <div class="flex justify-between"><p class="text-[7px] font-black text-[#101a46] dark:text-white sm:text-[10px]">Performance des ventes</p><span class="text-[5px] text-slate-400 sm:text-[7px]">7 derniers jours</span></div>
                        <svg viewBox="0 0 340 130" class="mt-[3%] w-full" fill="none" aria-hidden="true"><path d="M4 107C42 101 52 55 86 70s46 32 79-4 48-46 78-17 51-5 93-42" stroke="#3154f4" stroke-width="5" stroke-linecap="round"/><path d="M4 107C42 101 52 55 86 70s46 32 79-4 48-46 78-17 51-5 93-42V130H4Z" fill="url(#report-area)"/><defs><linearGradient id="report-area" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#3154f4" stop-opacity=".3"/><stop offset="1" stop-color="#3154f4" stop-opacity="0"/></linearGradient></defs></svg>
                    </div>
                    <div class="rounded-xl bg-[#101a46] p-[7%] text-white shadow-sm"><p class="text-[6px] font-black sm:text-[9px]">État du système</p><div class="mt-[10%] grid aspect-square place-items-center rounded-full border-[5px] border-emerald-400 border-r-white/15 text-center"><span><b class="block text-[13px] sm:text-xl">99%</b><span class="text-[4px] text-blue-100 sm:text-[6px]">Opérationnel</span></span></div><p class="mt-[10%] text-center text-[5px] text-blue-100 sm:text-[7px]">Toutes les données sont protégées et synchronisées.</p></div>
                </div>
            @endif
        </div>
    </div>
</div>
