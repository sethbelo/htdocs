<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $editing ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}</h1>
        <a href="{{ route('admin.categories.index') }}" class="pos-btn-ghost" wire:navigate><x-heroicon-o-arrow-left class="w-5 h-5"/> Retour</a>
    </div>

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text" class="form-input" placeholder="Ex: Boissons chaudes">
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea wire:model="description" class="form-input" rows="2"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Catégorie parente</label>
                    <select wire:model="parent_id" class="form-input">
                        <option value="">— Catégorie racine —</option>
                        @foreach($parentCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Couleur</label>
                        <div class="flex items-center gap-3">
                            <input wire:model="color" type="color" class="w-10 h-10 rounded-lg border border-slate-300 cursor-pointer p-0.5">
                            <input wire:model="color" type="text" class="form-input flex-1" placeholder="#3B82F6">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ordre d'affichage</label>
                        <input wire:model="sort_order" type="number" min="0" class="form-input">
                    </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input wire:model="is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">Catégorie active</span>
                </label>

                <!-- Color Preview -->
                <div class="p-3 rounded-lg border" style="border-color: {{ $color }}; background-color: {{ $color }}10;">
                    <p class="text-sm font-medium" style="color: {{ $color }}">
                        Aperçu : {{ $name ?: 'Nom de la catégorie' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.categories.index') }}" class="pos-btn-ghost" wire:navigate>Annuler</a>
            <button type="submit" class="pos-btn-primary">
                <x-heroicon-o-check class="w-5 h-5"/>
                {{ $editing ? 'Enregistrer' : 'Créer' }}
            </button>
        </div>
    </form>
</div>
