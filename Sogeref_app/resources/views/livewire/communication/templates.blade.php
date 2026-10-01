<div>
    <x-ui.page-header title="Modèles de messages" eyebrow="Communication" subtitle="Textes prêts à l’emploi pour WhatsApp, SMS et e-mail. Les variables sont remplacées par les informations de chaque contact.">
        <x-slot:actions>
            @if ($canSend)<x-ui.button variant="accent" icon="plus" wire:click="create">Nouveau modèle</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap gap-2" role="group" aria-label="Filtrer par canal">
        @foreach (['all' => ['Tous', 'squares-2x2', $counts->sum()]] + collect($channels)->map(fn ($c, $k) => [$c['label'], $c['icon'], $counts[$k] ?? 0])->all() as $key => [$label, $icon, $count])
            <button type="button" wire:click="$set('channel', '{{ $key }}')" @class(['btn btn-sm', 'btn-primary' => $channel === $key, 'btn-secondary' => $channel !== $key])>
                <x-ui.icon :name="$icon" class="size-4" />{{ $label }} <span class="opacity-70">{{ $count }}</span>
            </button>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($templates as $template)
            @php $meta = $channels[$template->channel] ?? $channels['whatsapp']; @endphp
            <article wire:key="tpl-{{ $template->id }}" class="card flex flex-col p-4">
                <div class="mb-2 flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <x-ui.badge :tone="$meta['tone']" :icon="$meta['icon']">{{ $meta['label'] }}</x-ui.badge>
                        <h3 class="mt-2 truncate font-display text-[15px] font-semibold">{{ $template->name }}</h3>
                        @if ($template->subject)<p class="truncate text-[12.5px] text-fg-muted">Objet : {{ $template->subject }}</p>@endif
                    </div>
                    @if ($canSend)
                        <div class="flex shrink-0 gap-0.5">
                            <button type="button" class="btn btn-ghost btn-sm btn-icon" wire:click="edit({{ $template->id }})" title="Modifier" aria-label="Modifier"><x-ui.icon name="pencil-square" class="size-4" /></button>
                            <button type="button" class="btn btn-ghost btn-sm btn-icon" wire:click="duplicate({{ $template->id }})" title="Dupliquer" aria-label="Dupliquer"><x-ui.icon name="document-duplicate" class="size-4" /></button>
                            <button type="button" class="btn btn-ghost btn-sm btn-icon" wire:click="delete({{ $template->id }})" wire:confirm="Supprimer ce modèle ?" title="Supprimer" aria-label="Supprimer"><x-ui.icon name="trash" class="size-4 text-flame-600" /></button>
                        </div>
                    @endif
                </div>
                <p class="line-clamp-5 flex-1 text-[13px] whitespace-pre-line text-fg-muted">{{ $template->body }}</p>
            </article>
        @empty
            <div class="card md:col-span-2 xl:col-span-3"><x-ui.empty icon="document-duplicate" title="Aucun modèle" text="Créez vos messages types : relance de facture, promotion, rappel de rendez-vous…" /></div>
        @endforelse
    </div>

    <x-ui.modal wire:model="showForm" :title="$editingId ? 'Modifier le modèle' : 'Nouveau modèle'" icon="document-duplicate" width="max-w-3xl">
        <div class="grid gap-5 text-left md:grid-cols-[1fr_260px]">
            <div class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-ui.select label="Canal" wire:model.live="form.channel" :options="collect($channels)->map(fn ($c) => $c['label'])->all()" required />
                    <x-ui.input label="Nom du modèle" wire:model="form.name" placeholder="Relance facture" required />
                </div>
                @if ($form['channel'] === 'email')
                    <x-ui.input label="Objet" wire:model.live.debounce.400ms="form.subject" placeholder="Votre facture {entreprise}" required />
                @endif
                <x-ui.textarea label="Message" wire:model.live.debounce.400ms="form.body" rows="8" placeholder="Bonjour {prenom}, …" required />
                <div class="flex flex-wrap gap-1.5">
                    @foreach (\App\Livewire\Communication\Templates::PLACEHOLDERS as $placeholder => $help)
                        <button type="button" class="badge tone-brand cursor-pointer" wire:click="insert('{{ $placeholder }}')" title="{{ $help }}">{{ $placeholder }}</button>
                    @endforeach
                </div>
                @if ($form['channel'] === 'sms')
                    @php $len = mb_strlen($form['body'] ?? ''); @endphp
                    <p class="text-[12px] text-fg-subtle tabular-nums">{{ $len }} caractère(s) · {{ max(1, (int) ceil($len / 153)) }} SMS</p>
                @endif
            </div>
            <div>
                <p class="label">Aperçu ({{ $sample->display_name ?? 'contact exemple' }})</p>
                <div class="comm-wallpaper rounded-xl border border-line p-3">
                    <div class="rounded-2xl rounded-br-md bg-brand-600 px-3 py-2 text-[13px] text-white shadow-sm">
                        @if ($form['channel'] === 'email' && $previewSubject)<p class="mb-1 font-semibold">{{ $previewSubject }}</p>@endif
                        <p class="break-words whitespace-pre-line">{{ $preview ?: '…' }}</p>
                    </div>
                </div>
            </div>
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button icon="check" wire:click="save">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
