<div class="space-y-6">
    <x-page-header title="Gestion électronique des documents" subtitle="Stockage privé, versioning, liens signés temporaires, historique et archivage.">
        <x-slot:actions>@can('documents.create')<flux:modal.trigger name="upload"><flux:button size="sm" variant="primary" icon="arrow-up-tray">Déposer</flux:button></flux:modal.trigger>@endcan</x-slot:actions>
    </x-page-header>

    <div class="flex flex-wrap gap-2">
        <flux:button size="sm" :variant="$category === '' ? 'primary' : 'ghost'" wire:click="$set('category', '')">Tous</flux:button>
        @foreach ($categories as $v => $l)<flux:button size="sm" :variant="$category === $v ? 'primary' : 'ghost'" wire:click="$set('category', '{{ $v }}')">{{ $l }}</flux:button>@endforeach
    </div>

    <div class="panel p-4 space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Titre…" class="max-w-sm" />
            <flux:switch wire:model.live="archived" label="Archives" />
        </div>
        <flux:table :paginate="$documents">
            <flux:table.columns><flux:table.column>Document</flux:table.column><flux:table.column>Catégorie</flux:table.column><flux:table.column>Lié à</flux:table.column><flux:table.column>Version</flux:table.column><flux:table.column>Déposé par</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
            <flux:table.rows>
                @forelse ($documents as $document)
                    <flux:table.row :key="$document->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-2 font-medium">@if ($document->is_sensitive)<flux:icon.lock-closed class="size-4 text-amber-500" />@endif {{ $document->title }}</div>
                            <div class="text-xs text-zinc-500">{{ $document->latestVersion?->original_name }} · {{ Number::fileSize($document->latestVersion?->size ?? 0) }}</div>
                        </flux:table.cell>
                        <flux:table.cell><flux:badge size="sm">{{ $document->category->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $document->documentable?->number ?? ($document->documentable?->name ?? '—') }}</flux:table.cell>
                        <flux:table.cell>v{{ $document->current_version }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $document->uploader?->name }}<div class="text-xs text-zinc-500">{{ $document->updated_at->format('d/m/Y') }}</div></flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="arrow-down-tray" wire:click="download({{ $document->id }})" />
                                <flux:button size="xs" variant="ghost" icon="clock" wire:click="showHistory({{ $document->id }})" />
                                @can('update', $document)
                                    <flux:button size="xs" variant="ghost" icon="document-plus" wire:click="openVersion({{ $document->id }})" />
                                    <flux:button size="xs" variant="ghost" :icon="$document->archived_at ? 'archive-box-x-mark' : 'archive-box'" wire:click="toggleArchive({{ $document->id }})" />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="6"><x-empty-state icon="folder-open" title="Aucun document" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="upload" class="w-full max-w-lg">
        <form wire:submit="upload" class="space-y-4">
            <flux:heading size="lg">Déposer un document</flux:heading>
            <flux:input type="file" wire:model="file" label="Fichier" />
            <flux:input wire:model="form.title" label="Titre (facultatif)" />
            <flux:select wire:model="form.category" label="Catégorie">@foreach ($categories as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
            <flux:switch wire:model="form.is_sensitive" label="Document sensible (accès restreint)" />
            <flux:button type="submit" variant="primary" class="w-full">Déposer</flux:button>
        </form>
    </flux:modal>

    <flux:modal name="version" class="w-full max-w-md">
        <form wire:submit="addVersion" class="space-y-4">
            <flux:heading size="lg">Nouvelle version</flux:heading>
            <flux:input type="file" wire:model="newVersion" />
            <flux:button type="submit" variant="primary" class="w-full">Enregistrer</flux:button>
        </form>
    </flux:modal>

    <flux:modal name="history" class="w-full max-w-lg">
        @if ($history)
            <flux:heading size="lg">{{ $history->title }}</flux:heading>
            <ul class="mt-3 space-y-1 text-sm">@foreach ($history->versions as $v)<li>v{{ $v->version }} · {{ $v->original_name }} · {{ $v->uploader?->name }} · {{ $v->created_at->format('d/m/Y H:i') }}</li>@endforeach</ul>
            <flux:heading size="sm" class="mt-4">Journal</flux:heading>
            <ul class="mt-1 space-y-1 text-xs text-zinc-500">@foreach ($activity as $a)<li>{{ $a->created_at->format('d/m/Y H:i') }} · {{ $a->description }} · {{ $a->causer?->name }}</li>@endforeach</ul>
        @endif
    </flux:modal>
</div>
