{{-- Accusés d'envoi d'un message sortant : queued (horloge), sent (✓), delivered (✓✓), read (✓✓ turquoise), failed (!) --}}
@php $light ??= false; @endphp
@php [$label] = \App\Livewire\Communication\Inbox::statusMeta($status); @endphp
<span class="inline-flex items-center" title="{{ $label }}" aria-label="{{ $label }}">
    @switch($status)
        @case('queued')<x-ui.icon name="clock" @class(['size-3.5', 'text-white/60' => $light, 'text-fg-subtle' => ! $light]) />@break
        @case('sent')<x-ui.icon name="check" @class(['size-3.5', 'text-white/70' => $light, 'text-fg-subtle' => ! $light]) />@break
        @case('delivered')
        @case('read')
            <span class="relative inline-flex w-[18px]">
                <x-ui.icon name="check" @class(['size-3.5', 'text-lagoon-300' => $status === 'read' && $light, 'text-lagoon-500' => $status === 'read' && ! $light, 'text-white/70' => $status !== 'read' && $light, 'text-fg-subtle' => $status !== 'read' && ! $light]) />
                <x-ui.icon name="check" @class(['absolute left-[5px] size-3.5', 'text-lagoon-300' => $status === 'read' && $light, 'text-lagoon-500' => $status === 'read' && ! $light, 'text-white/70' => $status !== 'read' && $light, 'text-fg-subtle' => $status !== 'read' && ! $light]) />
            </span>
            @break
        @case('failed')<x-ui.icon name="exclamation-circle" class="size-3.5 text-flame-400" />@break
    @endswitch
</span>
