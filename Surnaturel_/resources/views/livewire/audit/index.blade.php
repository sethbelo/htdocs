<div class="space-y-6">
    <x-page-header title="Audit & traçabilité" subtitle="Historique des actions sensibles et journal des connexions." />

    <div class="flex flex-wrap items-center gap-2">
        <flux:button size="sm" :variant="$tab === 'activity' ? 'primary' : 'ghost'" wire:click="$set('tab', 'activity')">Actions</flux:button>
        @can('audit.view_logins')<flux:button size="sm" :variant="$tab === 'logins' ? 'primary' : 'ghost'" wire:click="$set('tab', 'logins')">Connexions</flux:button>@endcan
        <flux:spacer />
        @if ($tab === 'activity')
            <flux:select wire:model.live="logName" size="sm" class="max-w-48"><flux:select.option value="">Tous modules</flux:select.option>@foreach ($logNames as $n)<flux:select.option :value="$n">{{ $n }}</flux:select.option>@endforeach</flux:select>
        @endif
        <flux:select wire:model.live="userId" size="sm" class="max-w-48"><flux:select.option value="">Tous utilisateurs</flux:select.option>@foreach ($users as $u)<flux:select.option :value="$u->id">{{ $u->name }}</flux:select.option>@endforeach</flux:select>
        <flux:input type="date" wire:model.live="from" size="sm" />
    </div>

    <div class="panel p-4">
        @if ($tab === 'activity' && $activities)
            <flux:table :paginate="$activities">
                <flux:table.columns><flux:table.column>Date</flux:table.column><flux:table.column>Utilisateur</flux:table.column><flux:table.column>Module</flux:table.column><flux:table.column>Action</flux:table.column><flux:table.column>Objet</flux:table.column><flux:table.column>Modifications</flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @foreach ($activities as $activity)
                        <flux:table.row :key="$activity->id">
                            <flux:table.cell class="whitespace-nowrap">{{ $activity->created_at->format('d/m/Y H:i:s') }}</flux:table.cell>
                            <flux:table.cell>{{ $activity->causer?->name ?? 'Système' }}</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm">{{ $activity->log_name }}</flux:badge></flux:table.cell>
                            <flux:table.cell>{{ ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression'][$activity->description] ?? $activity->description }}</flux:table.cell>
                            <flux:table.cell class="text-sm">{{ $activity->subject?->number ?? $activity->subject?->name ?? ($activity->subject_type.' #'.$activity->subject_id) }}</flux:table.cell>
                            <flux:table.cell class="max-w-md whitespace-normal! text-xs text-zinc-500">
                                @foreach ((array) ($activity->attribute_changes['attributes'] ?? $activity->properties['attributes'] ?? []) as $key => $value)
                                    <span class="me-2"><strong>{{ $key }}</strong> : {{ is_scalar($value) ? Str::limit((string) $value, 40) : json_encode($value) }}</span>
                                @endforeach
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @elseif ($logins)
            <flux:table :paginate="$logins">
                <flux:table.columns><flux:table.column>Connexion</flux:table.column><flux:table.column>Identifiant</flux:table.column><flux:table.column>Résultat</flux:table.column><flux:table.column>IP</flux:table.column><flux:table.column>Navigateur</flux:table.column><flux:table.column>Déconnexion</flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @foreach ($logins as $login)
                        <flux:table.row :key="$login->id">
                            <flux:table.cell>{{ $login->logged_in_at->format('d/m/Y H:i:s') }}</flux:table.cell>
                            <flux:table.cell>{{ $login->user?->name ?? $login->identifier }}</flux:table.cell>
                            <flux:table.cell>@if ($login->succeeded)<flux:badge size="sm" color="emerald">Succès</flux:badge>@else<flux:badge size="sm" color="red">Échec</flux:badge>@endif</flux:table.cell>
                            <flux:table.cell>{{ $login->ip_address }}</flux:table.cell>
                            <flux:table.cell class="max-w-64 truncate text-xs text-zinc-500">{{ $login->user_agent }}</flux:table.cell>
                            <flux:table.cell>{{ $login->logged_out_at?->format('d/m H:i') }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</div>
