<div>
    <x-ui.page-header title="Rapports" eyebrow="Finance" subtitle="Tableaux de gestion imprimables sur le papier à en-tête SOGEREF.">
        <x-slot:actions>
            <a href="{{ route('pdf.letterhead') }}" target="_blank" class="btn btn-secondary"><x-ui.icon name="document" class="size-[18px]" /> Papier à en-tête</a>
            <a href="{{ route('pdf.report', ['report' => $report, 'from' => $from, 'to' => $to]) }}" target="_blank" class="btn btn-primary"><x-ui.icon name="printer" class="size-[18px]" /> Imprimer le rapport</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
        <nav class="card h-fit p-2">
            @foreach ($catalog as $key => [$title, $icon, $desc])
                <button type="button" wire:click="$set('report', '{{ $key }}')" @class(['flex w-full items-start gap-3 rounded-xl px-3 py-2.5 text-left transition', 'bg-brand-50 dark:bg-white/8' => $report === $key, 'hover:bg-surface-2' => $report !== $key])>
                    <x-ui.icon :name="$icon" @class(['mt-0.5 size-5', 'text-brand-600 dark:text-lagoon-300' => $report === $key, 'text-fg-subtle' => $report !== $key]) />
                    <span><span class="block text-[13.5px] font-semibold">{{ $title }}</span><span class="block text-[12px] leading-snug text-fg-subtle">{{ $desc }}</span></span>
                </button>
            @endforeach
        </nav>

        <div class="min-w-0 space-y-5">
            <div class="card flex flex-col gap-2 p-3 md:flex-row md:items-center">
                <input type="date" wire:model.live="from" class="input md:w-44"><span class="hidden text-fg-subtle md:block">→</span><input type="date" wire:model.live="to" class="input md:w-44">
                <div class="flex gap-1 md:ml-auto">
                    @foreach (['mois' => 'Ce mois', 'mois-dernier' => 'Mois dernier', 'trimestre' => 'Trimestre', 'annee' => 'Année'] as $k => $l)
                        <button type="button" wire:click="preset('{{ $k }}')" class="btn btn-ghost btn-sm">{{ $l }}</button>
                    @endforeach
                </div>
            </div>

            <div>
                <h2 class="font-display text-[20px] font-semibold">{{ $data['title'] }}</h2>
                @if (! empty($data['subtitle']))<p class="text-[13px] text-fg-subtle">{{ $data['subtitle'] }}</p>@endif
            </div>

            @if (! empty($data['kpis']))
                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    @foreach ($data['kpis'] as $kpi)<div class="kpi"><p class="kpi-label">{{ $kpi['label'] }}</p><p class="kpi-value text-[22px]">{{ $kpi['value'] }}</p></div>@endforeach
                </div>
            @endif

            @foreach ($data['sections'] as $section)
                <x-ui.card :title="$section['title']" :padded="false">
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr>@foreach ($section['columns'] as $i => $c)<th @class(['num' => $section['numeric'][$i] ?? false])>{{ $c }}</th>@endforeach</tr></thead>
                            <tbody>
                                @forelse ($section['rows'] as $row)
                                    <tr @class(['bg-surface-2 font-semibold' => $row['_total'] ?? false])>
                                        @foreach (array_values(array_diff_key($row, ['_total' => 1])) as $i => $cell)<td @class(['num' => $section['numeric'][$i] ?? false])>{{ $cell }}</td>@endforeach
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ count($section['columns']) }}"><x-ui.empty icon="chart-bar" title="Aucune donnée sur la période" /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </div>
</div>
