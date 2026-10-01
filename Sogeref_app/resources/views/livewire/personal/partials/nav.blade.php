{{-- Sous-navigation de l'Espace personnel (sous-écrans déclarés dans config/erp.php → modules.personal.menu) --}}
<nav class="tabs scrollbar-none no-print mb-6" aria-label="Espace personnel">
    @foreach (config('erp.modules.personal.menu', []) as $item)
        <a href="{{ route($item['route']) }}" wire:navigate class="tab" aria-selected="{{ $current === $item['route'] ? 'true' : 'false' }}" @if ($current === $item['route']) aria-current="page" @endif>
            <x-ui.icon :name="$item['icon']" class="size-[18px]" />{{ $item['label'] }}
        </a>
    @endforeach
</nav>
