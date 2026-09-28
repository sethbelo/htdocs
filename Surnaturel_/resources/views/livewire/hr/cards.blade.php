@php
    $company = app(\App\Services\Printing\PdfRenderer::class)->company();
    $validity = (int) \App\Models\Setting::get('hr.card_validity_years', 2);
    $dgName = \App\Models\User::role('directeur-general')->value('name');
    [$cw, $ch] = match ($kind) { 'badge' => [54, 85.6], 'business' => [85, 55], default => [85.6, 54] };
@endphp
<div class="space-y-6">
    <x-page-header title="Badges & cartes" eyebrow="Ressources humaines" subtitle="Badges d’accès, cartes de service recto / verso avec QR de vérification et cartes de visite, prêts à imprimer." />

    <div class="grid gap-6 xl:grid-cols-5">
        <div class="space-y-6 xl:col-span-3">
            <div class="panel space-y-4 p-5">
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($kinds as $key => $label)
                        <button type="button" wire:click="$set('kind', '{{ $key }}')" @class(['flex items-center gap-3 rounded-xl border p-3 text-left transition', 'border-brand-500 bg-brand-50 ring-2 ring-brand-500/20 dark:bg-white/8' => $kind === $key, 'border-line hover:border-line-strong' => $kind !== $key])>
                            <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-surface shadow-sm ring-1 ring-line"><flux:icon :icon="['service' => 'identification', 'badge' => 'user-circle', 'business' => 'credit-card'][$key]" variant="mini" class="text-brand-600 dark:text-cyan2-300" /></span>
                            <span class="text-sm font-semibold leading-tight">{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="flex flex-wrap items-end gap-4">
                    <div>
                        <span class="mb-1.5 block text-sm font-medium">Mise en page</span>
                        <div class="segmented">
                            <button type="button" wire:click="$set('layout', 'single')" aria-pressed="{{ $layout === 'single' ? 'true' : 'false' }}">Carte à l’unité</button>
                            <button type="button" wire:click="$set('layout', 'sheet')" aria-pressed="{{ $layout === 'sheet' ? 'true' : 'false' }}">Planche A4 ({{ $kind === 'badge' ? 9 : 10 }} / page)</button>
                        </div>
                    </div>
                    <flux:switch wire:model.live="withBack" label="Imprimer le verso" />
                    <flux:spacer />
                    @can('cards.print')
                        <flux:button variant="primary" icon="printer" wire:click="generate">Générer {{ count($selected) }} carte(s)</flux:button>
                    @endcan
                </div>
                <flux:error name="selected" />
            </div>

            <div class="panel">
                <div class="flex flex-wrap items-center gap-2 border-b border-line p-3">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nom, matricule, fonction…" class="max-w-xs" size="sm" />
                    <flux:select wire:model.live="departmentId" size="sm" class="max-w-48"><flux:select.option value="">Tous les services</flux:select.option>@foreach ($departments as $d)<flux:select.option :value="$d->id">{{ $d->name }}</flux:select.option>@endforeach</flux:select>
                    <flux:spacer />
                    <flux:button size="sm" variant="ghost" icon="check" wire:click="toggleAll">Tout sélectionner</flux:button>
                </div>
                <ul class="max-h-[560px] divide-y divide-line overflow-y-auto">
                    @forelse ($this->employees as $employee)
                        <li @class(['flex items-center gap-3 px-3 py-2.5', 'bg-brand-50/60 dark:bg-white/4' => $this->preview?->id === $employee->id]) wire:key="emp-{{ $employee->id }}">
                            <input type="checkbox" wire:model.live="selected" value="{{ $employee->id }}" class="size-4 rounded border-zinc-300 text-brand-600">
                            <button type="button" wire:click="$set('previewId', {{ $employee->id }})" class="flex min-w-0 flex-1 items-center gap-3 text-left">
                                @if ($photo = $employee->photoDataUri())
                                    <img src="{{ $photo }}" class="size-9 rounded-lg object-cover" alt="">
                                @else
                                    <span class="avatar size-9 rounded-lg bg-brand-100 text-[12px] text-brand-700 dark:bg-white/10 dark:text-white">{{ $employee->initials() }}</span>
                                @endif
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold">{{ $employee->fullName() }}</span>
                                    <span class="block truncate text-xs text-fg-subtle">{{ $employee->matricule }} · {{ $employee->job_title ?? '—' }} · {{ $employee->department?->name ?? '—' }}</span>
                                </span>
                            </button>
                            @if ($employee->card_expires_at)
                                <flux:badge size="sm" :color="$employee->card_expires_at->isPast() ? 'red' : 'emerald'">{{ $employee->card_expires_at->isPast() ? 'Expirée' : 'Valide' }} {{ $employee->card_expires_at->format('m/Y') }}</flux:badge>
                            @else
                                <flux:badge size="sm">Non émise</flux:badge>
                            @endif
                        </li>
                    @empty
                        <li><x-empty-state icon="identification" title="Aucun agent" /></li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="xl:col-span-2">
            <div class="panel space-y-4 p-5 xl:sticky xl:top-20">
                <flux:heading>Aperçu</flux:heading>
                @if ($employee = $this->preview)
                    <style>
                        .card-preview { position: relative; overflow: hidden; background: #fff; color: #134A63; font-family: Helvetica, Arial, sans-serif; border-radius: 3mm; box-shadow: 0 10px 30px -12px rgb(0 0 0 / .35); }
                        .card-preview .abs { position: absolute; }
                        .card-preview .upper { text-transform: uppercase; letter-spacing: .3mm; }
                        .card-preview .stripe { position: absolute; left: 0; right: 0; height: 1mm; display: flex; }
                        .card-preview .stripe span { display: block; height: 1mm; }
                        .card-preview .initials { background: #ececf3; color: #6b6b80; font-weight: bold; text-align: center; }
                        .card-preview .label { font-size: 4.6pt; color: #6b6b80; text-transform: uppercase; letter-spacing: .2mm; }
                        .card-preview .val { font-size: 6.4pt; font-weight: bold; }
                        .card-preview table { border-collapse: collapse; }
                        .card-preview img { max-width: none; display: inline-block; }
                    </style>
                    @php($verifyUrls = [$employee->id => app(\App\Services\Hr\EmployeeCards::class)->verificationUrl($employee)])
                    <div class="space-y-4">
                        @foreach ($withBack ? ['front' => 'Recto', 'back' => 'Verso'] : ['front' => 'Recto'] as $side => $sideLabel)
                            <div>
                                <div class="mb-1.5 text-xs font-semibold tracking-wide text-fg-subtle uppercase">{{ $sideLabel }}</div>
                                <div class="flex justify-center overflow-hidden rounded-xl bg-zinc-100 p-4 dark:bg-white/4">
                                    <div style="zoom: {{ $kind === 'badge' ? 1.2 : 1.02 }};">
                                        <div class="card-preview" style="width: {{ $cw }}mm; height: {{ $ch }}mm;">
                                            @include('pdf.cards.'.$kind.'-'.$side, ['employee' => $employee])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-fg-subtle">Format {{ $cw }} × {{ $ch }} mm. Le QR code du verso ouvre une page de vérification signée : toute modification du lien le rend invalide.</p>
                @else
                    <x-empty-state icon="identification" title="Aucun agent sélectionné" />
                @endif
            </div>
        </div>
    </div>
</div>
