<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $editing ? 'Modifier le fournisseur' : 'Nouveau fournisseur' }}</h1>
        </div>
        <a href="{{ route('admin.suppliers.index') }}" class="pos-btn-ghost" wire:navigate>
            <x-heroicon-o-arrow-left class="w-5 h-5"/> Retour
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Informations générales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text" class="form-input" placeholder="Nom du fournisseur">
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Personne de contact</label>
                    <input wire:model="contact_name" type="text" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">E-mail</label>
                    <input wire:model="email" type="email" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Téléphone</label>
                    <input wire:model="phone" type="tel" class="form-input" placeholder="+243 ...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tél. secondaire</label>
                    <input wire:model="phone_secondary" type="tel" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ville</label>
                    <input wire:model="city" type="text" class="form-input" placeholder="Kinshasa">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Délai de paiement (jours)</label>
                    <input wire:model="payment_terms_days" type="number" min="0" class="form-input" placeholder="0 = comptant">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Adresse</label>
                    <textarea wire:model="address" class="form-input" rows="2"></textarea>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Informations fiscales et bancaires</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">NIF</label>
                    <input wire:model="nif" type="text" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">RCCM</label>
                    <input wire:model="rccm" type="text" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Banque</label>
                    <input wire:model="bank_name" type="text" class="form-input" placeholder="Ex: Rawbank, Equity BCDC...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">N° de compte</label>
                    <input wire:model="bank_account" type="text" class="form-input">
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes</label>
            <textarea wire:model="notes" class="form-input" rows="3"></textarea>
            <label class="flex items-center gap-2 mt-3 cursor-pointer">
                <input wire:model="is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                <span class="text-sm text-slate-700 dark:text-slate-300">Fournisseur actif</span>
            </label>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.suppliers.index') }}" class="pos-btn-ghost" wire:navigate>Annuler</a>
            <button type="submit" class="pos-btn-primary">
                <x-heroicon-o-check class="w-5 h-5"/>
                {{ $editing ? 'Enregistrer' : 'Créer le fournisseur' }}
            </button>
        </div>
    </form>
</div>
