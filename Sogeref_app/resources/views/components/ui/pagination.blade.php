{{-- Pagination SOGEREF (vue par défaut du Paginator) --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-3 text-[13px]">
        <p class="text-fg-subtle">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ $paginator->total() }}</p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-ghost btn-sm btn-icon opacity-40"><x-ui.icon name="chevron-left" class="size-4" /></span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" class="btn btn-ghost btn-sm btn-icon" aria-label="Page précédente"><x-ui.icon name="chevron-left" class="size-4" /></button>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-fg-subtle">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn btn-soft btn-sm min-w-8" aria-current="page">{{ $page }}</span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" class="btn btn-ghost btn-sm min-w-8">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" class="btn btn-ghost btn-sm btn-icon" aria-label="Page suivante"><x-ui.icon name="chevron-right" class="size-4" /></button>
            @else
                <span class="btn btn-ghost btn-sm btn-icon opacity-40"><x-ui.icon name="chevron-right" class="size-4" /></span>
            @endif
        </div>
    </nav>
@endif
