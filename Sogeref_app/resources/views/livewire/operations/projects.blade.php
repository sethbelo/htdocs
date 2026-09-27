<div>
    <x-ui.page-header title="Projets" eyebrow="Services" subtitle="Prestations, chantiers et fabrications suivis en tâches, heures et budget.">
        <x-slot:actions>@if (auth()->user()->canManage('projects'))<x-ui.button variant="accent" icon="plus" wire:click="create">Nouveau projet</x-ui.button>@endif</x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 flex gap-1 overflow-x-auto scrollbar-none">
        @foreach (['' => 'Tous'] + collect(config('erp.statuses.project'))->map(fn ($s) => $s[0])->all() as $k => $l)
            <button type="button" wire:click="$set('status', '{{ $k }}')" @class(['btn btn-sm', 'btn-soft' => $status === $k, 'btn-ghost' => $status !== $k])>{{ $l }} <span class="text-fg-subtle">{{ $k === '' ? $counts->sum() : ($counts[$k] ?? 0) }}</span></button>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
        @forelse ($projects as $p)
            @php $progress = $p->tasks_count ? round($p->done_count / $p->tasks_count * 100) : $p->progress; @endphp
            <a href="{{ route('projects.show', $p) }}" wire:navigate class="card group overflow-hidden transition hover:border-brand-200 hover:shadow-md">
                <div class="h-1.5" style="background: {{ $p->color }}"></div>
                <div class="p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0"><p class="text-[12px] font-semibold text-fg-subtle">{{ $p->code }}</p><h3 class="line-clamp-2 font-display text-[16px] font-semibold group-hover:text-brand-600">{{ $p->name }}</h3></div>
                        <x-ui.status map="project" :value="$p->status" />
                    </div>
                    <p class="mt-1 truncate text-[12.5px] text-fg-muted">{{ $p->contact?->display_name ?? 'Projet interne' }} · {{ $p->manager?->name }}</p>
                    <x-ui.progress class="mt-4" :value="$progress" :label="$p->done_count.'/'.$p->tasks_count.' tâches'" tone="sun" />
                    <div class="mt-3 flex justify-between text-[12px] text-fg-subtle"><span>{{ qty($p->timesheets_sum_hours ?? 0) }} h saisies</span><span>{{ $p->end_date ? 'Échéance '.$p->end_date->format('d/m/Y') : '' }}</span></div>
                </div>
            </a>
        @empty
            <div class="card md:col-span-2 2xl:col-span-3"><x-ui.empty icon="rectangle-stack" title="Aucun projet" /></div>
        @endforelse
    </div>

    <x-ui.drawer wire:model="showForm" title="Nouveau projet" icon="rectangle-stack">
        <div class="space-y-4">
            <x-ui.input label="Nom du projet" wire:model="form.name" required />
            <x-ui.select label="Client" wire:model="form.contact_id" placeholder="Projet interne" :options="$clients->mapWithKeys(fn ($c) => [$c->id => $c->display_name])" />
            <x-ui.select label="Chef de projet" wire:model="form.manager_id" :options="$users->pluck('name', 'id')" />
            <div class="grid grid-cols-2 gap-3"><x-ui.input label="Début" type="date" wire:model="form.start_date" /><x-ui.input label="Échéance" type="date" wire:model="form.end_date" /></div>
            <div class="grid grid-cols-2 gap-3"><x-ui.input label="Budget" type="number" wire:model="form.budget" suffix="USD" /><x-ui.input label="Couleur" type="color" wire:model="form.color" class="[&_input]:h-10 [&_input]:p-1" /></div>
            <x-ui.textarea label="Description" wire:model="form.description" rows="3" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="save">Créer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
