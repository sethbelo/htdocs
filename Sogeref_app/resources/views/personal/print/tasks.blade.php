@extends('personal.print.layout', ['title' => 'Tâches du '.$day->format('d/m/Y'), 'heading' => 'TÂCHES DU JOUR', 'reference' => ucfirst($day->translatedFormat('l j F Y'))])

@section('content')
    @php $done = $tasks->where('is_done', true)->count(); @endphp
    <div class="mb-4 flex items-end justify-between">
        <h1 class="font-display text-[22px] font-semibold text-ink-900">{{ ucfirst($day->translatedFormat('l j F Y')) }}</h1>
        <p class="text-[12.5px] text-ink-500">{{ $tasks->count() }} tâche(s) · {{ $done }} terminée(s)</p>
    </div>
    <table class="w-full border-collapse text-[13px]">
        <thead>
            <tr class="border-b-2 border-brand-600 text-left text-[11px] tracking-wider text-brand-600 uppercase">
                <th class="w-8 py-2"></th><th class="py-2">Tâche</th><th class="w-32 py-2">Créneau</th><th class="w-24 py-2">Priorité</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tasks as $task)
                <tr class="border-b border-ink-200 align-top">
                    <td class="py-2.5"><span class="inline-grid size-4 place-items-center rounded border-2 border-ink-400 text-[10px] font-bold">{{ $task->is_done ? '✓' : '' }}</span></td>
                    <td class="py-2.5"><p @class(['font-medium', 'text-ink-400 line-through' => $task->is_done])>{{ $task->title }}</p>@if ($task->notes)<p class="text-[12px] text-ink-500">{{ $task->notes }}</p>@endif</td>
                    <td class="py-2.5 text-ink-600 tabular-nums">{{ $task->timeSlot() ?? '—' }}</td>
                    <td class="py-2.5">{{ erp_status('priority', $task->priority)[0] }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-8 text-center text-ink-500">Aucune tâche pour ce jour.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-8">
        <p class="mb-2 text-[11px] font-semibold tracking-wider text-ink-500 uppercase">Notes</p>
        @for ($i = 0; $i < 5; $i++)<div class="h-8 border-b border-dashed border-ink-300"></div>@endfor
    </div>
@endsection
