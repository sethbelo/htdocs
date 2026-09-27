{{-- Interrupteur on/off lié à une propriété booléenne --}}
@props(['label' => null, 'description' => null])
<label {{ $attributes->only('class')->merge(['class' => 'flex cursor-pointer items-start justify-between gap-4']) }}>
    <span class="min-w-0">
        @if ($label)<span class="block text-[13.5px] font-semibold text-fg">{{ $label }}</span>@endif
        @if ($description)<span class="block text-[12.5px] text-fg-subtle">{{ $description }}</span>@endif
    </span>
    <span class="relative mt-0.5 inline-flex shrink-0">
        <input type="checkbox" {{ $attributes->except('class') }} class="peer sr-only">
        <span class="h-6 w-11 rounded-full bg-ink-300 transition peer-checked:bg-brand-600 peer-focus-visible:ring-3 peer-focus-visible:ring-lagoon-400/40 dark:bg-white/15"></span>
        <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
    </span>
</label>
