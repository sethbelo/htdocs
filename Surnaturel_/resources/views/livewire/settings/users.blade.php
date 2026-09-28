<div class="space-y-6">
    <x-page-header title="Utilisateurs" subtitle="Rattachement aux sièges, rôles, PIN caisse, badge, expiration de session.">
        <x-slot:actions>@can('users.create')<flux:button size="sm" variant="primary" icon="user-plus" wire:click="create">Nouvel utilisateur</flux:button>@endcan</x-slot:actions>
    </x-page-header>
    @include('livewire.settings.nav')

    <div class="panel p-4 space-y-4">
        <div class="flex flex-wrap gap-3">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nom, identifiant, e-mail…" class="max-w-sm" />
            <flux:select wire:model.live="role" class="max-w-xs"><flux:select.option value="">Tous les rôles</flux:select.option>@foreach ($allRoles as $r)<flux:select.option :value="$r">{{ config("erp.roles.$r.label", $r) }}</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model.live="state" class="max-w-40"><flux:select.option value="">Tous les comptes</flux:select.option><flux:select.option value="active">Actifs</flux:select.option><flux:select.option value="inactive">Désactivés</flux:select.option></flux:select>
            <flux:select wire:model.live="siteFilter" class="max-w-52"><flux:select.option value="">Tous les sièges</flux:select.option>@foreach ($sites as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach</flux:select>
        </div>
        <flux:table :paginate="$users">
            <flux:table.columns><flux:table.column>Utilisateur</flux:table.column><flux:table.column>Rôles</flux:table.column><flux:table.column>Sièges</flux:table.column><flux:table.column>Dernière connexion</flux:table.column><flux:table.column>Connexion & état</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
            <flux:table.rows>
                @foreach ($users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell><div class="font-medium">{{ $user->name }}</div><div class="text-xs text-zinc-500">{{ $user->username }} · {{ $user->email }}</div></flux:table.cell>
                        <flux:table.cell class="max-w-56 whitespace-normal!">@foreach ($user->roles as $r)<flux:badge size="sm" class="me-1">{{ config("erp.roles.{$r->name}.label", $r->name) }}</flux:badge>@endforeach</flux:table.cell>
                        <flux:table.cell class="max-w-56 truncate text-sm">{{ $user->sites->pluck('code')->implode(', ') }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $user->last_login_at?->diffForHumans() ?? 'Jamais' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($user->is_active)<flux:badge size="sm" color="emerald">Actif</flux:badge>@else<flux:badge size="sm" color="red">Désactivé</flux:badge>@endif
                            @if ($user->must_change_password)<flux:badge size="sm" color="amber">MDP à changer</flux:badge>@endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @can('users.update')
                                <flux:dropdown align="end">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil" wire:click="edit({{ $user->id }})">Modifier</flux:menu.item>
                                        <flux:menu.item icon="key" wire:click="resetPassword({{ $user->id }})">Réinitialiser le mot de passe</flux:menu.item>
                                        <flux:menu.item icon="finger-print" wire:click="openPin({{ $user->id }})">Définir le PIN</flux:menu.item>
                                        @if ($user->isNot(auth()->user()))<flux:menu.item icon="power" wire:click="toggleActive({{ $user->id }})">{{ $user->is_active ? 'Désactiver' : 'Réactiver' }}</flux:menu.item>@endif
                                    </flux:menu>
                                </flux:dropdown>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="user" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Modifier l’utilisateur' : 'Nouvel utilisateur' }}</flux:heading>
            <div class="grid gap-3 md:grid-cols-2">
                <flux:input wire:model="form.name" label="Nom complet" />
                <flux:input wire:model="form.username" label="Nom d’utilisateur" />
                <flux:input wire:model="form.email" type="email" label="E-mail" />
                <flux:input wire:model="form.phone" label="Téléphone" />
                <flux:input wire:model="form.badge_code" label="Badge employé (RFID / NFC / QR)" description="Pointage et identification (la connexion se fait toujours par identifiant et mot de passe)." />
                <flux:input wire:model="form.session_timeout_minutes" type="number" label="Expiration de session (min, facultatif)" />
                <flux:select wire:model="form.default_site_id" label="Siège par défaut">@foreach ($sites as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach</flux:select>
                <flux:switch wire:model="form.is_active" label="Compte actif" />
            </div>
            <flux:checkbox.group wire:model="siteIds" label="Sièges accessibles">
                <div class="grid gap-1 md:grid-cols-3">@foreach ($sites as $s)<flux:checkbox :value="$s->id" :label="$s->name" />@endforeach</div>
            </flux:checkbox.group>
            <flux:checkbox.group wire:model="roles" label="Rôles">
                <div class="grid gap-1 md:grid-cols-3">@foreach ($allRoles as $r)<flux:checkbox :value="$r" :label="config('erp.roles.'.$r.'.label', $r)" />@endforeach</div>
            </flux:checkbox.group>
            <flux:error name="roles" />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Enregistrer</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="pin" class="w-full max-w-sm">
        <form wire:submit="savePin" class="space-y-4">
            <flux:heading size="lg">PIN de connexion / caisse</flux:heading>
            <flux:text>Utilisé pour les connexions « Caisse / Atelier » et « Badge », l’ouverture de caisse et les validations de responsable.</flux:text>
            <flux:input wire:model="newPin" inputmode="numeric" label="Nouveau PIN (4 à 6 chiffres)" copyable />
            <flux:button type="submit" variant="primary" class="w-full">Enregistrer le PIN</flux:button>
        </form>
    </flux:modal>

    <flux:modal name="password" class="w-full max-w-md" :dismissible="false">
        <div class="space-y-4">
            <flux:heading size="lg">Mot de passe provisoire</flux:heading>
            <flux:text>Communiquez-le de façon sécurisée : il ne sera plus affiché. L’utilisateur devra le changer à la première connexion.</flux:text>
            <flux:input :value="$generatedPassword" readonly copyable />
            <flux:button variant="primary" class="w-full" wire:click="closePassword">J’ai noté le mot de passe</flux:button>
        </div>
    </flux:modal>
</div>
