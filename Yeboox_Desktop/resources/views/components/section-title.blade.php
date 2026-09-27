<div class="md:col-span-1 flex justify-between">
    <div class="px-4 sm:px-0">
        <flux:heading size="lg">{{ $title }}</flux:heading>
        <flux:text class="mt-1">{{ $description }}</flux:text>
    </div>

    <div class="px-4 sm:px-0">
        {{ $aside ?? '' }}
    </div>
</div>
