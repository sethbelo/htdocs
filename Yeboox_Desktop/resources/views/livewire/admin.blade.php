@php
    $configurationGroups = [
        'Général' => [
            'company' => ['label' => 'Entreprise', 'icon' => 'building-office-2', 'description' => 'Identité, devises et documents', 'permission' => 'manage settings'],
            'documents' => ['label' => 'Tickets & PDF', 'icon' => 'document-text', 'description' => 'Modèles d’impression et typographie', 'permission' => 'manage settings'],
            'theme' => ['label' => 'Apparence', 'icon' => 'swatch', 'description' => 'Logo, couleurs et thème', 'permission' => 'manage settings'],
            'display' => ['label' => 'Affichage & écran', 'icon' => 'computer-desktop', 'description' => 'Résolution, échelle et densité du POS', 'permission' => 'manage settings'],
        ],
        'Accès & système' => [
            'env' => ['label' => 'Serveur', 'icon' => 'server-stack', 'description' => 'Variables d’environnement', 'permission' => 'manage settings'],
            'api' => ['label' => 'API & intégrations', 'icon' => 'key', 'description' => 'Clés et services externes', 'permission' => 'manage settings'],
            'roles' => ['label' => 'Rôles & droits', 'icon' => 'shield-check', 'description' => 'Autorisations par profil', 'permission' => 'manage roles'],
            'users' => ['label' => 'Utilisateurs', 'icon' => 'users', 'description' => 'Comptes et équipes', 'permission' => 'manage users'],
            'logs' => ['label' => 'Journal système', 'icon' => 'clock', 'description' => 'Activité et diagnostic', 'permission' => 'manage administration'],
        ],
        'Vente & opérations' => [
            'modules' => ['label' => 'Modules mensuels', 'icon' => 'squares-plus', 'description' => 'Activation et échéance des fonctions métier', 'permission' => 'manage settings'],
            'payment_methods' => ['label' => 'Paiements', 'icon' => 'credit-card', 'description' => 'Moyens d’encaissement', 'permission' => 'manage settings'],
            'restaurants' => ['label' => 'Restaurant', 'icon' => 'cake', 'description' => 'Tables et cuisine', 'permission' => 'manage settings'],
        ],
    ];
    $configurationTabs = collect($configurationGroups)->collapse();
    $activeConfiguration = $configurationTabs->get($activeTab, $configurationTabs->first());
@endphp

