{{-- Confirmation de déconnexion partagée par les interfaces principale et POS. --}}
<flux:modal name="confirm-logout" class="md:w-[30rem]" :dismissible="false">
    <div class="space-y-6">
        <div class="flex items-start gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                <flux:icon.arrow-right-start-on-rectangle class="size-6" />
            </span>
            <div class="min-w-0">
                <flux:heading size="lg">Confirmer la déconnexion</flux:heading>
                <flux:text class="mt-1">Voulez-vous vraiment quitter votre session Yeboox&nbsp;?</flux:text>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-2xl bg-zinc-100 p-3 dark:bg-zinc-800/80">
            <flux:avatar :src="auth()->user()?->profile_photo_url" :name="auth()->user()?->name" size="lg" />
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold">{{ auth()->user()?->name }}</div>
                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()?->email }}</div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <flux:modal.close>
                <flux:button type="button" variant="ghost">Rester connecté</flux:button>
            </flux:modal.close>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button type="submit" variant="danger" icon="arrow-right-start-on-rectangle" class="w-full sm:w-auto">Se déconnecter</flux:button>
            </form>
        </div>
    </div>
</flux:modal>
