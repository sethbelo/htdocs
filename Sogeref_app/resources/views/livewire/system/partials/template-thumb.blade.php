{{-- Miniature schématique d'un modèle d'impression ($thumb : band, frame, minimal, split, ticket, list, site, gold) --}}
@php
    $bar = fn ($w, $c = '#cfd9e1', $h = 3) => "<div style=\"height: {$h}px; width: {$w}%; background: {$c}; border-radius: 2px; margin-top: 3px;\"></div>";
    $lines = fn ($n = 4) => collect(range(1, $n))->map(fn ($i) => '<div style="height:3px;border-radius:2px;margin-top:3px;background:#e3e9ee;width:'.(92 - ($i % 3) * 14).'%"></div>')->implode('');
@endphp
<div class="relative mx-auto h-28 w-[86px] overflow-hidden rounded-md border border-line bg-white p-1.5 shadow-sm" aria-hidden="true">
    @switch($thumb)
        @case('band')
            <div class="h-3 rounded-sm bg-[#104A63]"></div>
            <div class="mt-0.5 h-0.5 w-1/3 bg-[#FFCE31]"></div>
            <div class="mt-1.5 grid grid-cols-2 gap-1"><div class="h-5 rounded-sm bg-[#f4f7f9]"></div><div class="h-5 rounded-sm border border-[#d6dfe6]"></div></div>
            <div class="mt-1.5 h-1.5 rounded-sm bg-[#104A63]"></div>{!! $lines(4) !!}
            @break
        @case('frame')
            <div class="h-full rounded-sm border-2 border-[#104A63] p-1">
                <div class="h-full border border-[#104A63] p-1">{!! $bar(60, '#104A63') !!}{!! $lines(5) !!}<div class="mt-2 ml-auto h-3 w-8 rounded-sm border border-dashed border-[#8a9dac]"></div></div>
            </div>
            @break
        @case('minimal')
            <div class="flex justify-between">{!! $bar(40, '#111', 4) !!}<div class="h-2 w-5 bg-[#111]"></div></div>
            <div class="mt-1.5 h-px bg-[#111]"></div>{!! $lines(6) !!}<div class="mt-3 h-px w-1/2 bg-[#111]"></div>
            @break
        @case('split')
            <div class="h-[46%] rounded-sm border border-[#104A63] p-1">{!! $bar(50, '#104A63') !!}{!! $lines(2) !!}</div>
            <div class="my-1 border-t border-dashed border-[#8a9dac]"></div>
            <div class="h-[46%] rounded-sm border border-[#104A63] p-1">{!! $bar(50, '#104A63') !!}{!! $lines(2) !!}</div>
            @break
        @case('ticket')
            <div class="mx-auto h-full w-9 rounded-sm border border-[#cfd9e1] px-0.5 py-1">{!! $bar(70, '#111') !!}<div class="my-1 border-t border-dashed border-[#111]"></div>{!! $lines(6) !!}<div class="my-1 border-t border-dashed border-[#111]"></div>{!! $bar(90, '#111') !!}</div>
            @break
        @case('list')
            <div class="h-2 rounded-sm bg-[#104A63]"></div>
            @foreach (range(1, 7) as $i)<div class="mt-1 flex items-center gap-0.5"><div class="size-1.5 border border-[#104A63]"></div><div class="h-0.5 flex-1 bg-[#e3e9ee]"></div><div class="h-0.5 w-2 bg-[#104A63]"></div></div>@endforeach
            @break
        @case('site')
            <div class="h-6 rounded-sm bg-[#104A63] p-0.5"><div class="h-1 w-2/3 bg-[#FFCE31]"></div><div class="mt-1 h-0.5 w-1/2 bg-white/60"></div></div>
            <div class="mt-1 h-1.5 rounded-sm bg-[#104A63]/80"></div>{!! $lines(4) !!}
            <div class="mt-1.5 grid grid-cols-3 gap-0.5"><div class="h-3 border border-dashed border-[#8a9dac]"></div><div class="h-3 border border-dashed border-[#8a9dac]"></div><div class="h-3 border border-dashed border-[#8a9dac]"></div></div>
            @break
        @case('gold')
            <div class="h-4 rounded-sm bg-[#FFCE31] p-0.5"><div class="h-1 w-1/2 bg-[#104A63]"></div></div>
            <div class="mt-1.5 h-5 rounded border-2 border-[#104A63]"></div>
            <div class="mt-1.5 h-1.5 rounded-sm bg-[#FFCE31]"></div>{!! $lines(3) !!}<div class="mt-1 ml-auto h-1.5 w-1/2 rounded-sm bg-[#104A63]"></div>
            @break
    @endswitch
</div>
