<div class="space-y-6">
    <x-page-header title="Circuit de validation" subtitle="Demandes d’achat, notes de frais, bons de décaissement… selon les seuils configurés." />

    <div class="panel p-4">
        <flux:heading>À valider ({{ $pending->count() }})</flux:heading>
        <ul class="mt-2 divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($pending as $request)
                <li class="flex flex-wrap items-center justify-between gap-2 py-3" wire:key="pd-{{ $request->id }}">
                    <div>
                        <div class="font-medium">{{ $request->approvable?->approvalTitle() }}</div>
                        <div class="text-sm text-zinc-500">{{ $request->workflow->name }} · étape « {{ $request->currentStep->name }} » · demandé par {{ $request->requester?->name }} {{ $request->created_at->diffForHumans() }}</div>
                    </div>
                    <div class="flex items-center gap-3"><x-money :amount="$request->amount" class="font-semibold" /><flux:button size="sm" variant="primary" wire:click="open({{ $request->id }})">Examiner</flux:button></div>
                </li>
            @empty
                <li class="py-6"><x-empty-state icon="check-badge" title="Rien à valider" /></li>
            @endforelse
        </ul>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        @foreach (['Mes demandes' => $mine, 'Mes décisions récentes' => $history] as $title => $list)
            <div class="panel p-4">
                <flux:heading>{{ $title }}</flux:heading>
                <ul class="mt-2 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                    @forelse ($list as $request)
                        <li class="py-2" wire:key="{{ Str::slug($title) }}-{{ $request->id }}">
                            <div class="flex justify-between"><span class="font-medium">{{ $request->approvable?->approvalTitle() }}</span><x-status-badge :status="$request->status" /></div>
                            @foreach ($request->actions as $action)
                                <div class="text-xs text-zinc-500">{{ $action->created_at->format('d/m H:i') }} · {{ $action->step?->name }} · {{ $action->decision->label() }} par {{ $action->user->name }}{{ $action->reason ? ' — '.$action->reason : '' }}</div>
                            @endforeach
                        </li>
                    @empty
                        <li class="py-2 text-zinc-500">—</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>

    <flux:modal name="decision" class="w-full max-w-xl">
        @if ($current)
            <div class="space-y-4">
                <flux:heading size="lg">{{ $current->approvable?->approvalTitle() }}</flux:heading>
                <flux:text>Montant : <strong><x-money :amount="$current->amount" /></strong> · demandé par {{ $current->requester?->name }}</flux:text>
                @if ($current->approvable?->justification ?? $current->approvable?->description ?? null)
                    <flux:callout icon="information-circle" :text="$current->approvable->justification ?? $current->approvable->description" />
                @endif
                <ol class="flex flex-wrap gap-2 text-xs">
                    @foreach ($current->workflow->steps as $step)
                        <li @class(['rounded-full px-2.5 py-1', 'bg-brand-600 text-white' => $step->id === $current->current_step_id, 'bg-zinc-100 dark:bg-zinc-800' => $step->id !== $current->current_step_id, 'opacity-40' => (float) $current->amount < (float) $step->min_amount])>{{ $step->sequence }}. {{ $step->name }} @if ((float) $step->min_amount > 0)(≥ {{ (float) $step->min_amount }})@endif</li>
                    @endforeach
                </ol>
                <flux:textarea wire:model="comment" label="Commentaire" rows="2" />
                <flux:input wire:model="reason" label="Motif (obligatoire en cas de refus)" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="danger" icon="x-mark" wire:click="reject">Refuser</flux:button>
                    <flux:button variant="primary" icon="check" wire:click="approve">Approuver</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
