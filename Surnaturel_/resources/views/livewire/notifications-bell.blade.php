<div wire:poll.60s>
    <flux:dropdown position="bottom" align="end">
        <button type="button" class="shell-icon-btn relative" aria-label="Notifications">
            <flux:icon.bell variant="mini" />
            @if ($unread)
                <span class="absolute top-1.5 right-1.5 grid min-w-4 place-items-center rounded-full bg-carmin-500 px-1 text-[10px] leading-4 font-bold text-white">{{ min($unread, 9) }}</span>
            @endif
        </button>
        <flux:menu class="w-80">
            <div class="flex items-center justify-between px-2 py-1.5">
                <flux:heading size="sm">Notifications</flux:heading>
                @if ($unread)
                    <flux:button size="xs" variant="ghost" wire:click="markAllRead">Tout marquer lu</flux:button>
                @endif
            </div>
            <flux:menu.separator />
            @forelse ($notifications as $notification)
                <flux:menu.item wire:click="open('{{ $notification->id }}')" wire:key="n-{{ $notification->id }}">
                    <div class="flex min-w-0 items-start gap-2.5">
                        <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-lg bg-safran-100 text-safran-800 dark:bg-safran-400/15 dark:text-safran-300">
                            <flux:icon :icon="$notification->data['icon'] ?? 'bell'" variant="micro" class="size-3.5" />
                        </span>
                        <div class="min-w-0">
                            <div @class(['text-sm truncate', 'font-semibold' => ! $notification->read_at])>{{ $notification->data['title'] ?? 'Notification' }}</div>
                            <div class="truncate text-xs text-fg-subtle">{{ $notification->data['message'] ?? '' }}</div>
                            <div class="text-[11px] text-fg-subtle">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                </flux:menu.item>
            @empty
                <p class="px-3 py-8 text-center text-[13px] text-fg-subtle">Rien en attente. Tout est à jour ✓</p>
            @endforelse
        </flux:menu>
    </flux:dropdown>
</div>
