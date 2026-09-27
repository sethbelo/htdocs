@php($compact = $compact ?? true)

<flux:button.group class="yb-action-group {{ $compact ? 'justify-end' : 'w-full [&>*]:flex-1' }}">
    @if($product->type === 'product')
        <flux:button
            size="sm"
            variant="primary"
            color="amber"
            icon="arrows-right-left"
            wire:click="openOperation('adjustment', {{ $product->id }})"
            :tooltip="$compact ? 'Ajuster le stock' : null"
        >@if(!$compact) Ajuster @endif</flux:button>
    @endif

    <flux:button
        size="sm"
        variant="primary"
        color="blue"
        icon="pencil-square"
        wire:click="editProduct({{ $product->id }})"
        :tooltip="$compact ? 'Modifier' : null"
    >@if(!$compact) Modifier @endif</flux:button>

    <flux:button
        size="sm"
        variant="primary"
        :color="$product->is_active ? 'slate' : 'emerald'"
        :icon="$product->is_active ? 'eye-slash' : 'eye'"
        wire:click="toggleActive({{ $product->id }})"
        :tooltip="$compact ? ($product->is_active ? 'Désactiver' : 'Activer') : null"
    >@if(!$compact) {{ $product->is_active ? 'Désactiver' : 'Activer' }} @endif</flux:button>

    <flux:button
        size="sm"
        variant="danger"
        icon="trash"
        wire:click="confirmDelete({{ $product->id }})"
        :tooltip="$compact ? 'Supprimer' : null"
    >@if(!$compact) Supprimer @endif</flux:button>
</flux:button.group>
