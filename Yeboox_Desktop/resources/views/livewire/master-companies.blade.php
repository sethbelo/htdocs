<div class="space-y-6">
    <x-page-hero
        title="Plateforme SaaS"
        eyebrow="Administration master"
        description="Créez les entreprises, contrôlez leurs points de vente et suivez les abonnements depuis une console isolée."
        icon="building-office-2"
        badge-icon="shield-check"
        illustration="settings"
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="$set('showCreate', true)">Nouvelle entreprise</flux:button>
        </x-slot:actions>
    </x-page-hero>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" heading="Provisionnement terminé" text="{{ session('success') }}" />
    @endif

    @php
        $platformStats = [
            ['label' => 'Entreprises', 'value' => $companies->total(), 'icon' => 'building-office-2', 'tone' => 'violet'],
            ['label' => 'Entreprises actives', 'value' => \App\Models\Company::where('status', 'active')->count(), 'icon' => 'check-badge', 'tone' => 'emerald'],
            ['label' => 'Points de vente', 'value' => \App\Models\PointOfSale::count(), 'icon' => 'building-storefront', 'tone' => 'sky'],
            ['label' => 'Abonnements actifs', 'value' => \App\Models\PosSubscription::whereIn('status', ['active', 'trial'])->where('ends_at', '>=', now())->count(), 'icon' => 'credit-card', 'tone' => 'amber'],
        ];
    @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($platformStats as $stat)
            <flux:card class="flex items-center gap-4">
                <span @class([
                    'grid size-11 place-items-center rounded-2xl',
                    'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300' => $stat['tone'] === 'violet',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' => $stat['tone'] === 'emerald',
                    'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300' => $stat['tone'] === 'sky',
                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' => $stat['tone'] === 'amber',
                ])><flux:icon :name="$stat['icon']" class="size-5" /></span>
                <div><div class="text-2xl font-bold">{{ $stat['value'] }}</div><flux:text>{{ $stat['label'] }}</flux:text></div>
            </flux:card>
        @endforeach
    </div>

    <flux:card class="overflow-hidden p-0!">
        <div class="flex flex-col gap-2 border-b border-zinc-200 p-5 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="lg">Portefeuille d’entreprises</flux:heading>
                <flux:text>Chaque ligne agrège uniquement ses établissements, PDV et abonnements.</flux:text>
            </div>
            <flux:badge color="violet" icon="lock-closed">Isolation active</flux:badge>
        </div>

        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Entreprise</flux:table.column>
                    <flux:table.column>Structure</flux:table.column>
                    <flux:table.column>Devise</flux:table.column>
                    <flux:table.column>Abonnement</flux:table.column>
                    <flux:table.column>État</flux:table.column>
                    <flux:table.column class="text-end">Action</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($companies as $company)
                        @php($subscription = $company->pointsOfSale->first()?->activeSubscription())
                        <flux:table.row wire:key="company-{{ $company->id }}">
                            <flux:table.cell>
                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $company->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $company->email ?: $company->slug }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-sm">{{ $company->establishments_count }} établissement(s)</div>
                                <div class="text-xs text-zinc-500">{{ $company->points_of_sale_count }} PDV · {{ $company->users_count }} utilisateur(s)</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $company->base_currency }} / {{ $company->secondary_currency }}</flux:table.cell>
                            <flux:table.cell>
                                @if($subscription)
                                    <div class="font-medium">{{ ucfirst($subscription->plan_code) }}</div>
                                    <div class="text-xs text-zinc-500">Jusqu’au {{ $subscription->ends_at->format('d/m/Y') }}</div>
                                @else
                                    <flux:badge color="rose">Aucun</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$company->status === 'active' ? 'emerald' : 'rose'">
                                    {{ $company->status === 'active' ? 'Active' : 'Suspendue' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-end">
                                <flux:button size="sm" :variant="$company->status === 'active' ? 'danger' : 'primary'" wire:click="toggleStatus({{ $company->id }})">
                                    {{ $company->status === 'active' ? 'Suspendre' : 'Réactiver' }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="6" class="py-12 text-center">Aucune entreprise.</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-white/10">{{ $companies->links() }}</div>
    </flux:card>

    <flux:modal wire:model="showCreate" variant="flyout" position="right" class="w-full md:w-[46rem]" :dismissible="false">
        <form wire:submit="createCompany" class="space-y-6">
            <div class="flex items-start gap-4 border-b border-zinc-200 pb-5 pe-10 dark:border-white/10">
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-violet-100 text-violet-700 ring-1 ring-violet-200 dark:bg-violet-950/60 dark:text-violet-300 dark:ring-violet-800">
                    <flux:icon.building-office-2 class="size-6" />
                </span>
                <div class="min-w-0">
                    <flux:heading size="xl">Provisionner une entreprise</flux:heading>
                    <flux:text class="mt-1">La création génère automatiquement l’établissement, le premier PDV, l’abonnement et les données de démarrage.</flux:text>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="name" label="Nom commercial" required />
                <flux:input wire:model="legalName" label="Raison sociale" />
                <flux:input wire:model="email" type="email" label="E-mail société" />
                <flux:input wire:model="phone" label="Téléphone" />
                <flux:select wire:model.live="baseCurrency" label="Devise principale">
                    @foreach(\App\Support\DisplayCurrency::currencies() as $code => $meta)<option value="{{ $code }}">{{ $code }} — {{ $meta['label'] }}</option>@endforeach
                </flux:select>
                <flux:select wire:model.live="secondaryCurrency" label="Devise secondaire">
                    @foreach(\App\Support\DisplayCurrency::currencies() as $code => $meta)@continue($code === $baseCurrency)<option value="{{ $code }}">{{ $code }} — {{ $meta['label'] }}</option>@endforeach
                </flux:select>
                <flux:input wire:model="exchangeRate" inputmode="decimal" label="1 {{ $baseCurrency }} = ? {{ $secondaryCurrency }}" required />
            </div>

            <div class="rounded-2xl bg-violet-50 p-4 dark:bg-violet-950/30">
                <flux:heading>Administrateur de la société</flux:heading>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="administratorName" label="Nom complet" required />
                    <flux:input wire:model="administratorEmail" type="email" label="E-mail de connexion" required />
                    <flux:input wire:model="administratorPassword" type="password" label="Mot de passe" required />
                    <flux:input wire:model="administratorPasswordConfirmation" type="password" label="Confirmation" required />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="planCode" label="Formule">
                    <option value="starter">Starter</option>
                    <option value="professional">Professional</option>
                    <option value="enterprise">Enterprise</option>
                </flux:select>
                <flux:input wire:model="subscriptionDays" inputmode="numeric" label="Durée initiale (jours)" required />
            </div>

            <div class="sticky bottom-0 -mx-6 flex justify-end gap-3 border-t border-zinc-200 bg-white/95 px-6 pb-1 pt-5 backdrop-blur dark:border-white/10 dark:bg-zinc-800/95">
                <flux:button type="button" wire:click="$set('showCreate', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary" icon="building-office-2">Créer et provisionner</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
