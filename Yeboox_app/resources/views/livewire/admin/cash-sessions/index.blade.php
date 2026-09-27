<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Sessions de caisse</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $openCount }} session(s) ouverte(s)</p>
        </div>
    </div>

    <!-- Filter -->
    <flux:card class="mb-4 max-w-sm">
        <flux:select wire:model.live="statusFilter">
            <flux:select.option value="">Tous les statuts</flux:select.option>
            <flux:select.option value="open">Ouverte</flux:select.option>
            <flux:select.option value="closed">Fermée</flux:select.option>
            <flux:select.option value="validated">Validée</flux:select.option>
        </flux:select>
    </flux:card>

    <!-- Sessions Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-900/50">
                    <tr>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase px-4 py-3">Caisse</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase px-4 py-3">Caissier</th>
                        <th class="text-right text-xs font-semibold text-slate-500 uppercase px-4 py-3">Ouverture</th>
                        <th class="text-right text-xs font-semibold text-slate-500 uppercase px-4 py-3">Clôture</th>
                        <th class="text-right text-xs font-semibold text-slate-500 uppercase px-4 py-3">Écart</th>
                        <th class="text-center text-xs font-semibold text-slate-500 uppercase px-4 py-3">Statut</th>
                        <th class="text-right text-xs font-semibold text-slate-500 uppercase px-4 py-3">Ouvert le</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($sessions as $session)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" wire:key="sess-{{ $session->id }}">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $session->cashRegister?->name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $session->cashRegister?->device_name }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-primary-600 text-white flex items-center justify-center text-[10px] font-bold">
                                        {{ $session->user?->initials }}
                                    </div>
                                    <span class="text-sm text-slate-900 dark:text-white">{{ $session->user?->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-slate-900 dark:text-white">
                                {{ number_format($session->opening_amount, 0, ',', '.') }} FC
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                @if($session->closing_amount !== null)
                                    <span class="font-medium text-slate-900 dark:text-white">{{ number_format($session->closing_amount, 0, ',', '.') }} FC</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                @if($session->difference !== null)
                                    <span class="{{ $session->difference >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-medium">
                                        {{ $session->difference >= 0 ? '+' : '' }}{{ number_format($session->difference, 0, ',', '.') }} FC
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusConfig = [
                                        'open' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                        'closed' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                                        'validated' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                    ];
                                    $statusLabels = ['open' => 'Ouverte', 'closed' => 'Fermée', 'validated' => 'Validée'];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium {{ $statusConfig[$session->status] ?? '' }}">
                                    @if($session->status === 'open')
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span>
                                    @endif
                                    {{ $statusLabels[$session->status] ?? $session->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-slate-500">
                                {{ $session->opened_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <x-heroicon-o-calculator class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3"/>
                                <p class="text-sm text-slate-500">Aucune session de caisse</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700"><flux:pagination :paginator="$sessions" scroll-to /></div>
        @endif
    </div>
</div>
