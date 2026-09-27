@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white', 'dropdownClasses' => ''])

@php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    'none', 'false' => '',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
    '60' => 'w-60',
    default => 'w-48',
};
@endphp

<flux:dropdown position="bottom" :align="$align === 'left' ? 'start' : 'end'" class="{{ $dropdownClasses }}">
    {{ $trigger }}
    <flux:navmenu class="{{ $width }} {{ $contentClasses }}">{{ $content }}</flux:navmenu>
</flux:dropdown>
