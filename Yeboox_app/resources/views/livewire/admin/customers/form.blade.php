<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ $editing ? 'Modifier le client' : 'Nouveau client' }}
            </h1>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="pos-btn-ghost" wire:navigate>
            <x-heroicon-o-arrow-left class="w-5 h-5"/> Retour
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Identité -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Identité</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Prénom <span class="text-red-500">*</span></label>
                    <input wire:model="first_name" type="text" class="form-input" placeholder="Prénom">
                    @error('first_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom <span class="text-red-500">*</span></label>
                    <input wire:model="last_name" type="text" class="form-input" placeholder="Nom de famille">
                    @error('last_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Entreprise</label>
                    <input wire:model="company" type="text" class="form-input" placeholder="Société (optionnel)">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Genre</label>
                    <select wire:model="gender" class="form-input">
                        <option value="">—</option>
                        <option value="male">Homme</option>
                        <option value="female">Femme</option>
                        <option value="other">Autre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date de naissance</label>
                    <input wire:model="birth_date" type="date" class="form-input">
                </div>
            </div>
        </div>

        <!-- Contact -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Contact</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Téléphone</label>
                    <input wire:model="phone" type="tel" class="form-input" placeholder="+243 ...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tél. secondaire</label>
                    <input wire:model="phone_secondary" type="tel" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">E-mail</label>
                    <input wire:model="email" type="email" class="form-input" placeholder="email@exemple.cd">
                    @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ville</label>
                    <input wire:model="city" type="text" class="form-input" placeholder="Kinshasa">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Adresse</label>
                    <textarea wire:model="address" class="form-input" rows="2" placeholder="Adresse complète"></textarea>
                </div>
            </div>
        </div>

        <!-- Fiscal (B2B) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Informations fiscales (B2B)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">NIF</label>
                    <input wire:model="nif" type="text" class="form-input" placeholder="Numéro d'Identification Fiscale">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">RCCM</label>
                    <input wire:model="rccm" type="text" class="form-input" placeholder="Registre de Commerce">
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes internes</label>
            <textarea wire:model="notes" class="form-input" rows="3" placeholder="Notes internes sur ce client..."></textarea>
            <label class="flex items-center gap-2 mt-3 cursor-pointer">
                <input wire:model="is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                <span class="text-sm text-slate-700 dark:text-slate-300">Client actif</span>
            </label>
        </div>

        @if($editing && $customer)
            <!-- Loyalty Summary -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Programme de fidélité</h3>
                @can('loyalty.adjust_points')
                    <div class="mb-5 max-w-xs">
                        <flux:input wire:model="loyalty_points" type="number" min="0" label="Solde de points" description="Ajustement manuel autorisé pour ce compte client." />
                    </div>
                @endcan
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div>
                        <p class="text-2xl font-bold text-primary-600">{{ number_format($loyalty_points) }}</p>
                        <p class="text-xs text-slate-500">Points</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-amber-600">{{ ucfirst($customer->loyalty_level) }}</p>
                        <p class="text-xs text-slate-500">Niveau</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-emerald-600">{{ number_format($customer->total_purchases, 0, ',', '.') }}</p>
                        <p class="text-xs text-slate-500">Total achats (FC)</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-purple-600">{{ $customer->total_orders }}</p>
                        <p class="text-xs text-slate-500">Commandes</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.customers.index') }}" class="pos-btn-ghost" wire:navigate>Annuler</a>
            <button type="submit" class="pos-btn-primary">
                <x-heroicon-o-check class="w-5 h-5"/>
                {{ $editing ? 'Enregistrer' : 'Créer le client' }}
            </button>
        </div>
    </form>
</div>