<div class="space-y-6">
    {{-- En-tête partagé : configuration générale et état de disponibilité du système. --}}
    <x-page-hero
        title="Configuration"
        eyebrow="Paramètres du système"
        description="Personnalisez l’application, sécurisez les accès et adaptez les opérations à votre activité."
        icon="cog-6-tooth"
        badge-icon="wrench-screwdriver"
        illustration="settings"
    >
        <x-slot:actions>
            <flux:badge color="emerald" icon="check-badge" class="w-fit bg-emerald-400/15! text-emerald-100! ring-emerald-300/20!">Système opérationnel</flux:badge>
        </x-slot:actions>
    </x-page-hero>


    @if (session()->has('success'))
        <flux:callout variant="success" icon="check-circle" heading="Modification enregistrée" text="{{ session('success') }}" />
    @endif
    
    @if (session()->has('error'))
        <flux:callout variant="danger" icon="exclamation-circle" heading="Une action est requise" text="{{ session('error') }}" />
    @endif

    <div class="xl:hidden">
        <flux:select wire:model.live="activeTab" label="Section de configuration">
            @foreach($configurationGroups as $group => $tabs)
                <optgroup label="{{ $group }}">
                    @foreach($tabs as $tab => $meta)
                        @continue(!auth()->user()->can($meta['permission']))
                        <option value="{{ $tab }}">{{ $meta['label'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </flux:select>
    </div>

    <div class="grid gap-6 xl:grid-cols-[17rem_minmax(0,1fr)] xl:items-start">
        <aside class="hidden xl:block xl:sticky xl:top-24">
            <flux:card class="p-3!">
                <flux:navlist variant="outline">
                    @foreach($configurationGroups as $group => $tabs)
                        <flux:navlist.group :heading="$group" class="mb-3 last:mb-0">
                            @foreach($tabs as $tab => $meta)
                                @continue(!auth()->user()->can($meta['permission']))
                                <flux:navlist.item wire:click="setTab('{{ $tab }}')" :icon="$meta['icon']" :current="$activeTab === $tab" class="cursor-pointer">
                                    {{ $meta['label'] }}
                                </flux:navlist.item>
                            @endforeach
                        </flux:navlist.group>
                    @endforeach
                </flux:navlist>
            </flux:card>
        </aside>

        <section class="min-w-0 space-y-6" aria-live="polite">
            <flux:card class="flex flex-col gap-4 p-5! sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="grid size-11 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300">
                        <flux:icon :name="$activeConfiguration['icon']" class="size-5" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ $activeConfiguration['label'] }}</flux:heading>
                        <flux:text class="mt-0.5">{{ $activeConfiguration['description'] }}</flux:text>
                    </div>
                </div>
                <flux:badge icon="lock-closed" color="violet">Réservé aux administrateurs</flux:badge>
            </flux:card>

    @if($activeTab === 'modules')
        <form wire:submit="saveMonthlyModules" class="space-y-6">
            <flux:callout icon="calendar-days" heading="Activation renouvelable chaque mois">Un module expiré disparaît automatiquement de la navigation et refuse tout accès direct. Renouvelez sa date pour le rendre disponible.</flux:callout>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach(\App\Support\ModuleRegistry::DEFINITIONS as $key => $module)
                    @php
                        $enabled = (bool) ($monthlyModules[$key]['enabled'] ?? false);
                    @endphp
                    <flux:card class="p-5! {{ $key === 'loyalty' ? 'md:col-span-2' : '' }} {{ $enabled ? 'ring-1 ring-violet-300 dark:ring-violet-700' : 'opacity-75' }}">
                        <div class="flex items-start justify-between gap-4"><span class="grid size-11 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300"><flux:icon :name="$module['icon']" class="size-5" /></span><flux:switch wire:model.live="monthlyModules.{{ $key }}.enabled" aria-label="Activer {{ $module['label'] }}" /></div>
                        <flux:heading size="lg" class="mt-4">{{ $module['label'] }}</flux:heading><flux:text size="sm" class="mt-1">{{ $module['description'] }}</flux:text>
                        @if($key === 'loyalty')
                            <div class="mt-5 rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/60">
                                <div class="grid gap-4 lg:grid-cols-3">
                                    <flux:select wire:model.live="loyaltyMode" label="Mode de fidélité" icon="gift">
                                        <flux:select.option value="points">Carte par points</flux:select.option>
                                        <flux:select.option value="services">Carte par prestations</flux:select.option>
                                    </flux:select>
                                    @if($loyaltyMode === 'points')
                                        <flux:input type="text" inputmode="decimal" wire:model="loyaltyPointValueUsd" label="Valeur de 1 point" suffix="{{ \App\Support\DisplayCurrency::baseCode() }}" icon="currency-dollar" placeholder="0,02" description="Valeur exprimée dans la monnaie comptable de base." />
                                        <flux:callout color="amber" icon="star" heading="Valeur comptable">1 point = {{ number_format((float) $loyaltyPointValueUsd, 4, ',', ' ') }} {{ \App\Support\DisplayCurrency::baseCode() }}</flux:callout>
                                    @else
                                        <flux:input type="text" inputmode="numeric" wire:model="loyaltyRequiredServices" label="Prestations à acheter" icon="check-badge" placeholder="9" description="Exemple : 9 achats débloquent 1 carte cadeau offerte." />
                                        <flux:select wire:model="loyaltyRewardProductId" label="Cadeau débloqué — 1 offert" icon="gift" placeholder="Sélectionner un produit ou une prestation">
                                            @foreach($loyaltyRewards as $reward)
                                                <flux:select.option value="{{ $reward->id }}">
                                                    {{ $reward->type === 'service' ? 'Prestation' : 'Produit' }} · {{ $reward->name }} · {{ \App\Support\DisplayCurrency::format($reward->price) }}{{ $reward->type === 'product' ? ' · stock '.number_format((float) $reward->stock_qty, 0, ',', ' ') : '' }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        @if($loyaltyRewards->isEmpty())
                                            <flux:callout variant="warning" icon="exclamation-triangle" heading="Aucun cadeau disponible">Créez ou activez au moins un produit ou une prestation dans l’inventaire.</flux:callout>
                                        @endif
                                    @endif
                                </div>
                                <flux:error name="loyaltyMode" /><flux:error name="loyaltyPointValueUsd" /><flux:error name="loyaltyRequiredServices" /><flux:error name="loyaltyRewardProductId" />
                                <div class="mt-4 flex justify-end">
                                    <flux:button type="button" wire:click="saveMonthlyModules" wire:loading.attr="disabled" wire:target="saveMonthlyModules" variant="primary" color="violet" icon="check">
                                        <span wire:loading.remove wire:target="saveMonthlyModules">Enregistrer la carte de fidélité</span>
                                        <span wire:loading wire:target="saveMonthlyModules">Enregistrement…</span>
                                    </flux:button>
                                </div>
                            </div>
                        @endif
                        <div class="mt-4"><flux:input type="date" wire:model="monthlyModules.{{ $key }}.expires_at" label="Disponible jusqu’au" icon="calendar-days" /></div>
                    </flux:card>
                @endforeach
            </div>
            <div class="sticky bottom-4 flex justify-end"><flux:button type="submit" variant="primary" color="violet" icon="check" class="shadow-lg">Appliquer les modules</flux:button></div>
        </form>
    <!-- TAB: COMPANY -->
    @elseif($activeTab === 'company')
    <div class="max-w-5xl">
        <!-- FORM -->
        <flux:card class="p-6!">
            <div class="mb-6 flex items-center gap-3">
                <div class="grid size-10 place-items-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300">
                    <flux:icon.building-office-2 class="size-5" />
                </div>
                <div>
                    <flux:heading size="lg">Informations de la société</flux:heading>
                    <flux:text class="mt-0.5">Coordonnées affichées sur vos tickets et factures.</flux:text>
                </div>
            </div>
            <form wire:submit.prevent="saveCompany" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom de la société</label>
                    <flux:input type="text" wire:model.live.debounce.300ms="company_name" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500" />
                    @error('company_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email de contact</label>
                    <flux:input type="email" wire:model.live.debounce.300ms="company_email" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500" />
                    @error('company_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Téléphone</label>
                    <flux:input type="text" wire:model.live.debounce.300ms="company_phone" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500" />
                    @error('company_phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Adresse complète</label>
                    <flux:textarea wire:model.live.debounce.300ms="company_address" rows="3" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500"></flux:textarea>
                    @error('company_address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Taux de change (1 {{ $company_currency }} = ? {{ $secondary_currency }})</label>
                    <flux:input type="text" inputmode="decimal" wire:model="exchange_rate" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500" />
                    <p class="text-xs text-slate-500 mt-1">Ce taux sert uniquement à l’équivalent imprimé sur les factures et tickets.</p>
                    @error('exchange_rate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Devise de base</label>
                        <div class="flex h-10 items-center gap-2 rounded-lg bg-violet-50 px-3 text-sm font-semibold text-violet-800 ring-1 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-200 dark:ring-violet-900"><flux:icon.lock-closed class="size-4" />{{ $company_currency }} · {{ \App\Support\DisplayCurrency::label($company_currency) }}</div>
                        <p class="mt-1 text-xs text-slate-500">Fixée lors de la création de l’administrateur pour préserver la comptabilité.</p>
                    </div>
                    <div>
                        <flux:select wire:model.live="secondary_currency" label="Devise secondaire" description="Réservée aux documents clients">
                            @foreach(\App\Support\DisplayCurrency::currencies() as $code => $meta)
                                @continue($code === $company_currency)
                                <flux:select.option value="{{ $code }}">{{ $meta['label'] }} ({{ $code }})</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                <flux:callout color="violet" icon="information-circle" heading="Comptabilité mono-devise">Les produits, factures comptables, dépenses, journaux et rapports sont enregistrés et imprimés uniquement en {{ $company_currency }}. L’équivalent {{ $secondary_currency }} n’apparaît que sur les documents remis au client.</flux:callout>
                <div class="flex flex-col gap-4 rounded-2xl bg-violet-50 p-4 dark:bg-violet-500/10 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-violet-700 shadow-sm dark:bg-zinc-900 dark:text-violet-300"><flux:icon.receipt-percent class="size-5" /></span>
                        <div>
                            <flux:heading size="sm">Taxe incluse dans le prix par défaut</flux:heading>
                            <flux:text size="sm" class="mt-1">Activé : le prix saisi est TTC et la TVA est extraite. Désactivé : le prix saisi est HT et la TVA est ajoutée à la vente.</flux:text>
                        </div>
                    </div>
                    <flux:switch wire:model.live="default_tax_included" label="{{ $default_tax_included ? 'Prix TTC' : 'Prix HT' }}" align="left" />
                </div>
                <flux:error name="default_tax_included" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">RCCM</label>
                        <flux:input type="text" wire:model.live.debounce.300ms="company_rccm" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500" />
                        @error('company_rccm') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">N° Impôt (NIF)</label>
                        <flux:input type="text" wire:model.live.debounce.300ms="company_nif" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500" />
                        @error('company_nif') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">N° TVA</label>
                        <flux:input type="text" wire:model.live.debounce.300ms="company_tva" placeholder="Ex. TVA-243-001" />
                        @error('company_tva') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">En-tête de document (Ticket, Facture)</label>
                    <flux:textarea wire:model.live.debounce.300ms="company_header" rows="2" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500"></flux:textarea>
                    @error('company_header') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Pied de page (Ticket, Facture)</label>
                    <flux:textarea wire:model.live.debounce.300ms="company_footer" rows="2" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500"></flux:textarea>
                    @error('company_footer') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="pt-4">
                    <flux:button type="submit" variant="primary" icon="check" class="w-full md:w-auto">Enregistrer les informations</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
    @endif

    <!-- TAB: THEME -->
    @if($activeTab === 'theme')
        <div class="grid gap-6 2xl:grid-cols-[minmax(0,0.85fr)_minmax(30rem,1.15fr)]">
            <flux:card class="p-6!">
                <div class="mb-6 flex items-center gap-3">
                    <div class="grid size-10 place-items-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300">
                        <flux:icon.swatch class="size-5" />
                    </div>
                    <div>
                        <flux:heading size="lg">Identité visuelle</flux:heading>
                        <flux:text class="mt-0.5">Logo, couleur et mode d’affichage de l’application.</flux:text>
                    </div>
                </div>

                <form wire:submit="saveTheme" class="space-y-6">
                    <div class="rounded-2xl border border-dashed border-zinc-300 p-5 dark:border-zinc-700">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                            <div class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-2xl bg-zinc-100 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                                @if($logo)
                                    <img src="{{ $logo->temporaryUrl() }}" alt="Nouveau logo" class="size-full object-contain p-2">
                                @elseif($currentLogoUrl)
                                    <img src="{{ $currentLogoUrl }}" alt="Logo actuel" class="size-full object-contain p-2">
                                @else
                                    <flux:icon.photo class="size-9 text-zinc-400" />
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <flux:heading>Logo de l’entreprise</flux:heading>
                                <flux:text class="mt-1">PNG ou JPG, maximum 2 Mo. Format carré recommandé.</flux:text>
                                <flux:input type="file" wire:model.live="logo" accept="image/png,image/jpeg" class="mt-3" />
                                @error('logo')<flux:text class="mt-1 text-red-600!">{{ $message }}</flux:text>@enderror
                            </div>
                        </div>
                        <div wire:loading.flex wire:target="logo" class="mt-3 items-center gap-2 text-sm text-violet-600">
                            <flux:icon.arrow-path class="size-4 animate-spin" /> Chargement du logo…
                        </div>
                    </div>

                    <div class="rounded-2xl border border-dashed border-zinc-300 p-5 dark:border-zinc-700">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                            <div class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-2xl bg-zinc-100 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                                @if($establishmentLogo)
                                    <img src="{{ $establishmentLogo->temporaryUrl() }}" alt="Nouveau logo de l’établissement" class="size-full object-contain p-2">
                                @elseif($currentEstablishmentLogoUrl)
                                    <img src="{{ $currentEstablishmentLogoUrl }}" alt="Logo de l’établissement" class="size-full object-contain p-2">
                                @elseif($currentLogoUrl)
                                    <img src="{{ $currentLogoUrl }}" alt="Logo hérité de l’entreprise" class="size-full object-contain p-2 opacity-70">
                                @else
                                    <flux:icon.building-storefront class="size-9 text-zinc-400" />
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <flux:heading>Logo de l’établissement</flux:heading>
                                <flux:text class="mt-1">Utilisé dans ses rapports PDF. Sans fichier, le logo de l’entreprise est repris.</flux:text>
                                <flux:input type="file" wire:model.live="establishmentLogo" accept="image/png,image/jpeg" class="mt-3" />
                                @error('establishmentLogo')<flux:text class="mt-1 text-red-600!">{{ $message }}</flux:text>@enderror
                            </div>
                        </div>
                        <div wire:loading.flex wire:target="establishmentLogo" class="mt-3 items-center gap-2 text-sm text-violet-600">
                            <flux:icon.arrow-path class="size-4 animate-spin" /> Chargement du logo de l’établissement…
                        </div>
                    </div>

                    <div>
                        <flux:label>Couleur principale</flux:label>
                        <div class="mt-2 grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                            <flux:input type="color" wire:model.live.debounce.200ms="primary_color" class="h-10! p-1!" aria-label="Sélecteur de couleur" />
                            <flux:input wire:model.live.debounce.200ms="primary_color" icon="paint-brush" placeholder="#7E22CE" />
                        </div>
                        @error('primary_color')<flux:text class="mt-1 text-red-600!">{{ $message }}</flux:text>@enderror
                    </div>

                    <flux:select label="Mode de l’application" wire:model.live="app_theme" icon="computer-desktop">
                        <flux:select.option value="system">Automatique — suivre le système</flux:select.option>
                        <flux:select.option value="light">Toujours clair</flux:select.option>
                        <flux:select.option value="dark">Toujours sombre</flux:select.option>
                    </flux:select>

                    <flux:callout icon="information-circle" heading="Documents déplacés">
                        Les modèles de tickets, factures et rapports sont maintenant configurés dans l’onglet « Tickets & PDF ».
                    </flux:callout>

                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary" icon="check">Appliquer l’apparence</flux:button>
                    </div>
                </form>
            </flux:card>

            <flux:card class="overflow-hidden p-0!">
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                    <div>
                        <flux:heading>Aperçu de l’application</flux:heading>
                        <flux:text class="mt-0.5">Sidebar, topbar et composants principaux.</flux:text>
                    </div>
                    <flux:badge icon="eye">Temps réel</flux:badge>
                </div>
                <div class="{{ $app_theme === 'light' ? 'bg-zinc-100 text-zinc-950' : ($app_theme === 'dark' ? 'bg-zinc-950 text-white' : 'bg-zinc-900 text-white') }} p-5">
                    <div class="mx-auto grid min-h-[34rem] max-w-4xl grid-cols-[8.5rem_minmax(0,1fr)] overflow-hidden rounded-2xl border border-white/10 bg-white shadow-2xl dark:bg-zinc-950">
                        <aside class="flex flex-col bg-zinc-950 p-3 text-white">
                            <div class="mb-5 flex items-center gap-2 px-2">
                                <div class="grid size-8 place-items-center overflow-hidden rounded-lg" style="background-color: {{ $primary_color }}">
                                    @if($logo)<img src="{{ $logo->temporaryUrl() }}" class="size-full object-contain">@elseif($currentLogoUrl)<img src="{{ $currentLogoUrl }}" class="size-full object-contain">@else<flux:icon.building-storefront class="size-4" />@endif
                                </div>
                                <span class="truncate text-xs font-bold">{{ $company_name ?: 'Yeboox' }}</span>
                            </div>
                            <div class="space-y-1 text-[10px]">
                                @foreach([['home','Tableau de bord'],['document-text','Facturation'],['users','Clients'],['cube','Inventaire']] as [$icon,$label])
                                    <div class="flex items-center gap-2 rounded-lg px-2 py-2 {{ $loop->first ? 'text-white' : 'text-zinc-400' }}" @if($loop->first) style="background-color: {{ $primary_color }}" @endif>
                                        <flux:icon :name="$icon" class="size-3.5" /><span>{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-auto flex items-center gap-2 border-t border-white/10 px-2 pt-3 text-[10px]"><flux:avatar size="xs" initials="SA" /><span>Super Admin</span></div>
                        </aside>
                        <main class="{{ $app_theme === 'dark' ? 'bg-zinc-950' : 'bg-zinc-50' }}">
                            <div class="flex h-12 items-center justify-between border-b border-zinc-200 px-4 dark:border-zinc-800">
                                <strong class="text-xs">Tableau de bord</strong>
                                <flux:badge size="sm" icon="check-circle" color="green">En ligne</flux:badge>
                            </div>
                            <div class="space-y-4 p-4">
                                <div><div class="text-lg font-black">Bonjour, Super Admin</div><div class="text-[10px] text-zinc-500">Voici l’activité du jour.</div></div>
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach([['banknotes','Ventes','1 240 $'],['users','Clients','128'],['cube','Stock','540']] as [$icon,$label,$value])
                                        <div class="rounded-xl border border-zinc-200 bg-white p-3 text-zinc-950 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white">
                                            <flux:icon :name="$icon" class="size-4" style="color: {{ $primary_color }}" />
                                            <div class="mt-3 text-[9px] text-zinc-500">{{ $label }}</div><div class="text-sm font-black">{{ $value }}</div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="rounded-xl border border-zinc-200 bg-white p-4 text-zinc-950 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white">
                                    <div class="mb-4 flex items-center justify-between"><strong class="text-xs">Activité</strong><span class="text-[9px] text-zinc-400">7 derniers jours</span></div>
                                    <div class="flex h-28 items-end gap-2">
                                        @foreach([35,58,42,76,62,88,70] as $height)<div class="flex-1 rounded-t" style="height:{{ $height }}%;background-color:{{ $primary_color }};opacity:{{ .45 + ($loop->iteration * .06) }}"></div>@endforeach
                                    </div>
                                </div>
                                <flux:button variant="primary" size="sm" icon="plus" style="background-color: {{ $primary_color }}">Nouvelle vente</flux:button>
                            </div>
                        </main>
                    </div>
                </div>
            </flux:card>
        </div>
    @endif

    <!-- TAB: DISPLAY -->
    @if($activeTab === 'display')
        @php
            $displayProfiles = [
                'auto' => ['Automatique', 'Écran actuel', 'Adaptation sans redimensionnement forcé'],
                '800x600' => ['800 × 600', '4:3 · Minimal', 'Petites caisses et anciens écrans'],
                '1024x768' => ['1024 × 768', '4:3 · Compact', 'Terminaux tactiles et petits écrans'],
                '1280x720' => ['1280 × 720', 'HD · 16:9', 'Terminaux POS compacts'],
                '1366x768' => ['1366 × 768', 'HD · 16:9', 'Ordinateurs portables courants'],
                '1440x900' => ['1440 × 900', '16:10', 'Confort vertical renforcé'],
                '1600x900' => ['1600 × 900', 'HD+', 'Postes de vente standards'],
                '1920x1080' => ['1920 × 1080', 'Full HD', 'Grand écran de caisse'],
                '2560x1440' => ['2560 × 1440', 'QHD', 'Écran haute définition'],
                'custom' => ['Personnalisée', '760–3840 px', 'Dimensions adaptées au matériel'],
            ];
        @endphp
        <form wire:submit="saveDisplaySettings" class="grid gap-6 2xl:grid-cols-[minmax(0,1.2fr)_minmax(22rem,.8fr)]">
            <flux:card class="p-6!">
                <div class="mb-6 flex items-start gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300">
                        <flux:icon.computer-desktop class="size-5" />
                    </span>
                    <div>
                        <flux:heading size="lg">Résolution de travail</flux:heading>
                        <flux:text class="mt-1">Choisissez le profil du poste. Sous Windows, la fenêtre Electron est redimensionnée sans jamais dépasser l’écran disponible.</flux:text>
                    </div>
                </div>

                <fieldset>
                    <legend class="sr-only">Profil de résolution</legend>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($displayProfiles as $value => [$title, $ratio, $description])
                            <button type="button" wire:click="$set('displayResolution', '{{ $value }}')" aria-pressed="{{ $displayResolution === $value ? 'true' : 'false' }}" class="group min-w-0 rounded-2xl border p-4 text-left transition {{ $displayResolution === $value ? 'border-violet-500 bg-violet-50 ring-2 ring-violet-500/15 dark:bg-violet-500/10' : 'border-zinc-200 hover:border-violet-300 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-violet-700 dark:hover:bg-zinc-800/60' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-xl {{ $displayResolution === $value ? 'bg-violet-600 text-white' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300' }}"><flux:icon.rectangle-group class="size-4" /></span>
                                    @if($displayResolution === $value)<flux:icon.check-circle class="size-5 shrink-0 text-violet-600 dark:text-violet-300" />@endif
                                </div>
                                <div class="mt-4 font-semibold text-zinc-900 dark:text-white">{{ $title }}</div>
                                <div class="mt-0.5 text-xs font-medium text-violet-600 dark:text-violet-300">{{ $ratio }}</div>
                                <p class="mt-2 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                            </button>
                        @endforeach
                    </div>
                </fieldset>

                @if($displayResolution === 'custom')
                    <div class="mt-5 grid gap-4 rounded-2xl border border-dashed border-zinc-300 p-4 dark:border-zinc-700 sm:grid-cols-2">
                        <flux:input type="number" min="760" max="3840" step="1" wire:model="displayWidth" label="Largeur personnalisée" suffix="px" />
                        <flux:input type="number" min="540" max="2160" step="1" wire:model="displayHeight" label="Hauteur personnalisée" suffix="px" />
                    </div>
                @endif

                <div class="mt-6 grid gap-5 lg:grid-cols-2">
                    <flux:select wire:model="displayScale" label="Échelle de l’interface" icon="magnifying-glass-plus">
                        <flux:select.option value="80">80 % · Très compacte</flux:select.option>
                        <flux:select.option value="90">90 % · Compacte</flux:select.option>
                        <flux:select.option value="100">100 % · Recommandée</flux:select.option>
                        <flux:select.option value="110">110 % · Confort visuel</flux:select.option>
                        <flux:select.option value="125">125 % · Texte agrandi</flux:select.option>
                    </flux:select>
                    <flux:select wire:model="displayDensity" label="Densité des contenus" icon="bars-3">
                        <flux:select.option value="comfortable">Confortable · plus aérée</flux:select.option>
                        <flux:select.option value="compact">Compacte · plus d’informations</flux:select.option>
                    </flux:select>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-zinc-200 pt-5 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                    <flux:text size="sm" class="flex items-center gap-2"><flux:icon.shield-check class="size-4 text-emerald-600" /> Le système empêche tout débordement hors écran.</flux:text>
                    <flux:button type="submit" variant="primary" icon="arrows-pointing-out">Appliquer l’affichage</flux:button>
                </div>
            </flux:card>

            <div class="space-y-6">
                <flux:card class="overflow-hidden p-0!">
                    <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                        <div class="flex items-center justify-between gap-3"><flux:heading>Aperçu du POS</flux:heading><flux:badge color="sky" icon="eye">Adaptatif</flux:badge></div>
                        <flux:text class="mt-1">La grille et le panier se réorganisent selon l’espace utile.</flux:text>
                    </div>
                    <div class="bg-zinc-100 p-5 dark:bg-zinc-950">
                        <div class="mx-auto overflow-hidden rounded-xl border-4 border-zinc-700 bg-white shadow-xl dark:bg-zinc-900" style="max-width: {{ $displayResolution === '1024x768' ? '21rem' : ($displayResolution === '2560x1440' ? '32rem' : '27rem') }}">
                            <div class="flex h-7 items-center gap-1.5 border-b border-zinc-200 px-2 dark:border-zinc-700"><span class="size-1.5 rounded-full bg-rose-400"></span><span class="size-1.5 rounded-full bg-amber-400"></span><span class="size-1.5 rounded-full bg-emerald-400"></span><span class="ml-2 text-[7px] text-zinc-400">Yeboox POS</span></div>
                            <div class="grid grid-cols-[minmax(0,1fr)_30%]">
                                <div class="p-2"><div class="mb-2 h-4 rounded bg-zinc-100 dark:bg-zinc-800"></div><div class="grid grid-cols-3 gap-1.5">@foreach(range(1, 9) as $item)<div class="aspect-square rounded bg-violet-100 dark:bg-violet-500/15"></div>@endforeach</div></div>
                                <div class="border-l border-zinc-200 p-2 dark:border-zinc-700"><div class="h-3 w-2/3 rounded bg-zinc-200 dark:bg-zinc-700"></div><div class="mt-2 space-y-1.5">@foreach(range(1, 5) as $item)<div class="h-5 rounded bg-zinc-100 dark:bg-zinc-800"></div>@endforeach</div><div class="mt-3 h-6 rounded bg-violet-600"></div></div>
                            </div>
                        </div>
                        <div class="mt-4 text-center text-xs text-zinc-500">{{ $displayProfiles[$displayResolution][0] ?? 'Personnalisée' }} · {{ $displayScale }} % · {{ $displayDensity === 'compact' ? 'Compacte' : 'Confortable' }}</div>
                    </div>
                </flux:card>

                <flux:callout icon="information-circle" heading="Comportement navigateur et Windows">
                    Le navigateur applique l’échelle et la densité. L’application Electron Windows applique aussi les dimensions de fenêtre demandées, limitées à la zone de travail de l’écran.
                </flux:callout>
            </div>
        </form>
    @endif

    <!-- TAB: ENV -->
    @if($activeTab === 'env')
    <div class="bg-white dark:bg-gray-800/40 p-6 rounded-xl border border-gray-200 dark:border-gray-700/50 shadow-sm backdrop-blur-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Gestion du fichier .env</h3>
            <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">Zone Critique</span>
        </div>
        <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">La modification de ce fichier peut casser l'application. Ne modifiez ces valeurs que si vous savez exactement ce que vous faites.</p>
        
        <form wire:submit.prevent="saveEnv" class="space-y-4">
            <flux:textarea wire:model="envContent" rows="15" class="w-full bg-gray-900 text-green-400 font-mono text-sm p-4 rounded-xl border border-gray-700 focus:ring-2 focus:ring-red-500 focus:outline-none" spellcheck="false"></flux:textarea>
            
            <div class="pt-4">
                <flux:button type="submit" wire:confirm="Êtes-vous absolument sûr de vouloir écraser le fichier .env ? Une erreur peut rendre le site inaccessible." class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium transition shadow-lg shadow-red-500/30"><i class="fas fa-exclamation-triangle mr-2"></i> Sauvegarder .env</flux:button>
            </div>
        </form>
    </div>
    @endif

    <!-- TAB: API KEYS -->
    @if($activeTab === 'api')
    <div class="bg-white dark:bg-[#111111] p-6 rounded-2xl border border-gray-200 dark:border-gray-800/60 shadow-sm max-w-4xl mb-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Intégrations & Clés API</h3>
        <p class="text-sm text-slate-600 dark:text-gray-400 mb-6">Configurez les services de messagerie, les clés API externes et les options de sauvegarde cloud.</p>
        
        <form wire:submit.prevent="saveApiKeys" class="space-y-8">
            <!-- Section API Keys -->
            <div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
                    <i class="fas fa-key text-purple-500"></i> Clés API (Paiements & SMS)
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Clé Secrète Stripe</label>
                        <flux:input type="password" wire:model.defer="stripe_key" placeholder="sk_test_..." class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Clé API Twilio</label>
                        <flux:input type="password" wire:model.defer="twilio_key" placeholder="..." class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                    </div>
                </div>
            </div>

            <!-- Section Email -->
            <div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
                    <i class="fas fa-envelope text-purple-500"></i> Serveur Email (SMTP)
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Pilote (Driver)</label>
                        <flux:select wire:model.defer="mail_driver" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500">
                            <option value="smtp">SMTP</option>
                            <option value="mailgun">Mailgun</option>
                            <option value="ses">Amazon SES</option>
                        </flux:select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Hôte (Host)</label>
                        <flux:input type="text" wire:model.defer="mail_host" placeholder="smtp.mailtrap.io" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Port</label>
                        <flux:input type="text" wire:model.defer="mail_port" placeholder="587" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cryptage (Encryption)</label>
                        <flux:select wire:model.defer="mail_encryption" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="">Aucun</option>
                        </flux:select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom d'utilisateur</label>
                        <flux:input type="text" wire:model.defer="mail_username" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Mot de passe</label>
                        <flux:input type="password" wire:model.defer="mail_password" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Adresse d'expédition (From Address)</label>
                        <flux:input type="email" wire:model.defer="mail_from_address" placeholder="contact@monsite.com" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                    </div>
                </div>
            </div>

            <!-- Section Cloud Backup -->
            <div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
                    <i class="fas fa-cloud-upload-alt text-teal-500"></i> Sauvegarde Cloud (Backup)
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Disque de sauvegarde</label>
                        <flux:select wire:model.live="backup_disk" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500">
                            <option value="local">Local (Serveur)</option>
                            <option value="s3">Amazon S3</option>
                            <option value="google">Google Cloud Storage</option>
                        </flux:select>
                    </div>
                    
                    @if($backup_disk === 's3' || $backup_disk === 'google')
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Access Key ID</label>
                        <flux:input type="text" wire:model.defer="aws_access_key_id" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Secret Access Key</label>
                        <flux:input type="password" wire:model.defer="aws_secret_access_key" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Region par défaut</label>
                        <flux:input type="text" wire:model.defer="aws_default_region" placeholder="us-east-1" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom du Bucket</label>
                        <flux:input type="text" wire:model.defer="aws_bucket" class="w-full bg-gray-50 dark:bg-[#141414] border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:ring-1 focus:ring-purple-500" />
                    </div>
                    @endif
                </div>
            </div>

            <div class="pt-6 border-t border-gray-200 dark:border-gray-700/50 flex justify-end">
                <flux:button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-6 py-2.5 rounded-xl transition shadow-lg shadow-purple-500/30 flex items-center gap-2">
                    <i class="fas fa-save"></i> Enregistrer les intégrations
                </flux:button>
            </div>
        </form>
    </div>
    @endif

    <!-- TAB: ROLES & PERMISSIONS -->
    @if($activeTab === 'roles')
        <flux:card class="overflow-hidden p-0!">
            <div class="flex flex-col gap-3 border-b border-zinc-200 p-6 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg">Matrice des rôles et droits</flux:heading>
                    <flux:text class="mt-1">Chaque modification est appliquée immédiatement au rôle sélectionné.</flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:badge icon="users">{{ $roles->sum('users_count') }} utilisateurs</flux:badge>
                    <flux:badge color="violet" icon="shield-check">{{ $permissions->count() }} permissions</flux:badge>
                </div>
            </div>
            <div class="p-3 sm:p-5">
                <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
                <flux:table class="min-w-[48rem] table-fixed">
                    <flux:table.columns>
                        <flux:table.column class="w-[38%] min-w-72 px-4! py-3! text-left"><x-table-heading icon="key">Permission</x-table-heading></flux:table.column>
                        @foreach($roles as $role)
                            <flux:table.column class="w-44 px-3! py-3! text-center">
                                <div class="flex min-h-12 flex-col items-center justify-center normal-case">
                                    <div class="font-semibold">{{ $role->name }}</div>
                                    <div class="mt-0.5 text-xs font-normal text-zinc-500">{{ $role->users_count }} compte(s)</div>
                                </div>
                            </flux:table.column>
                        @endforeach
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($permissions as $permission)
                            <flux:table.row :key="$permission->id">
                                <flux:table.cell variant="strong" class="px-4! py-3! align-middle">
                                    <div class="flex min-h-9 items-center gap-3">
                                        <div class="grid size-8 shrink-0 place-items-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800"><flux:icon.key class="size-4" /></div>
                                        <span class="leading-5">{{ $permission->name }}</span>
                                    </div>
                                </flux:table.cell>
                                @foreach($roles as $role)
                                    @php
                                        $hasPermission = $role->hasPermissionTo($permission->name);
                                    @endphp
                                    <flux:table.cell class="px-3! py-3! text-center align-middle">
                                        <div class="flex min-h-9 items-center justify-center">
                                            @if($role->name === 'Super administrateur')
                                                <flux:badge color="green" icon="check" size="sm">Inclus</flux:badge>
                                            @elseif(!auth()->user()->is_master_admin)
                                                <flux:badge :color="$hasPermission ? 'green' : 'zinc'" :icon="$hasPermission ? 'check' : 'x-mark'" size="sm">
                                                    {{ $hasPermission ? 'Actif' : 'Non' }}
                                                </flux:badge>
                                            @else
                                                <flux:button
                                                    wire:click="togglePermission({{ $role->id }}, '{{ addslashes($permission->name) }}')"
                                                    :variant="$hasPermission ? 'primary' : 'ghost'"
                                                    :icon="$hasPermission ? 'check' : 'x-mark'"
                                                    size="sm"
                                                    :aria-label="($hasPermission ? 'Retirer' : 'Accorder').' '.$permission->name.' au rôle '.$role->name"
                                                />
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                @endforeach
                            </flux:table.row>
                        @empty
                            <flux:table.row><flux:table.cell colspan="{{ $roles->count() + 1 }}"><flux:callout icon="shield-exclamation" heading="Aucune permission" text="Ajoutez des permissions pour construire la matrice d’accès." /></flux:table.cell></flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
                </div>
            </div>
        </flux:card>
    @endif

    <!-- TAB: UTILISATEURS -->
    @if($activeTab === 'users')
        <div class="space-y-5">
            <flux:card class="p-5!">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <flux:input class="w-full sm:max-w-sm" label="Rechercher un utilisateur" wire:model.live.debounce.300ms="searchUsers" icon="magnifying-glass" placeholder="Nom ou adresse e-mail…" clearable />
                    <flux:button wire:click="openUserModal" variant="primary" icon="user-plus">Nouvel utilisateur</flux:button>
                </div>
            </flux:card>

            <flux:card class="overflow-hidden p-0!">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column><x-table-heading icon="user">Utilisateur</x-table-heading></flux:table.column>
                        <flux:table.column><x-table-heading icon="shield-check">Rôle</x-table-heading></flux:table.column>
                        <flux:table.column><x-table-heading icon="signal">Statut</x-table-heading></flux:table.column>
                        <flux:table.column class="text-right"><x-table-heading icon="ellipsis-horizontal">Actions</x-table-heading></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($users as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        <flux:avatar size="sm" :name="$user->name" :initials="str($user->name)->substr(0, 2)->upper()" color="violet" />
                                        <div class="min-w-0"><div class="truncate font-semibold text-zinc-900 dark:text-white">{{ $user->name }}</div><div class="truncate text-sm text-zinc-500">{{ $user->email }}</div></div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($user->roles as $role)<flux:badge color="violet" size="sm" icon="shield-check">{{ $role->name }}</flux:badge>@empty<flux:badge size="sm">Sans rôle</flux:badge>@endforelse
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell><flux:badge color="green" size="sm" icon="check-circle">Actif</flux:badge></flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex justify-end gap-1">
                                        <flux:button wire:click="openUserModal({{ $user->id }})" variant="filled" color="amber" size="sm" icon="pencil-square" aria-label="Modifier {{ $user->name }}" tooltip="Modifier" />
                                        @if($user->id !== auth()->id())
                                            <flux:button wire:click="deleteUser({{ $user->id }})" wire:confirm="Confirmer la suppression de cet utilisateur ?" variant="danger" size="sm" icon="trash" aria-label="Supprimer {{ $user->name }}" tooltip="Supprimer" />
                                        @else
                                            <flux:badge size="sm" icon="user-circle">Vous</flux:badge>
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row><flux:table.cell colspan="4"><flux:callout icon="users" heading="Aucun utilisateur trouvé" text="Modifiez la recherche ou créez un nouveau compte." /></flux:table.cell></flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
                <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">{{ $users->links() }}</div>
            </flux:card>
        </div>
    @endif

    <!-- TAB: LOGS -->
    @if($activeTab === 'logs')
    <div class="space-y-6">
        <div class="flex justify-between items-center bg-white dark:bg-gray-800/40 p-4 rounded-xl border border-gray-200 dark:border-gray-700/50 shadow-sm dark:shadow-none backdrop-blur-md">
            <div class="relative w-96">
                <flux:input type="text" wire:model.live.debounce.300ms="searchLogs" placeholder="Rechercher dans l'historique..." 
                    class="w-full bg-gray-50 dark:bg-gray-900/50 border border-gray-300 dark:border-gray-600/50 text-slate-900 dark:text-white text-sm rounded-lg focus:ring-purple-500 focus:border-purple-500 block pl-10 p-2.5" />
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="fas fa-search text-slate-500"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800/40 rounded-xl border border-gray-200 dark:border-gray-700/50 overflow-hidden shadow-sm dark:shadow-none backdrop-blur-md">
            <div class="overflow-x-auto">
                <flux:table class="w-full text-sm text-left text-slate-700 dark:text-gray-300">
                    <flux:table.columns class="text-xs text-slate-600 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-900/50">
                        <flux:table.row>
                            <flux:table.column class="px-6 py-3"><x-table-heading icon="calendar-days">Date</x-table-heading></flux:table.column>
                            <flux:table.column class="px-6 py-3"><x-table-heading icon="user">Utilisateur</x-table-heading></flux:table.column>
                            <flux:table.column class="px-6 py-3"><x-table-heading icon="bolt">Action</x-table-heading></flux:table.column>
                            <flux:table.column class="px-6 py-3"><x-table-heading icon="bars-3-bottom-left">Description</x-table-heading></flux:table.column>
                            <flux:table.column class="px-6 py-3"><x-table-heading icon="globe-alt">IP</x-table-heading></flux:table.column>
                        </flux:table.row>
                    </flux:table.columns>
                    <flux:table.rows class="divide-y divide-gray-200 dark:divide-gray-700/50">
                        @forelse($logs as $log)
                        <flux:table.row class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <flux:table.cell class="px-6 py-4 whitespace-nowrap text-slate-600 dark:text-gray-300 text-xs">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </flux:table.cell>
                            <flux:table.cell class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                {{ $log->user->name ?? 'Système' }}
                            </flux:table.cell>
                            <flux:table.cell class="px-6 py-4">
                                <span class="bg-gray-100 dark:bg-gray-900/80 text-gray-700 dark:text-gray-300 text-xs font-mono px-2 py-1 rounded border border-gray-300 dark:border-gray-700">
                                    {{ $log->action }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell class="px-6 py-4">{{ $log->description }}</flux:table.cell>
                            <flux:table.cell class="px-6 py-4 text-xs font-mono text-slate-600 dark:text-gray-300">{{ $log->ip_address }}</flux:table.cell>
                        </flux:table.row>
                        @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="px-6 py-8 text-center text-slate-600 dark:text-gray-300">
                                <i class="fas fa-history text-4xl mb-3 opacity-20"></i>
                                <p>Aucun journal d'activité trouvé.</p>
                            </flux:table.cell>
                        </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700/50">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
    @endif

    <!-- TAB: PAYMENT METHODS -->
    @if($activeTab === 'payment_methods')
        <div class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:card class="p-5!"><div class="flex items-center gap-3"><div class="grid size-10 place-items-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10"><flux:icon.credit-card class="size-5" /></div><div><flux:text>Total</flux:text><flux:heading size="xl">{{ $paymentMethodsList->count() }}</flux:heading></div></div></flux:card>
                <flux:card class="p-5!"><div class="flex items-center gap-3"><div class="grid size-10 place-items-center rounded-xl bg-green-50 text-green-600 dark:bg-green-500/10"><flux:icon.check-circle class="size-5" /></div><div><flux:text>Actifs</flux:text><flux:heading size="xl">{{ $paymentMethodsList->where('is_active', true)->count() }}</flux:heading></div></div></flux:card>
                <flux:card class="p-5!"><div class="flex items-center gap-3"><div class="grid size-10 place-items-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10"><flux:icon.lock-closed class="size-5" /></div><div><flux:text>Système</flux:text><flux:heading size="xl">{{ $paymentMethodsList->whereIn('code', ['cash', 'points'])->count() }}</flux:heading></div></div></flux:card>
            </div>

            <flux:card class="overflow-hidden p-0!">
                <div class="flex flex-col gap-4 bg-zinc-50/70 p-5 dark:bg-zinc-950/40 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="flex items-start gap-3"><span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-violet-600 text-white shadow-sm shadow-violet-600/20"><flux:icon.wallet class="size-5" /></span><div><flux:heading size="lg">Moyens de paiement</flux:heading><flux:text class="mt-1 max-w-2xl">Configurez les options proposées lors de l’encaissement et leur compte comptable OHADA.</flux:text></div></div>
                    <flux:button wire:click="openPaymentMethodModal" variant="primary" icon="plus">Ajouter un moyen</flux:button>
                </div>

                <div class="bg-white p-3 dark:bg-zinc-900 sm:p-4">
                    <div class="overflow-x-auto rounded-2xl ring-1 ring-zinc-200/80 dark:ring-zinc-800">
                        <flux:table class="w-full text-sm">
                            <flux:table.columns><flux:table.row class="bg-zinc-50/90 text-[11px] font-semibold uppercase tracking-[0.08em] text-zinc-500 dark:bg-zinc-950/60 dark:text-zinc-400"><flux:table.column class="w-[48%] px-5 py-4 sm:px-6 xl:w-[36%]"><x-table-heading icon="wallet">Moyen d’encaissement</x-table-heading></flux:table.column><flux:table.column class="max-xl:hidden px-5 py-4"><x-table-heading icon="hashtag">Code interne</x-table-heading></flux:table.column><flux:table.column class="max-xl:hidden px-5 py-4"><x-table-heading icon="building-library">Compte SYSCOHADA</x-table-heading></flux:table.column><flux:table.column class="px-5 py-4 text-center"><x-table-heading icon="signal">Disponibilité</x-table-heading></flux:table.column><flux:table.column class="px-5 py-4 text-right sm:px-6"><x-table-heading icon="ellipsis-horizontal">Actions</x-table-heading></flux:table.column></flux:table.row></flux:table.columns>
                            <flux:table.rows class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                                @forelse($paymentMethodsList as $pm)
                                    @php $isSystemPayment = in_array($pm->code, ['cash', 'points']); @endphp
                                    <flux:table.row :key="$pm->id" class="transition-colors hover:bg-violet-50/45 dark:hover:bg-violet-500/6">
                                        <flux:table.cell class="px-5 py-4.5 sm:px-6">
                                            <div class="flex items-center gap-3.5">
                                                <span class="grid size-11 shrink-0 place-items-center rounded-2xl {{ $pm->is_active ? 'bg-violet-600 text-white shadow-sm shadow-violet-600/20' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800' }}">
                                                    @switch($pm->code)
                                                        @case('cash') <flux:icon.banknotes class="size-5" /> @break
                                                        @case('bank') <flux:icon.building-library class="size-5" /> @break
                                                        @case('mobile_money') <flux:icon.device-phone-mobile class="size-5" /> @break
                                                        @case('yepay') <flux:icon.wallet class="size-5" /> @break
                                                        @case('points') <flux:icon.gift class="size-5" /> @break
                                                        @default <flux:icon.credit-card class="size-5" />
                                                    @endswitch
                                                </span>
                                                <div class="min-w-0"><p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $pm->name }}</p><p class="mt-1 flex items-center gap-1.5 text-xs text-zinc-500">@if($isSystemPayment)<flux:icon.lock-closed class="size-3.5" /><span>Protégé par le système</span>@else<flux:icon.adjustments-horizontal class="size-3.5" /><span>Moyen personnalisé</span>@endif</p></div>
                                            </div>
                                        </flux:table.cell>
                                        <flux:table.cell class="max-xl:hidden px-5 py-4.5"><flux:badge color="zinc" size="sm" class="font-mono uppercase">{{ $pm->code }}</flux:badge></flux:table.cell>
                                        <flux:table.cell class="max-xl:hidden px-5 py-4.5">@if($pm->syscohada_code)<div class="flex items-center gap-2"><span class="grid size-7 place-items-center rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-300"><flux:icon.building-library class="size-3.5" /></span><div><p class="font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $pm->syscohada_code }}</p><p class="text-xs text-zinc-500">Compte comptable</p></div></div>@else<flux:text size="sm">Non défini</flux:text>@endif</flux:table.cell>
                                        <flux:table.cell class="px-5 py-4.5 text-center">@if($pm->is_active)<flux:badge color="emerald" icon="check-circle" size="sm">Actif</flux:badge>@else<flux:badge color="rose" icon="x-circle" size="sm">Inactif</flux:badge>@endif</flux:table.cell>
                                        <flux:table.cell class="px-5 py-4.5 sm:px-6"><div class="flex justify-end"><flux:button.group class="yb-action-group"><flux:button wire:click="openPaymentMethodModal({{ $pm->id }})" variant="filled" color="amber" size="sm" icon="pencil-square" aria-label="Modifier {{ $pm->name }}" tooltip="Modifier" />@if(!$isSystemPayment)<flux:button wire:click="deletePaymentMethod({{ $pm->id }})" wire:confirm="Confirmer la suppression de ce moyen de paiement ?" variant="danger" size="sm" icon="trash" aria-label="Supprimer {{ $pm->name }}" tooltip="Supprimer" />@endif</flux:button.group></div></flux:table.cell>
                                    </flux:table.row>
                                @empty
                                    <flux:table.row><flux:table.cell colspan="5" class="px-6 py-14"><flux:callout icon="credit-card" heading="Aucun moyen de paiement" text="Ajoutez une option pour permettre les encaissements." /></flux:table.cell></flux:table.row>
                                @endforelse
                            </flux:table.rows>
                        </flux:table>
                    </div>
                </div>
            </flux:card>
            <flux:card class="p-5!">
                <form wire:submit="savePosPaymentProviders" class="space-y-5">
                    <div class="flex items-start gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300"><flux:icon.arrows-right-left class="size-5" /></span><div><flux:heading size="lg">Listes proposées dans le PDV</flux:heading><flux:text class="mt-1">Séparez les établissements par une virgule. Les modales Banque, Mobile Money et YePay utilisent directement ces paramètres.</flux:text></div></div>
                    <div class="grid gap-4 lg:grid-cols-3"><flux:textarea wire:model="posBanks" label="Banques" rows="5" placeholder="Rawbank, EquityBCDC…" /><flux:textarea wire:model="posMobileProviders" label="Opérateurs Mobile Money" rows="5" placeholder="M-Pesa, Airtel Money…" /><flux:textarea wire:model="posYepayProviders" label="Services YePay" rows="5" placeholder="YePay Wallet" /></div>
                    <div class="flex justify-end"><flux:button type="submit" variant="primary" color="cyan" icon="check">Synchroniser avec le PDV</flux:button></div>
                </form>
            </flux:card>
        </div>
    @endif

    <!-- TAB: TICKETS & PDF -->
    @if($activeTab === 'documents')
        @php
            $receiptTemplates = \App\Support\DocumentPreferences::receiptTemplates();
            $reportTemplates = \App\Support\DocumentPreferences::reportTemplates();
            $reportPreviewTheme = \App\Support\DocumentPreferences::reportTheme($report_template);
            $documentFonts = \App\Support\DocumentPreferences::fonts();
            $legalTopReceiptTemplates = ['elegant', 'eco', 'playlist', 'transaction', 'timestamp'];
            $previewCodeValue = match($receipt_code_source) {
                'company' => $company_name ?: 'YEBOOX',
                'custom' => $receipt_code_custom ?: 'VALEUR-PERSONNALISEE',
                default => 'REC-000123',
            };
            $receiptPreviewStyle = sprintf(
                'font-family:%s;font-size:%dpx;font-weight:%d',
                \App\Support\DocumentPreferences::fontStack($receipt_font_family),
                $receipt_font_size,
                $receipt_font_weight,
            );
            $reportPreviewStyle = sprintf(
                'font-family:%s;font-size:%dpx;font-weight:%d;border-color:%s;background-color:%s',
                \App\Support\DocumentPreferences::fontStack($report_font_family),
                $report_font_size,
                $report_font_weight,
                $reportPreviewTheme['border'],
                $reportPreviewTheme['soft'],
            );
        @endphp

        <form wire:submit="saveDocumentSettings" class="space-y-6">
            <flux:card class="p-4!">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="grid size-10 shrink-0 place-items-center rounded-xl bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400">
                            <flux:icon.bolt class="size-5" />
                        </div>
                        <div>
                            <flux:heading>Prévisualisation en temps réel</flux:heading>
                            <flux:text class="mt-1">Chaque choix actualise immédiatement le ticket et le rapport A4, avant l’enregistrement.</flux:text>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <flux:badge color="green" icon="signal">Active</flux:badge>
                        <flux:badge wire:dirty color="amber" icon="pencil-square">Modifications non enregistrées</flux:badge>
                        <span wire:loading.delay class="inline-flex items-center gap-2 text-sm text-zinc-500">
                            <flux:icon.arrow-path class="size-4 animate-spin" /> Mise à jour…
                        </span>
                    </div>
                </div>
            </flux:card>

            <style>
                .yb-admin-receipt { background: #fff !important; color: #09090b !important; border-color: #18181b !important; }
                .yb-admin-receipt .yb-receipt-header { position: relative; }
                .yb-admin-receipt[data-receipt-template="classic"] .yb-receipt-header { padding-bottom: .7rem; border-bottom: 2px dashed #18181b; }
                .yb-admin-receipt[data-receipt-template="compact"] { padding: .75rem !important; letter-spacing: -.02em; }
                .yb-admin-receipt[data-receipt-template="compact"] .yb-receipt-header { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; text-align: left; }
                .yb-admin-receipt[data-receipt-template="compact"] .yb-receipt-section { margin-block: .35rem; }
                .yb-admin-receipt[data-receipt-template="modern"] { border: 3px solid #18181b !important; }
                .yb-admin-receipt[data-receipt-template="modern"] .yb-receipt-header { padding-bottom: .65rem; border-bottom: 5px solid #18181b; text-align: left; }
                .yb-admin-receipt[data-receipt-template="modern"] .yb-receipt-brand { letter-spacing: -.04em; }
                .yb-admin-receipt[data-receipt-template="elegant"] { font-family: Georgia, serif !important; border-inline: 0 !important; }
                .yb-admin-receipt[data-receipt-template="elegant"] .yb-receipt-header { padding-block: .65rem; border-block: 3px double #18181b; letter-spacing: .12em; }
                .yb-admin-receipt[data-receipt-template="boxed"] { border: 3px double #18181b !important; }
                .yb-admin-receipt[data-receipt-template="boxed"] .yb-receipt-section { margin-block: .55rem; padding: .55rem; border: 1px solid #18181b; }
                .yb-admin-receipt[data-receipt-template="minimal"] { border-color: transparent !important; box-shadow: none !important; }
                .yb-admin-receipt[data-receipt-template="minimal"] .yb-receipt-header { text-align: left; }
                .yb-admin-receipt[data-receipt-template="minimal"] .yb-receipt-brand { font-size: 1.05em; letter-spacing: .18em; }
                .yb-admin-receipt[data-receipt-template="bold"] { border: 4px solid #18181b !important; }
                .yb-admin-receipt[data-receipt-template="bold"] .yb-receipt-brand { font-size: 1.55em; line-height: 1; }
                .yb-admin-receipt[data-receipt-template="bold"] .yb-receipt-total { margin-inline: -.4rem; padding: .55rem .4rem; border-block: 5px solid #18181b; font-size: 1.45em; }
                .yb-admin-receipt[data-receipt-template="friendly"] { border: 2px dashed #18181b !important; border-radius: 1.1rem; }
                .yb-admin-receipt[data-receipt-template="friendly"] .yb-receipt-header { padding-inline: 1.25rem; }
                .yb-admin-receipt[data-receipt-template="friendly"] .yb-receipt-header::before, .yb-admin-receipt[data-receipt-template="friendly"] .yb-receipt-header::after { position: absolute; top: .15rem; content: "✦"; }
                .yb-admin-receipt[data-receipt-template="friendly"] .yb-receipt-header::before { left: 0; }
                .yb-admin-receipt[data-receipt-template="friendly"] .yb-receipt-header::after { right: 0; }
                .yb-admin-receipt[data-receipt-template="fiscal"] { border: 1px solid #18181b !important; border-top-width: 7px !important; }
                .yb-admin-receipt[data-receipt-template="fiscal"] .yb-receipt-header { text-align: left; }
                .yb-admin-receipt[data-receipt-template="fiscal"] .yb-receipt-legal { padding: .5rem; border: 1px solid #18181b; text-align: left; }
                .yb-admin-receipt[data-receipt-template="premium"] { border: 4px double #18181b !important; }
                .yb-admin-receipt[data-receipt-template="premium"] .yb-receipt-header { padding-block: .75rem; border-block: 1px solid #18181b; }
                .yb-admin-receipt[data-receipt-template="premium"] .yb-receipt-brand { font-family: Georgia, serif; font-weight: 600; letter-spacing: .16em; }
                .yb-admin-receipt[data-receipt-template="premium"] .yb-receipt-total { padding-block: .5rem; border-block: 3px double #18181b; }
                .yb-admin-receipt[data-receipt-template="ribbon"] { border: 2px solid #18181b !important; }
                .yb-admin-receipt[data-receipt-template="ribbon"] .yb-receipt-header { margin-inline: -.65rem; padding: .6rem .65rem; border-block: 4px solid #18181b; }
                .yb-admin-receipt[data-receipt-template="ribbon"] .yb-receipt-header::before { content: "◆"; position: absolute; left: .3rem; top: 50%; transform: translateY(-50%); }
                .yb-admin-receipt[data-receipt-template="ribbon"] .yb-receipt-header::after { content: "◆"; position: absolute; right: .3rem; top: 50%; transform: translateY(-50%); }
                .yb-admin-receipt[data-receipt-template="eco"] { padding: .65rem !important; border: 1px dashed #18181b !important; font-size: 9px !important; letter-spacing: -.02em; }
                .yb-admin-receipt[data-receipt-template="eco"] .yb-receipt-section { margin-block: .25rem; }
                .yb-admin-receipt[data-receipt-template="eco"] .yb-receipt-header { display: flex; align-items: baseline; justify-content: space-between; gap: .4rem; text-align: left; }
                .yb-admin-receipt[data-receipt-template="eco"] .yb-receipt-legal { margin-top: .35rem; }
                .yb-admin-receipt[data-receipt-paper="58"] { width: 13.25rem; }
                .yb-admin-receipt[data-receipt-paper="76"] { width: 16rem; }
                .yb-admin-receipt[data-receipt-paper="80"] { width: 18rem; }
                .yb-admin-receipt[data-receipt-template="transaction"] .yb-receipt-header { text-align: left; border-bottom: 3px double #18181b; padding-bottom: .55rem; }
                .yb-admin-receipt[data-receipt-template="transaction"] .yb-receipt-meta { border: 1px solid #18181b; padding: .5rem; }
                .yb-admin-receipt[data-receipt-template="playlist"] .yb-receipt-brand { font-size: 1.55em; letter-spacing: -.05em; }
                .yb-admin-receipt[data-receipt-template="playlist"] .yb-receipt-meta { text-transform: uppercase; border-bottom: 1px dashed #18181b; padding-bottom: .5rem; }
                .yb-admin-receipt[data-receipt-template="playlist"] .yb-receipt-items { border-block: 1px dashed #18181b; padding-block: .5rem; }
                .yb-admin-receipt[data-receipt-template="ledger_receipt"] { border-inline: 2px solid #18181b !important; }
                .yb-admin-receipt[data-receipt-template="ledger_receipt"] .yb-receipt-section { border-bottom: 1px solid #18181b; padding-block: .45rem; }
                .yb-admin-receipt[data-receipt-template="vat_summary"] { border-top: 2px dashed #18181b !important; }
                .yb-admin-receipt[data-receipt-template="vat_summary"] .yb-receipt-legal { border-block: 4px double #18181b; padding-block: .6rem; font-size: .82em; font-weight: 700; }
                .yb-admin-receipt[data-receipt-template="vat_summary"] .yb-receipt-total { border-top: 1px dashed #18181b; padding-top: .5rem; }
                .yb-admin-receipt[data-receipt-template="merchant"] .yb-receipt-header { padding: .65rem; border: 2px solid #18181b; text-align: left; }
                .yb-admin-receipt[data-receipt-template="merchant"] .yb-receipt-brand { display: block; border-bottom: 1px solid #18181b; padding-bottom: .35rem; margin-bottom: .35rem; }
                .yb-admin-receipt[data-receipt-template="columnar"] .yb-receipt-meta { display: grid; grid-template-columns: 1fr 1fr; gap: .2rem .75rem; }
                .yb-admin-receipt[data-receipt-template="columnar"] .yb-receipt-items { border-block: 3px double #18181b; padding-block: .55rem; }
                .yb-admin-receipt[data-receipt-template="timestamp"] .yb-receipt-meta { border-left: 5px solid #18181b; padding: .45rem .6rem; font-size: .95em; }
                .yb-admin-receipt[data-receipt-template="timestamp"] .yb-receipt-meta > div:nth-child(2), .yb-admin-receipt[data-receipt-template="timestamp"] .yb-receipt-meta > div:nth-child(3) { font-weight: 800; }
                .yb-admin-receipt[data-receipt-template="loyalty"] { border-bottom: 6px solid #18181b !important; }
                .yb-admin-receipt[data-receipt-template="loyalty"] .yb-receipt-footer { border: 2px dashed #18181b; padding: .65rem; font-weight: 700; text-transform: uppercase; }
                .yb-admin-receipt:is([data-receipt-template="elegant"],[data-receipt-template="eco"],[data-receipt-template="playlist"],[data-receipt-template="transaction"],[data-receipt-template="timestamp"]) .yb-receipt-legal--top { margin-top: .6rem; padding-block: .45rem; border-bottom: 1px dashed #18181b; text-align: left; }
                .yb-admin-receipt:is([data-receipt-template="elegant"],[data-receipt-template="eco"],[data-receipt-template="playlist"],[data-receipt-template="transaction"],[data-receipt-template="timestamp"]) .yb-receipt-meta { margin-top: .55rem; padding-bottom: .55rem; border-bottom: 1px dashed #18181b; }
                .yb-admin-receipt:is([data-receipt-template="elegant"],[data-receipt-template="eco"],[data-receipt-template="playlist"],[data-receipt-template="transaction"],[data-receipt-template="timestamp"]) .yb-receipt-items { margin-top: .55rem; padding-bottom: .55rem; border-bottom: 2px dashed #18181b; }
                .yb-admin-receipt:is([data-receipt-template="elegant"],[data-receipt-template="eco"],[data-receipt-template="playlist"],[data-receipt-template="transaction"],[data-receipt-template="timestamp"]) .yb-receipt-total { padding-block: .55rem; border-bottom: 3px double #18181b; }
                .yb-admin-receipt[data-receipt-template="eco"] .yb-receipt-legal--top, .yb-admin-receipt[data-receipt-template="eco"] .yb-receipt-meta, .yb-admin-receipt[data-receipt-template="eco"] .yb-receipt-items { margin-top: .3rem; padding-block: .25rem; }
            </style>

            <flux:card class="p-2!">
                <div class="flex gap-2 overflow-x-auto" role="tablist" aria-label="Types de documents">
                    <flux:button
                        type="button"
                        wire:click="$set('documentSection', 'tickets')"
                        :variant="$documentSection === 'tickets' ? 'primary' : 'ghost'"
                        icon="receipt-percent"
                        class="min-w-52 flex-1"
                        role="tab"
                        aria-controls="tickets-panel"
                        :aria-selected="$documentSection === 'tickets' ? 'true' : 'false'"
                    >Tickets de caisse <flux:badge size="sm" inset="top bottom">20</flux:badge></flux:button>
                    <flux:button
                        type="button"
                        wire:click="$set('documentSection', 'documents')"
                        :variant="$documentSection === 'documents' ? 'primary' : 'ghost'"
                        icon="document-chart-bar"
                        class="min-w-52 flex-1"
                        role="tab"
                        aria-controls="documents-panel"
                        :aria-selected="$documentSection === 'documents' ? 'true' : 'false'"
                    >Documents PDF & Word <flux:badge size="sm" inset="top bottom">14</flux:badge></flux:button>
                </div>
            </flux:card>

            @if($documentSection === 'tickets')
            <div id="tickets-panel" role="tabpanel" class="space-y-6">
            <div class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_23rem]">
                <div class="space-y-6">
                    <flux:card class="p-6!">
                        <div class="mb-5 flex items-start justify-between gap-4">
                            <div>
                                <flux:heading size="lg">20 modèles de ticket de caisse</flux:heading>
                                <flux:text class="mt-1">Choisissez la structure utilisée automatiquement après chaque encaissement.</flux:text>
                            </div>
                            <flux:badge color="violet" icon="receipt-percent">{{ $receipt_paper_width }} mm</flux:badge>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach($receiptTemplates as $template => $meta)
                                @php
                                    $templateIndex = $loop->iteration;
                                @endphp
                                <flux:button
                                    type="button"
                                    wire:click="$set('receipt_template', '{{ $template }}')"
                                    :variant="$receipt_template === $template ? 'primary' : 'outline'"
                                    class="h-auto! min-h-32! items-stretch! justify-start! p-3! text-left!"
                                    wire:key="receipt-template-{{ $template }}"
                                >
                                    <span class="flex w-full flex-col gap-2">
                                        <svg viewBox="0 0 120 54" class="h-14 w-full rounded-md bg-white text-zinc-950" aria-hidden="true">
                                            @if(in_array($templateIndex, [3, 7]))<rect x="5" y="4" width="110" height="8" rx="2" fill="currentColor" opacity=".9"/>@endif
                                            @if(in_array($templateIndex, [4, 8]))<circle cx="60" cy="10" r="6" fill="currentColor" opacity=".75"/>@endif
                                            @if($templateIndex === 5)<rect x="4" y="3" width="112" height="48" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>@endif
                                            @if($templateIndex === 9)<rect x="4" y="3" width="112" height="5" fill="currentColor"/><rect x="8" y="43" width="104" height="7" fill="none" stroke="currentColor"/>@endif
                                            @if($templateIndex === 10)<rect x="4" y="3" width="112" height="48" rx="2" fill="none" stroke="currentColor" stroke-width="3"/><rect x="8" y="7" width="104" height="40" rx="1" fill="none" stroke="currentColor"/>@endif
                                            @if($templateIndex === 11)<path d="M4 4h112v14l-56 8L4 18z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 10h34M74 10h34" stroke="currentColor" stroke-width="2"/>@endif
                                            @if($templateIndex === 12)<path d="M5 12h110M5 23h110M5 34h110M5 45h110" stroke="currentColor" opacity=".25"/>@endif
                                            @if($templateIndex === 13)<path d="M6 5h108M6 13h108" stroke="currentColor" stroke-width="2"/><rect x="7" y="18" width="106" height="12" fill="none" stroke="currentColor"/>@endif
                                            @if($templateIndex === 14)<path d="M7 20h106M7 29h106M7 38h106" stroke="currentColor" stroke-dasharray="3 2"/>@endif
                                            @if($templateIndex === 15)<path d="M7 5v44M38 20v29M87 20v29M113 5v44" stroke="currentColor"/><path d="M7 20h106M7 39h106" stroke="currentColor"/>@endif
                                            @if($templateIndex === 16)<rect x="7" y="4" width="106" height="15" fill="none" stroke="currentColor" stroke-width="2"/><path d="M7 27h106M7 43h106" stroke="currentColor" stroke-dasharray="4 2"/>@endif
                                            @if($templateIndex === 17)<rect x="6" y="5" width="108" height="17" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 12h50M12 17h34" stroke="currentColor"/>@endif
                                            @if($templateIndex === 18)<path d="M7 18h106M7 40h106M24 18v22M88 18v22" stroke="currentColor" stroke-width="1.5"/>@endif
                                            @if($templateIndex === 19)<rect x="7" y="17" width="106" height="16" fill="none" stroke="currentColor"/><rect x="7" y="17" width="5" height="16" fill="currentColor"/>@endif
                                            @if($templateIndex === 20)<path d="M7 7h106M7 47h106" stroke="currentColor" stroke-width="3"/><rect x="20" y="31" width="80" height="11" rx="2" fill="none" stroke="currentColor" stroke-dasharray="3 2"/>@endif
                                            <rect x="{{ in_array($templateIndex, [2, 6]) ? 8 : 20 }}" y="{{ in_array($templateIndex, [3, 7]) ? 17 : 8 }}" width="{{ in_array($templateIndex, [2, 6]) ? 58 : 80 }}" height="3" rx="1.5" fill="currentColor" opacity=".75"/>
                                            <rect x="10" y="25" width="100" height="1" fill="currentColor" opacity=".25"/>
                                            <rect x="10" y="31" width="62" height="2" rx="1" fill="currentColor" opacity=".45"/>
                                            <rect x="82" y="31" width="28" height="2" rx="1" fill="currentColor" opacity=".75"/>
                                            <rect x="10" y="38" width="48" height="2" rx="1" fill="currentColor" opacity=".35"/>
                                            <rect x="74" y="42" width="36" height="4" rx="1" fill="currentColor"/>
                                        </svg>
                                        <span class="font-semibold">{{ $meta['label'] }}</span>
                                        <span class="text-xs opacity-70">{{ $meta['description'] }}</span>
                                    </span>
                                </flux:button>
                            @endforeach
                        </div>
                    </flux:card>

                    <flux:card class="p-6!">
                        <div class="mb-5">
                            <flux:heading size="lg">Code et typographie du ticket</flux:heading>
                            <flux:text class="mt-1">Le QR ou le code-barres est généré localement en SVG, sans service externe.</flux:text>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                            <flux:select label="Largeur du papier" wire:model.live="receipt_paper_width" icon="arrows-right-left">
                                <flux:select.option value="58">58 mm · Compact</flux:select.option>
                                <flux:select.option value="76">76 mm · Intermédiaire</flux:select.option>
                                <flux:select.option value="80">80 mm · Standard</flux:select.option>
                            </flux:select>
                            <flux:select label="Code imprimé" wire:model.live="receipt_code_type" icon="qr-code">
                                <flux:select.option value="none">Aucun code</flux:select.option>
                                <flux:select.option value="qr">QR code</flux:select.option>
                                <flux:select.option value="barcode">Code-barres Code 39</flux:select.option>
                            </flux:select>
                            <flux:select label="Contenu du code" wire:model.live="receipt_code_source" :disabled="$receipt_code_type === 'none'">
                                <flux:select.option value="reference">Numéro du ticket</flux:select.option>
                                <flux:select.option value="company">Nom de l’entreprise</flux:select.option>
                                <flux:select.option value="custom">Valeur personnalisée</flux:select.option>
                            </flux:select>
                            <flux:input label="Valeur personnalisée" wire:model.live.debounce.300ms="receipt_code_custom" :disabled="$receipt_code_type === 'none' || $receipt_code_source !== 'custom'" maxlength="120" placeholder="Ex. https://monsite.cd/avis" />
                            <flux:select label="Police" wire:model.live="receipt_font_family" icon="language">
                                @foreach($documentFonts as $font => $meta)<flux:select.option value="{{ $font }}">{{ $meta['label'] }}</flux:select.option>@endforeach
                            </flux:select>
                            <flux:input label="Taille du texte (px)" type="number" min="8" max="18" wire:model.live.debounce.200ms="receipt_font_size" />
                            <flux:select label="Graisse" wire:model.live="receipt_font_weight">
                                <flux:select.option value="300">Légère · 300</flux:select.option>
                                <flux:select.option value="400">Normale · 400</flux:select.option>
                                <flux:select.option value="500">Moyenne · 500</flux:select.option>
                                <flux:select.option value="600">Semi-grasse · 600</flux:select.option>
                                <flux:select.option value="700">Grasse · 700</flux:select.option>
                                <flux:select.option value="800">Extra-grasse · 800</flux:select.option>
                            </flux:select>
                        </div>

                        <flux:separator class="my-6" />
                        <div class="mb-4">
                            <flux:heading>Mentions fiscales imprimées</flux:heading>
                            <flux:text class="mt-1">Ces informations sont reprises sur le ticket de caisse et les documents PDF.</flux:text>
                        </div>
                        <div class="grid gap-5 md:grid-cols-3">
                            <flux:input label="RCCM" wire:model.live.debounce.300ms="company_rccm" icon="building-office-2" placeholder="Ex. CD/KIN/RCCM/26-B-001" />
                            <flux:input label="N° Impôt / NIF" wire:model.live.debounce.300ms="company_nif" icon="identification" placeholder="Ex. A1234567Z" />
                            <flux:input label="N° TVA" wire:model.live.debounce.300ms="company_tva" icon="receipt-percent" placeholder="Ex. TVA-243-001" />
                        </div>
                    </flux:card>
                </div>

                <aside class="2xl:sticky 2xl:top-24">
                    <flux:card class="relative p-5!">
                        <div class="mb-4 flex items-center justify-between">
                            <flux:heading>Aperçu ticket</flux:heading>
                            <flux:badge color="green" icon="bolt">Temps réel</flux:badge>
                        </div>
                        <div wire:loading.class="opacity-60" class="yb-admin-receipt mx-auto max-w-72 bg-white p-5 text-black shadow-sm ring-1 ring-black/10 transition-all" data-receipt-template="{{ $receipt_template }}" data-receipt-paper="{{ $receipt_paper_width }}" style="{{ $receiptPreviewStyle }}">
                            <div class="yb-receipt-header text-center">
                                <div class="yb-receipt-brand text-lg font-black uppercase">{{ $company_name ?: 'YEBOOX' }}</div>
                                <div class="mt-1 text-[.8em] opacity-70">{{ $company_address ?: 'Adresse de l’entreprise' }}</div>
                            </div>
                            @if(in_array($receipt_template, $legalTopReceiptTemplates, true) && ($company_rccm || $company_nif || $company_tva))
                                <div class="yb-receipt-legal yb-receipt-legal--top space-y-0.5 text-[.72em]">
                                    @if($company_rccm)<div>RCCM : {{ $company_rccm }}</div>@endif
                                    @if($company_nif)<div>N° Impôt : {{ $company_nif }}</div>@endif
                                    @if($company_tva)<div>TVA : {{ $company_tva }}</div>@endif
                                </div>
                            @endif
                            <div class="yb-receipt-separator my-3 border-t border-dashed border-black/60"></div>
                            <div class="yb-receipt-meta yb-receipt-section space-y-1 text-[.82em]"><div class="flex justify-between"><span>Ticket</span><strong>REC-000123</strong></div><div class="flex justify-between"><span>Date</span><span>31/08/2026</span></div><div class="flex justify-between"><span>Heure</span><span>14:20:04</span></div><div class="flex justify-between"><span>Caissier</span><span>{{ auth()->user()->name }}</span></div></div>
                            <div class="yb-receipt-items yb-receipt-section mt-3 space-y-1 text-[.88em]"><div class="flex justify-between"><span>Article exemple</span><span>20,00 $</span></div><div class="flex justify-between"><span>Service exemple</span><span>35,00 $</span></div></div>
                            <div class="my-3 border-t-2 border-black"></div>
                            <div class="yb-receipt-total flex justify-between text-[1.15em] font-black"><span>TOTAL</span><span>55,00 $</span></div>
                            @if($receipt_code_type !== 'none')
                                <div class="mx-auto mt-4 max-w-36 text-center [&_svg]:mx-auto [&_svg]:max-h-28">
                                    {!! \App\Support\DocumentCode::render($receipt_code_type, $previewCodeValue, 112) !!}
                                </div>
                            @endif
                            @if(!in_array($receipt_template, $legalTopReceiptTemplates, true) && ($company_rccm || $company_nif || $company_tva))
                                <div class="yb-receipt-legal mt-4 space-y-0.5 text-center text-[.72em] opacity-75">
                                    @if($company_rccm)<div>RCCM : {{ $company_rccm }}</div>@endif
                                    @if($company_nif)<div>N° Impôt : {{ $company_nif }}</div>@endif
                                    @if($company_tva)<div>TVA : {{ $company_tva }}</div>@endif
                                </div>
                            @endif
                            <div class="yb-receipt-footer mt-4 text-center text-[.8em] opacity-70">{{ $receipt_footer ?: 'Merci pour votre confiance.' }}</div>
                        </div>
                    </flux:card>
                </aside>
            </div>
            </div>
            @endif

            @if($documentSection === 'documents')
            <div id="documents-panel" role="tabpanel">
            <flux:card class="p-6!">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <flux:heading size="lg">14 modèles de documents A4</flux:heading>
                        <flux:text class="mt-1">Le même modèle s’applique aux rapports, PDF, factures et fichiers Word DOCX.</flux:text>
                    </div>
                    <flux:badge color="blue" icon="document-chart-bar">PDF + Word · A4</flux:badge>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-7">
                    @foreach($reportTemplates as $template => $meta)
                        @php
                            $reportIndex = $loop->iteration;
                            $templateTheme = \App\Support\DocumentPreferences::reportTheme($template);
                        @endphp
                        <flux:button type="button" wire:click="$set('report_template', '{{ $template }}')" :variant="$report_template === $template ? 'primary' : 'outline'" class="h-auto! min-h-40! items-stretch! justify-start! p-3! text-left!" wire:key="report-template-{{ $template }}">
                            <span class="flex w-full flex-col gap-2">
                                <svg viewBox="0 0 90 120" class="mx-auto h-24 rounded-sm bg-white text-zinc-950 shadow-sm" aria-hidden="true">
                                    <rect x="3" y="3" width="84" height="114" fill="white" stroke="currentColor" opacity=".95"/>
                                    @if(in_array($reportIndex, [1, 3, 5, 8]))<path d="M3 3h84v25H3z" fill="{{ $templateTheme['accent'] }}" opacity="{{ $reportIndex === 5 ? '.22' : '.85' }}"/>@endif
                                    @if($reportIndex === 4)<rect x="8" y="8" width="28" height="104" fill="{{ $templateTheme['accent'] }}" opacity=".16"/>@endif
                                    @if($reportIndex === 7)<path d="M3 18h84M3 102h84" stroke="{{ $templateTheme['accent'] }}" stroke-width="2"/>@endif
                                    @if($reportIndex === 9)<rect x="7" y="7" width="76" height="106" fill="none" stroke="{{ $templateTheme['accent'] }}" stroke-width="2"/>@endif
                                    @if($reportIndex === 10)<rect x="3" y="3" width="84" height="10" fill="{{ $templateTheme['accent'] }}"/>@endif
                                    @if($reportIndex === 11)<path d="M3 3h84v8H3zM3 109h84v8H3z" fill="{{ $templateTheme['accent'] }}"/><path d="M8 16h74" stroke="{{ $templateTheme['dark'] }}" stroke-width="2"/>@endif
                                    @if($reportIndex === 12)<path d="M3 13h84M3 107h84" stroke="{{ $templateTheme['accent'] }}" stroke-width="3"/><circle cx="45" cy="13" r="5" fill="{{ $templateTheme['accent'] }}"/>@endif
                                    @if($reportIndex === 13)<path d="M3 3h84L62 30H3z" fill="{{ $templateTheme['accent'] }}" opacity=".9"/><path d="M87 117H3l25-25h59z" fill="{{ $templateTheme['accent'] }}" opacity=".2"/>@endif
                                    @if($reportIndex === 14)<path d="M3 3h84v19H3z" fill="{{ $templateTheme['dark'] }}"/><path d="M10 31h70M10 35h45" stroke="{{ $templateTheme['accent'] }}" stroke-width="2"/><path d="M10 103h70" stroke="{{ $templateTheme['accent'] }}" stroke-dasharray="2 2"/>@endif
                                    <rect x="{{ $reportIndex === 4 ? 42 : 10 }}" y="{{ in_array($reportIndex, [1,3,5,8]) ? 12 : 15 }}" width="38" height="4" rx="1" fill="currentColor" opacity=".8"/>
                                    <rect x="10" y="38" width="70" height="8" rx="1" fill="{{ $templateTheme['accent'] }}" opacity=".16"/>
                                    <path d="M10 55h70M10 65h70M10 75h70M10 85h70M10 95h70" stroke="currentColor" opacity=".25"/>
                                </svg>
                                <span class="font-semibold">{{ $meta['label'] }}</span>
                                <span class="text-xs opacity-70">{{ $meta['description'] }}</span>
                            </span>
                        </flux:button>
                    @endforeach
                </div>

                <flux:separator class="my-6" />
                <div class="grid gap-5 md:grid-cols-3">
                    <flux:select label="Police des rapports" wire:model.live="report_font_family" icon="language">
                        @foreach($documentFonts as $font => $meta)<flux:select.option value="{{ $font }}">{{ $meta['label'] }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input label="Taille du texte (px)" type="number" min="9" max="16" wire:model.live.debounce.200ms="report_font_size" />
                    <flux:select label="Graisse" wire:model.live="report_font_weight">
                        <flux:select.option value="300">Légère · 300</flux:select.option><flux:select.option value="400">Normale · 400</flux:select.option><flux:select.option value="500">Moyenne · 500</flux:select.option><flux:select.option value="600">Semi-grasse · 600</flux:select.option><flux:select.option value="700">Grasse · 700</flux:select.option><flux:select.option value="800">Extra-grasse · 800</flux:select.option>
                    </flux:select>
                </div>

                <div class="mt-6 overflow-hidden rounded-xl bg-zinc-100 p-4 dark:bg-zinc-900">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <flux:heading size="sm">Aperçu PDF & Word</flux:heading>
                            <flux:text class="text-xs">En-tête, logo, sommaire, tableaux, pied de page et typographie synchronisés.</flux:text>
                        </div>
                        <flux:badge color="green" icon="bolt">Temps réel</flux:badge>
                    </div>
                    <div wire:loading.class="opacity-60" class="mx-auto aspect-[210/120] max-w-3xl overflow-hidden bg-white p-6 text-black shadow transition-opacity" data-report-template="{{ $report_template }}" style="{{ $reportPreviewStyle }}">
                        <div class="flex items-start justify-between border-b-2 pb-4" style="border-color:{{ $reportPreviewTheme['accent'] }}"><div><strong class="text-xl uppercase" style="color:{{ $reportPreviewTheme['dark'] }}">{{ $company_name ?: 'YEBOOX' }}</strong><div class="mt-1 text-[.75em] opacity-60">{{ $company_address ?: 'Adresse de l’entreprise' }}</div></div><div class="text-right"><strong class="text-2xl">RAPPORT</strong><div class="text-[.75em] opacity-60">31/08/2026</div></div></div>
                        <div class="mt-4 border-l-4 p-2 text-[.7em]" style="border-color:{{ $reportPreviewTheme['accent'] }};background:{{ $reportPreviewTheme['soft'] }}"><strong>TABLE DES MATIÈRES</strong><div class="mt-1 flex justify-between"><span>01 · Synthèse</span><span>02 · Détails</span><span>03 · Annexes</span></div></div>
                        <div class="mt-4 grid grid-cols-3 gap-3"><div class="rounded border p-3"><small>Ventes</small><strong class="block text-lg">128</strong></div><div class="rounded border p-3"><small>Chiffre d’affaires</small><strong class="block text-lg">4 850 $</strong></div><div class="rounded border p-3"><small>Panier moyen</small><strong class="block text-lg">37,89 $</strong></div></div>
                        <div class="mt-4 h-2 rounded" style="background:linear-gradient(90deg,{{ $reportPreviewTheme['accent'] }} 0 68%,#e4e4e7 68%)"></div>
                        @if($company_rccm || $company_nif || $company_tva)<div class="mt-4 border-t pt-2 text-[.68em] opacity-60">RCCM : {{ $company_rccm ?: '—' }} · N° Impôt : {{ $company_nif ?: '—' }} · TVA : {{ $company_tva ?: '—' }}</div>@endif
                    </div>
                </div>
            </flux:card>
            </div>
            @endif

            <flux:card class="p-4!">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="grid size-9 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-500/10"><flux:icon.check-circle class="size-5" /></div>
                        <div><flux:heading size="sm">Configuration prête</flux:heading><flux:text class="text-xs">Le modèle et le format sélectionnés seront utilisés par les tickets et Electron.</flux:text></div>
                    </div>
                    <flux:button type="submit" variant="primary" icon="check" class="w-full justify-center sm:w-auto sm:min-w-64">Appliquer les modèles partout</flux:button>
                </div>
            </flux:card>
        </form>
    @endif

    <!-- TAB: RESTAURANTS -->
    @if($activeTab === 'restaurants')
        <form wire:submit="saveRestaurantSettings" class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:card class="p-5!"><div class="flex items-center gap-3"><div class="grid size-10 place-items-center rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-500/10"><flux:icon.building-storefront class="size-5" /></div><div><flux:text>Zones</flux:text><flux:heading size="xl">{{ count($restaurant_tables) }}</flux:heading></div></div></flux:card>
                <flux:card class="p-5!"><div class="flex items-center gap-3"><div class="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10"><flux:icon.squares-2x2 class="size-5" /></div><div><flux:text>Tables configurées</flux:text><flux:heading size="xl">{{ collect($restaurant_tables)->sum(fn($zone) => count(array_filter(array_map('trim', explode(',', $zone['tables'] ?? ''))))) }}</flux:heading></div></div></flux:card>
                <flux:card class="p-5!"><div class="flex items-center gap-3"><div class="grid size-10 place-items-center rounded-xl bg-green-50 text-green-600 dark:bg-green-500/10"><flux:icon.printer class="size-5" /></div><div><flux:text>Impression</flux:text><flux:heading size="lg">{{ $kitchen_print_mode === 'ip' ? 'Réseau' : 'Navigateur' }}</flux:heading></div></div></flux:card>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <flux:card class="p-6!">
                    <div class="mb-5 flex items-center justify-between">
                        <div><flux:heading size="lg">Plan de salle</flux:heading><flux:text class="mt-1">Définissez les zones et les tables du restaurant.</flux:text></div>
                        <flux:button type="button" wire:click="addTableZone" icon="plus" size="sm">Zone</flux:button>
                    </div>
                    <div class="space-y-3">
                        @forelse($restaurant_tables as $index => $zone)
                            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800" wire:key="restaurant-zone-{{ $index }}">
                                <div class="mb-3 flex items-center justify-between"><flux:badge color="orange" icon="map-pin">Zone {{ $index + 1 }}</flux:badge><flux:button type="button" wire:click="removeTableZone({{ $index }})" variant="ghost" size="sm" icon="trash" class="text-red-600!" aria-label="Supprimer la zone {{ $index + 1 }}" /></div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:input label="Nom de la zone" wire:model.defer="restaurant_tables.{{ $index }}.zone" placeholder="Terrasse" />
                                    <flux:input label="Tables" wire:model.defer="restaurant_tables.{{ $index }}.tables" placeholder="T1, T2, T3" description="Séparez les tables par des virgules." />
                                </div>
                            </div>
                        @empty
                            <flux:callout icon="map" heading="Aucune zone" text="Ajoutez une zone pour commencer le plan de salle." />
                        @endforelse
                    </div>
                </flux:card>

                <flux:card class="p-6!">
                    <div class="mb-5"><flux:heading size="lg">Impression cuisine</flux:heading><flux:text class="mt-1">Contrôlez la destination des bons et les catégories envoyées.</flux:text></div>
                    <div class="space-y-5">
                        <flux:select label="Mode d’impression" wire:model.live="kitchen_print_mode" icon="printer">
                            <flux:select.option value="browser">Navigateur — boîte de dialogue système</flux:select.option>
                            <flux:select.option value="ip">Réseau — imprimante IP locale</flux:select.option>
                        </flux:select>
                        @if($kitchen_print_mode === 'ip')
                            <flux:input label="Adresse IP de l’imprimante" wire:model.defer="kitchen_printer_ip" icon="signal" placeholder="192.168.1.100" />
                        @endif
                        <flux:separator />
                        <div x-data="{ newCategory: '' }">
                            <flux:label>Catégories envoyées en cuisine</flux:label>
                            <div class="mt-3 flex min-h-10 flex-wrap gap-2">
                                @forelse($kitchen_categories as $catIndex => $category)
                                    <flux:badge color="orange" icon="tag" wire:key="kitchen-category-{{ $catIndex }}">
                                        {{ $category }}
                                        <button type="button" wire:click="removeKitchenCategory({{ $catIndex }})" class="ml-1 rounded p-0.5 hover:bg-black/10" aria-label="Retirer {{ $category }}"><flux:icon.x-mark class="size-3" /></button>
                                    </flux:badge>
                                @empty
                                    <flux:text>Aucune catégorie sélectionnée.</flux:text>
                                @endforelse
                            </div>
                            <div class="mt-3 flex gap-2">
                                <flux:input x-model="newCategory" @keydown.enter.prevent="if (newCategory.trim()) { $wire.addKitchenCategory(newCategory); newCategory = '' }" placeholder="Ex. Boissons" class="flex-1" />
                                <flux:button type="button" icon="plus" @click="if (newCategory.trim()) { $wire.addKitchenCategory(newCategory); newCategory = '' }">Ajouter</flux:button>
                            </div>
                        </div>
                        <flux:callout icon="information-circle" text="Seuls les articles appartenant aux catégories sélectionnées sont envoyés à l’imprimante cuisine." />
                    </div>
                </flux:card>
            </div>

            <div class="flex justify-end"><flux:button type="submit" variant="primary" icon="check">Enregistrer le restaurant</flux:button></div>
        </form>
    @endif

    <flux:modal wire:model="isUserModalOpen" class="md:w-[34rem]">
        <form wire:submit="saveUser" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $userId ? 'Modifier l’utilisateur' : 'Nouvel utilisateur' }}</flux:heading>
                <flux:text class="mt-2">Définissez les informations de connexion et le niveau d’accès.</flux:text>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input label="Nom complet" wire:model="name" icon="user" autofocus />
                <flux:input label="Adresse e-mail" type="email" wire:model="email" icon="envelope" />
            </div>
            <flux:input label="{{ $userId ? 'Nouveau mot de passe — facultatif' : 'Mot de passe' }}" type="password" wire:model="password" icon="key" viewable :required="$userId === null" />
            <flux:select label="Rôle" wire:model="selectedRole" icon="shield-check">
                <flux:select.option value="">Sélectionner un rôle</flux:select.option>
                @foreach($availableRoles as $role)<flux:select.option value="{{ $role->name }}">{{ $role->name }}</flux:select.option>@endforeach
            </flux:select>
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="closeUserModal">Annuler</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Enregistrer l’utilisateur</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="isPaymentMethodModalOpen" class="md:w-[34rem]">
        <form wire:submit="savePaymentMethod" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $paymentMethodId ? 'Modifier le moyen de paiement' : 'Nouveau moyen de paiement' }}</flux:heading>
                <flux:text class="mt-2">Définissez sa disponibilité pour les encaissements et sa liaison comptable.</flux:text>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input label="Nom affiché" wire:model="pmName" icon="credit-card" placeholder="Mobile Money" autofocus />
                <flux:input label="Code interne" wire:model="pmCode" icon="code-bracket" placeholder="mobile_money" :readonly="in_array($pmCode, ['cash', 'points'])" description="Minuscules, sans espace." />
            </div>
            <flux:input label="Compte SYSCOHADA" wire:model="pmSyscohadaCode" icon="building-library" placeholder="5711" description="Compte associé aux écritures automatiques." />
            <flux:select label="Icône" wire:model="pmIcon">
                <flux:select.option value="">Choisir une icône</flux:select.option>
                <flux:select.option value="💵">💵 Espèces</flux:select.option><flux:select.option value="💳">💳 Carte bancaire</flux:select.option><flux:select.option value="📱">📱 Mobile Money</flux:select.option><flux:select.option value="🏦">🏦 Virement bancaire</flux:select.option><flux:select.option value="⭐">⭐ Points de fidélité</flux:select.option><flux:select.option value="🪙">🪙 Crypto ou jetons</flux:select.option><flux:select.option value="💶">💶 Euro</flux:select.option><flux:select.option value="💲">💲 Devise</flux:select.option><flux:select.option value="💸">💸 Transfert</flux:select.option>
            </flux:select>
            <flux:switch wire:model="pmIsActive" label="Moyen de paiement actif" description="Le rendre disponible immédiatement dans l’écran d’encaissement." />
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="closePaymentMethodModal">Annuler</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Enregistrer le moyen</flux:button>
            </div>
        </form>
    </flux:modal>

        </section>
    </div>
</div>
