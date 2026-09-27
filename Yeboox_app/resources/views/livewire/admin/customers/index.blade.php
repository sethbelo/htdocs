<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <flux:heading size="xl">Clients et fidélité</flux:heading>
            <flux:subheading class="mt-1">Comptes clients, points et cartes simples ou NFC.</flux:subheading>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('loyalty.view')<flux:button wire:click="openCardPanel" icon="radio">Lire une carte NFC</flux:button>@endcan
            @can('customers.export')<flux:button href="{{ route('admin.customers.export.pdf', ['search' => $search ?: null, 'loyalty' => $loyaltyFilter ?: null, 'status' => $statusFilter ?: null]) }}" icon="document-arrow-down">Exporter PDF</flux:button>@endcan
            @can('customers.create')<flux:button variant="primary" icon="plus" href="{{ route('admin.customers.create') }}" wire:navigate>Nouveau client</flux:button>@endcan
        </div>
    </div>

    @if (session('success'))<x-dismissible-alert variant="success" icon="check-circle">{{ session('success') }}</x-dismissible-alert>@endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="yeboox-card-tone p-5" style="--tone:#2563eb"><div class="flex items-center gap-4"><div class="yeboox-icon-tile"><flux:icon.users class="size-5" /></div><div><p class="text-2xl font-bold text-zinc-950 dark:text-white">{{ $customers->total() }}</p><p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Clients trouvés</p></div></div></div>
        <div class="yeboox-card-tone p-5" style="--tone:#8b5cf6"><div class="flex items-center gap-4"><div class="yeboox-icon-tile"><flux:icon.credit-card class="size-5" /></div><div><p class="text-2xl font-bold text-zinc-950 dark:text-white">{{ $cardCount }}</p><p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Cartes actives</p></div></div></div>
        <div class="yeboox-card-tone p-5" style="--tone:#f59e0b"><div class="flex items-center gap-4"><div class="yeboox-icon-tile"><flux:icon.star class="size-5" /></div><div><p class="text-2xl font-bold text-zinc-950 dark:text-white">{{ number_format($customers->sum('loyalty_points'), 0, ',', '.') }}</p><p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Points sur cette page</p></div></div></div>
        <div class="yeboox-card-tone p-5" style="--tone:#10b981"><div class="flex items-center gap-4"><div class="yeboox-icon-tile"><flux:icon.banknotes class="size-5" /></div><div><p class="text-xl font-bold text-zinc-950 dark:text-white">{{ number_format($customers->sum('total_purchases'), 0, ',', '.') }} FC</p><p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Achats sur cette page</p></div></div></div>
    </div>

    <flux:card>
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
            <flux:input class="min-w-0 flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nom, téléphone ou e-mail…" clearable />
            <div class="grid gap-3 sm:grid-cols-2 xl:flex">
                <flux:select wire:model.live="loyaltyFilter" class="xl:w-48"><flux:select.option value="">Tous les niveaux</flux:select.option><flux:select.option value="standard">Standard</flux:select.option><flux:select.option value="bronze">Bronze</flux:select.option><flux:select.option value="silver">Silver</flux:select.option><flux:select.option value="gold">Gold</flux:select.option><flux:select.option value="platinum">Platinum</flux:select.option></flux:select>
                <flux:select wire:model.live="statusFilter" class="xl:w-40"><flux:select.option value="">Tous les statuts</flux:select.option><flux:select.option value="active">Actifs</flux:select.option><flux:select.option value="inactive">Inactifs</flux:select.option></flux:select>
            </div>
            <div class="flex rounded-lg border border-zinc-200 p-1 dark:border-zinc-700" role="group" aria-label="Mode d’affichage">
                <flux:button type="button" wire:click="setViewMode('list')" :variant="$viewMode === 'list' ? 'primary' : 'subtle'" icon="list-bullet" square aria-label="Vue liste" />
                <flux:button type="button" wire:click="setViewMode('grid')" :variant="$viewMode === 'grid' ? 'primary' : 'subtle'" icon="squares-2x2" square aria-label="Vue grille" />
            </div>
        </div>
    </flux:card>

    @php
        $loyaltyIcons = ['standard' => 'shield-check', 'bronze' => 'fire', 'silver' => 'sparkles', 'gold' => 'trophy', 'platinum' => 'star'];
    @endphp

    @if ($viewMode === 'list')
        <flux:card class="p-0!">
            <flux:table>
                <flux:table.columns><flux:table.column>Client</flux:table.column><flux:table.column>Contact</flux:table.column><flux:table.column>Carte</flux:table.column><flux:table.column align="center">Fidélité</flux:table.column><flux:table.column align="end">Points</flux:table.column><flux:table.column align="end">Total achats</flux:table.column><flux:table.column align="end">Actions</flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @forelse ($customers as $customer)
                        <flux:table.row :key="$customer->id">
                            <flux:table.cell><div class="flex items-center gap-3"><flux:avatar size="sm" :initials="$customer->initials"/><div class="min-w-0"><flux:heading class="truncate">{{ $customer->full_name }}</flux:heading><flux:text size="sm">{{ $customer->company ?: ($customer->city ?: 'Client particulier') }}</flux:text></div></div></flux:table.cell>
                            <flux:table.cell><p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $customer->phone ?: 'Sans téléphone' }}</p>@if($customer->email)<p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $customer->email }}</p>@endif</flux:table.cell>
                            <flux:table.cell>@if($customer->loyaltyCard)<div class="flex items-center gap-2"><span class="flex size-8 items-center justify-center rounded-lg bg-violet-50 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300"><x-dynamic-component :component="$customer->loyaltyCard->isNfc() ? 'heroicon-o-radio' : 'heroicon-o-credit-card'" class="size-4"/></span><div><p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $customer->loyaltyCard->card_number }}</p><p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $customer->loyaltyCard->isNfc() ? 'Carte NFC' : 'Carte simple' }}</p></div></div>@else<span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Non émise</span>@endif</flux:table.cell>
                            <flux:table.cell align="center"><span class="loyalty-level-badge loyalty-level-badge--{{ $customer->loyalty_level }}"><x-dynamic-component :component="'heroicon-s-'.($loyaltyIcons[$customer->loyalty_level] ?? 'shield-check')" class="size-3.5"/>{{ ucfirst($customer->loyalty_level) }}</span></flux:table.cell>
                            <flux:table.cell align="end"><span class="font-bold text-zinc-900 dark:text-white">{{ number_format($customer->loyalty_points, 0, ',', '.') }}</span></flux:table.cell>
                            <flux:table.cell align="end"><span class="font-medium text-zinc-700 dark:text-zinc-200">{{ number_format($customer->total_purchases, 0, ',', '.') }} FC</span></flux:table.cell>
                            <flux:table.cell align="end"><flux:dropdown position="bottom" align="end"><flux:button variant="subtle" size="sm" icon="ellipsis-horizontal" square/><flux:menu>@can('loyalty.manage')<flux:menu.item icon="credit-card" wire:click="openCardPanel('{{ $customer->id }}')">{{ $customer->loyaltyCard ? 'Gérer la carte' : 'Créer une carte' }}</flux:menu.item><flux:menu.separator/>@endcan @can('customers.edit')<flux:menu.item icon="pencil-square" href="{{ route('admin.customers.edit', $customer) }}" wire:navigate>Modifier</flux:menu.item><flux:menu.item icon="power" wire:click="toggleStatus('{{ $customer->id }}')">{{ $customer->is_active ? 'Désactiver' : 'Activer' }}</flux:menu.item>@endcan @can('customers.delete')<flux:menu.separator/><flux:menu.item variant="danger" icon="trash" wire:click="requestDelete('{{ $customer->id }}')">Supprimer</flux:menu.item>@endcan</flux:menu></flux:dropdown></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="7"><div class="yeboox-empty py-12"><div><flux:icon.users class="mx-auto size-11 text-zinc-300"/><flux:heading class="mt-3">Aucun client trouvé</flux:heading><flux:text>Modifiez les filtres ou créez votre premier client.</flux:text></div></div></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @forelse ($customers as $customer)
                <flux:card wire:key="customer-grid-{{ $customer->id }}" class="loyalty-customer-card loyalty-customer-card--{{ $customer->loyalty_level }} relative overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-1 bg-[var(--level-accent)]"></div>
                    <div class="flex items-start justify-between gap-3"><div class="flex min-w-0 items-center gap-3"><flux:avatar :initials="$customer->initials"/><div class="min-w-0"><flux:heading class="truncate">{{ $customer->full_name }}</flux:heading><flux:text size="sm" class="truncate">{{ $customer->phone ?: 'Sans téléphone' }}</flux:text></div></div><flux:dropdown position="bottom" align="end"><flux:button variant="subtle" size="sm" icon="ellipsis-horizontal" square/><flux:menu>@can('loyalty.manage')<flux:menu.item icon="credit-card" wire:click="openCardPanel('{{ $customer->id }}')">{{ $customer->loyaltyCard ? 'Gérer la carte' : 'Créer une carte' }}</flux:menu.item>@endcan @can('customers.edit')<flux:menu.item icon="pencil-square" href="{{ route('admin.customers.edit', $customer) }}" wire:navigate>Modifier</flux:menu.item>@endcan @can('customers.delete')<flux:menu.separator/><flux:menu.item variant="danger" icon="trash" wire:click="requestDelete('{{ $customer->id }}')">Supprimer</flux:menu.item>@endcan</flux:menu></flux:dropdown></div>
                    <div class="loyalty-showcase loyalty-showcase--{{ $customer->loyalty_level }} my-5 p-4"><div class="flex items-center justify-between"><span class="loyalty-showcase__icon"><x-dynamic-component :component="'heroicon-o-'.($loyaltyIcons[$customer->loyalty_level] ?? 'shield-check')" class="size-5"/></span><div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest" style="color:var(--card-accent)">{{ ucfirst($customer->loyalty_level) }} @if($customer->loyaltyCard?->isNfc())<flux:icon.radio class="size-4"/>@endif</div></div><p class="mt-5 font-mono text-sm font-semibold tracking-wider">{{ $customer->loyaltyCard?->card_number ?? 'CARTE NON ÉMISE' }}</p><div class="mt-3 flex items-end justify-between gap-3"><span class="text-[.68rem] font-medium uppercase tracking-widest opacity-75">Yeboox fidélité</span><span class="text-xl font-bold">{{ number_format($customer->loyalty_points, 0, ',', '.') }} <small class="text-xs font-semibold opacity-75">pts</small></span></div></div>
                    <div class="flex items-center justify-between"><span class="loyalty-level-badge loyalty-level-badge--{{ $customer->loyalty_level }}"><x-dynamic-component :component="'heroicon-s-'.($loyaltyIcons[$customer->loyalty_level] ?? 'shield-check')" class="size-3.5"/>{{ ucfirst($customer->loyalty_level) }}</span><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ number_format($customer->total_purchases, 0, ',', '.') }} FC</span></div>
                </flux:card>
            @empty
                <div class="yeboox-empty col-span-full py-16"><div><flux:icon.users class="mx-auto size-11 text-zinc-300"/><flux:heading class="mt-3">Aucun client trouvé</flux:heading></div></div>
            @endforelse
        </div>
    @endif

    @if ($customers->hasPages())<flux:card class="py-3"><flux:pagination :paginator="$customers" scroll-to /></flux:card>@endif

    <flux:modal wire:model.self="showCardPanel" variant="flyout" position="right" class="w-full md:w-[34rem]" x-data="{ nfcBusy: false }">
        <div class="space-y-6">
            <div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#8b5cf6"><flux:icon.credit-card class="size-5"/></div><div><flux:heading size="lg">{{ $cardPanelMode === 'read' ? 'Lire une carte NFC' : 'Carte de fidélité' }}</flux:heading><flux:text>{{ $cardPanelMode === 'read' ? 'Identification par lecteur ACS ou Web NFC.' : $card_customer_name }}</flux:text></div></div>

            @if ($cardPanelMode === 'read')
                <div class="flex min-h-52 flex-col items-center justify-center rounded-2xl border border-dashed border-violet-300 bg-violet-50/60 p-6 text-center dark:border-violet-800 dark:bg-violet-950/20"><span class="mb-4 flex size-16 items-center justify-center rounded-full bg-violet-100 text-violet-600 dark:bg-violet-900/50 dark:text-violet-300"><flux:icon.radio class="size-8" x-bind:class="nfcBusy && 'animate-pulse'"/></span><flux:heading>{{ $detected_customer_name ?: 'En attente d’une carte' }}</flux:heading><flux:text class="mt-2">{{ $nfc_message }}</flux:text></div>
                <flux:button class="w-full" variant="primary" icon="radio" x-bind:disabled="nfcBusy" x-on:click="nfcBusy = true; try { const result = await window.YebooxNfc.read(); await $wire.nfcCardRead(result.uid || '', result.token || ''); } catch (error) { await $wire.nfcFailed(error.message); } finally { nfcBusy = false; }"><span x-text="nfcBusy ? 'Lecture en cours…' : 'Lire la carte'"></span></flux:button>
            @else
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" wire:click="$set('card_type', 'simple')" class="rounded-2xl border p-4 text-left text-zinc-900 dark:text-zinc-100 {{ $card_type === 'simple' ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-500/15 dark:bg-primary-950/30' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}"><flux:icon.credit-card class="mb-3 size-6"/><span class="block font-semibold">Carte simple</span><span class="text-xs text-zinc-600 dark:text-zinc-400">Numéro fidélité sans puce</span></button>
                    <button type="button" wire:click="$set('card_type', 'nfc')" class="rounded-2xl border p-4 text-left text-zinc-900 dark:text-zinc-100 {{ $card_type === 'nfc' ? 'border-violet-500 bg-violet-50 ring-2 ring-violet-500/15 dark:bg-violet-950/30' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}"><flux:icon.radio class="mb-3 size-6"/><span class="block font-semibold">Carte NFC</span><span class="text-xs text-zinc-600 dark:text-zinc-400">Lecture et écriture ACS</span></button>
                </div>
                <flux:input wire:model="card_number" label="Numéro de carte" required />
                <flux:select wire:model="card_status" label="Statut"><flux:select.option value="active">Active</flux:select.option><flux:select.option value="blocked">Bloquée</flux:select.option></flux:select>
                @if ($card_type === 'nfc')
                    <div class="rounded-2xl border border-violet-200 bg-violet-50/60 p-5 dark:border-violet-900 dark:bg-violet-950/20"><div class="flex items-start gap-3"><flux:icon.cpu-chip class="mt-0.5 size-5 shrink-0 text-violet-600"/><div><p class="font-medium">Lecteur ACS / PC-SC</p><p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Posez la carte sur le lecteur puis lancez l’écriture. Web NFC sera utilisé automatiquement si le connecteur ACS local n’est pas disponible.</p></div></div>@if($card_nfc_uid)<div class="mt-4 rounded-lg bg-white/80 p-3 font-mono text-xs dark:bg-zinc-900/70">UID : {{ $card_nfc_uid }}<br>Lecteur : {{ $card_reader_type ?: 'ACS' }}</div>@endif</div>
                    <flux:button class="w-full" icon="radio" x-bind:disabled="nfcBusy" x-on:click="nfcBusy = true; try { const result = await window.YebooxNfc.write({ cardNumber: $wire.card_number, token: $wire.card_token }); await $wire.nfcCardWritten(result.uid, result.readerType); } catch (error) { await $wire.nfcFailed(error.message); } finally { nfcBusy = false; }"><span x-text="nfcBusy ? 'Écriture en cours…' : '{{ $card_nfc_uid ? 'Réécrire la carte NFC' : 'Détecter et écrire la carte' }}'"></span></flux:button>
                @endif
                @if ($nfc_message)<div class="rounded-xl bg-zinc-100 p-3 text-sm text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ $nfc_message }}</div>@endif
                @error('nfc')<x-dismissible-alert variant="danger">{{ $message }}</x-dismissible-alert>@enderror
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button type="button" wire:click="$set('showCardPanel', false)">Annuler</flux:button><flux:button type="button" wire:click="saveLoyaltyCard" variant="primary" icon="check">Enregistrer la carte</flux:button></div>
            @endif
        </div>
    </flux:modal>

    <flux:modal wire:model.self="showDeleteModal" class="md:w-[28rem]"><div class="space-y-6"><div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#ef4444"><flux:icon.trash class="size-5"/></div><div><flux:heading size="lg">Supprimer ce client ?</flux:heading><flux:text>Le compte « {{ $deleteName }} » et sa carte seront archivés.</flux:text></div></div><div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button wire:click="$set('showDeleteModal', false)">Annuler</flux:button><flux:button wire:click="confirmDelete" variant="danger" icon="trash">Supprimer</flux:button></div></div></flux:modal>
</div>
