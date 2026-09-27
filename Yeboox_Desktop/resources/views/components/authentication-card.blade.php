<div class="flex min-h-screen flex-col items-center justify-center bg-zinc-50 px-4 dark:bg-zinc-950">
    <div>
        {{ $logo }}
    </div>

    <flux:card class="mt-6 w-full max-w-md">
        {{ $slot }}
    </flux:card>
</div>
