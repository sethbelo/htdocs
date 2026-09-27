<x-app-layout>
    {{-- Header slot --}}
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 py-2">
            <div>
                <span class="text-xs font-mono uppercase tracking-wider text-primary font-semibold">Système POS Yeboox</span>
                <h2 class="font-bold text-3xl text-base-content tracking-tight mt-1" style="font-family: 'Space Grotesk', sans-serif;">
                    Tableau de Bord
                </h2>
            </div>
            <div class="flex items-center gap-3">
                <span class="badge badge-success badge-outline gap-1.5 px-3 py-3 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                    Terminal Connecté
                </span>
                <div class="badge badge-neutral gap-1.5 px-3 py-3 font-mono text-xs">
                    Caisse: #01A
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-base-200/50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- 1. DaisyUI Stats Grid --}}
            <div class="stats stats-vertical lg:stats-horizontal shadow w-full bg-base-100 border border-base-200">
                
                <!-- Stat 1: Revenue -->
                <div class="stat">
                    <div class="stat-figure text-primary">
                        <i data-lucide="dollar-sign" class="w-6 h-6"></i>
                    </div>
                    <div class="stat-title font-mono text-xs uppercase tracking-wider">Recettes du Jour</div>
                    <div class="stat-value text-primary font-mono text-2xl lg:text-3xl mt-1">1 420.50 €</div>
                    <div class="stat-desc text-success font-semibold flex items-center gap-1 mt-1">
                        <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                        <span>+12.4% vs hier</span>
                    </div>
                </div>

                <!-- Stat 2: Orders -->
                <div class="stat border-t lg:border-t-0 lg:border-l border-base-200">
                    <div class="stat-figure text-secondary">
                        <i data-lucide="utensils" class="w-6 h-6"></i>
                    </div>
                    <div class="stat-title font-mono text-xs uppercase tracking-wider">Commandes</div>
                    <div class="stat-value text-secondary font-mono text-2xl lg:text-3xl mt-1">42 Tables</div>
                    <div class="stat-desc flex items-center gap-1.5 mt-1">
                        <span class="badge badge-xs badge-secondary animate-ping"></span>
                        <span>8 services actifs</span>
                    </div>
                </div>

                <!-- Stat 3: Basket -->
                <div class="stat border-t lg:border-t-0 lg:border-l border-base-200">
                    <div class="stat-figure text-accent">
                        <i data-lucide="shopping-bag" class="w-6 h-6"></i>
                    </div>
                    <div class="stat-title font-mono text-xs uppercase tracking-wider">Panier Moyen</div>
                    <div class="stat-value text-accent font-mono text-2xl lg:text-3xl mt-1">33.80 €</div>
                    <div class="stat-desc text-success font-semibold flex items-center gap-1 mt-1">
                        <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                        <span>+3.1% ce midi</span>
                    </div>
                </div>

                <!-- Stat 4: Cancellation -->
                <div class="stat border-t lg:border-t-0 lg:border-l border-base-200">
                    <div class="stat-figure text-error">
                        <i data-lucide="x-circle" class="w-6 h-6"></i>
                    </div>
                    <div class="stat-title font-mono text-xs uppercase tracking-wider">Annulations</div>
                    <div class="stat-value text-error font-mono text-2xl lg:text-3xl mt-1">0.8 %</div>
                    <div class="stat-desc text-success font-semibold flex items-center gap-1 mt-1">
                        <span>-1.2% ce jour</span>
                    </div>
                </div>

            </div>

            {{-- 2. Graphical Grid Section --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Chart Card (DaisyUI Card Style) -->
                <div class="card bg-base-100 border border-base-200 shadow lg:col-span-2">
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="card-title font-bold text-lg text-base-content" style="font-family: 'Space Grotesk', sans-serif;">Activité des Ventes</h3>
                                <p class="text-xs text-base-content/60">Évolution du chiffre d'affaires cumulé sur la journée</p>
                            </div>
                            <span class="badge badge-primary badge-outline font-mono text-xs">LIVE</span>
                        </div>

                        <!-- Vercel-like SVG Line chart -->
                        <div class="relative w-full h-64 bg-base-200/30 rounded-lg overflow-hidden border border-base-200/50 mt-4">
                            <svg class="w-full h-full p-4" viewBox="0 0 500 200" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="var(--fallback-p, #4f46e5)" stop-opacity="0.25"/>
                                        <stop offset="100%" stop-color="var(--fallback-p, #4f46e5)" stop-opacity="0.00"/>
                                    </linearGradient>
                                </defs>
                                <line x1="0" y1="50" x2="500" y2="50" stroke="rgba(0,0,0,0.03)" stroke-width="1"/>
                                <line x1="0" y1="100" x2="500" y2="100" stroke="rgba(0,0,0,0.03)" stroke-width="1"/>
                                <line x1="0" y1="150" x2="500" y2="150" stroke="rgba(0,0,0,0.03)" stroke-width="1"/>
                                
                                <path d="M 0 200 L 0 160 Q 100 120 180 140 T 360 60 T 500 40 L 500 200 Z" fill="url(#chartGrad)"/>
                                <path d="M 0 160 Q 100 120 180 140 T 360 60 T 500 40" fill="none" stroke="var(--fallback-p, #4f46e5)" stroke-width="2.5"/>
                                
                                <circle cx="180" cy="140" r="4" fill="var(--fallback-p, #4f46e5)" stroke="white" stroke-width="1.5"/>
                                <circle cx="360" cy="60" r="4" fill="var(--fallback-a, #06b6d4)" stroke="white" stroke-width="1.5"/>
                            </svg>
                            
                            <div class="absolute bottom-2 inset-x-0 px-4 flex justify-between text-[9px] font-mono text-base-content/50">
                                <span>08:00</span>
                                <span>12:00</span>
                                <span>15:00</span>
                                <span>19:00</span>
                                <span>23:00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Popularity (DaisyUI Card Style) -->
                <div class="card bg-base-100 border border-base-200 shadow">
                    <div class="card-body justify-between">
                        <div>
                            <h3 class="card-title font-bold text-lg text-base-content" style="font-family: 'Space Grotesk', sans-serif;">Top Ventes</h3>
                            <p class="text-xs text-base-content/60">Les plats les plus populaires</p>
                        </div>

                        <div class="space-y-5 my-4">
                            <!-- Item 1 -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-semibold">
                                    <span>Poulet Yassa</span>
                                    <span class="font-mono text-base-content/60">85%</span>
                                </div>
                                <progress class="progress progress-primary w-full" value="85" max="100"></progress>
                            </div>

                            <!-- Item 2 -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-semibold">
                                    <span>Allocos Plantains</span>
                                    <span class="font-mono text-base-content/60">65%</span>
                                </div>
                                <progress class="progress progress-secondary w-full" value="65" max="100"></progress>
                            </div>

                            <!-- Item 3 -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-semibold">
                                    <span>Jus de Bissap</span>
                                    <span class="font-mono text-base-content/60">50%</span>
                                </div>
                                <progress class="progress progress-accent w-full" value="50" max="100"></progress>
                            </div>
                        </div>

                        <button class="btn btn-outline btn-sm btn-block">Voir tous les produits</button>
                    </div>
                </div>

            </div>

            {{-- 3. Detailed Data Table (DaisyUI Table Style) --}}
            <div class="card bg-base-100 border border-base-200 shadow">
                <div class="card-body space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h3 class="card-title font-bold text-lg text-base-content" style="font-family: 'Space Grotesk', sans-serif;">Commandes Actives</h3>
                            <p class="text-xs text-base-content/60">Suivi en temps réel des services cuisine et bar</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" placeholder="Rechercher une table..." class="input input-bordered input-sm w-full max-w-xs focus:input-primary" />
                        </div>
                    </div>

                    <!-- DaisyUI Table -->
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                                <tr class="font-mono text-[10.5px] uppercase tracking-wider text-base-content/60">
                                    <th>Table</th>
                                    <th>Zone</th>
                                    <th>Détails Commande</th>
                                    <th>Statut</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <!-- Row 1 -->
                                <tr>
                                    <td class="font-bold text-primary font-mono">T8</td>
                                    <td>Terrasse Nord</td>
                                    <td class="text-base-content/75">1x Poulet Yassa, 2x Allocos, 1x Bissap</td>
                                    <td><span class="badge badge-warning gap-1.5">En cuisine</span></td>
                                    <td class="text-right font-bold font-mono">29,00 €</td>
                                </tr>
                                <!-- Row 2 -->
                                <tr>
                                    <td class="font-bold text-primary font-mono">T12</td>
                                    <td>Salle Principale</td>
                                    <td class="text-base-content/75">2x Cafés Gourmands, 1x Tarte Tatin</td>
                                    <td><span class="badge badge-success gap-1.5">Servi</span></td>
                                    <td class="text-right font-bold font-mono">21,50 €</td>
                                </tr>
                                <!-- Row 3 -->
                                <tr>
                                    <td class="font-bold text-primary font-mono">T5</td>
                                    <td>Zone Bar</td>
                                    <td class="text-base-content/75">1x Cocktail Royal, 1x Coca-Cola</td>
                                    <td><span class="badge badge-neutral gap-1.5">Payé</span></td>
                                    <td class="text-right font-bold font-mono">14,00 €</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
