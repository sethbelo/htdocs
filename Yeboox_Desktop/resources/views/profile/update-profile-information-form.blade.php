<form wire:submit="updateProfileInformation" class="space-y-6">
    <flux:card class="overflow-hidden p-0! shadow-sm">
        <div class="border-b border-zinc-200 bg-zinc-50/80 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900/60 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300"><flux:icon.identification class="size-5" /></span>
                <div><flux:heading>Informations personnelles</flux:heading><flux:text size="sm">Mettez à jour votre identité et votre photo de profil.</flux:text></div>
            </div>
        </div>

        <div class="space-y-7 p-5 sm:p-6">
            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                {{-- L'aperçu reste local au navigateur jusqu'à l'enregistrement du formulaire. --}}
                <div x-data="{ photoName: null, photoPreview: null }" class="flex flex-col gap-5 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800 sm:flex-row sm:items-center">
                    <input type="file" id="photo" class="sr-only" wire:model.live="photo" x-ref="photo" accept="image/jpeg,image/png"
                        x-on:change="
                            const file = $refs.photo.files?.[0];
                            if (!file) return;
                            photoName = file.name;
                            const reader = new FileReader();
                            reader.onload = (event) => photoPreview = event.target.result;
                            reader.readAsDataURL(file);
                        ">

                    <div class="relative mx-auto size-24 shrink-0 sm:mx-0">
                        <img x-bind:src="photoPreview || @js($this->user->profile_photo_url)" alt="Aperçu de la photo de {{ $this->user->name }}" class="size-24 rounded-2xl object-cover ring-4 ring-zinc-100 dark:ring-zinc-800">
                        <div wire:loading.flex wire:target="photo" class="absolute inset-0 items-center justify-center rounded-2xl bg-zinc-950/55 text-white"><flux:icon.arrow-path class="size-6 animate-spin" /></div>
                    </div>

                    <div class="min-w-0 flex-1 text-center sm:text-left">
                        <flux:heading size="sm">Photo du profil</flux:heading>
                        <flux:text size="sm" class="mt-1">JPG ou PNG, 1 Mo maximum. L’aperçu apparaît avant l’enregistrement.</flux:text>
                        <p x-show="photoName" x-text="photoName" class="mt-2 truncate text-xs font-medium text-violet-600 dark:text-violet-300"></p>
                        <div class="mt-4 flex flex-wrap justify-center gap-2 sm:justify-start">
                            <flux:button type="button" size="sm" icon="camera" x-on:click="$refs.photo.click()">Choisir une photo</flux:button>
                            @if ($this->user->profile_photo_path)
                                <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="deleteProfilePhoto" wire:confirm="Supprimer votre photo actuelle ?">Supprimer</flux:button>
                            @endif
                        </div>
                        @error('photo') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <flux:input wire:model="state.name" label="Nom complet" icon="user" required autocomplete="name" />
                <flux:input wire:model="state.email" type="email" label="Adresse e-mail" icon="envelope" required autocomplete="username" />
            </div>

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) && ! $this->user->hasVerifiedEmail())
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/30">
                    <div class="flex gap-3">
                        <flux:icon.exclamation-triangle class="mt-0.5 size-5 shrink-0 text-amber-600" />
                        <div>
                            <p class="text-sm font-medium text-amber-900 dark:text-amber-200">Votre adresse e-mail n’est pas vérifiée.</p>
                            <flux:button type="button" size="sm" variant="ghost" class="mt-2" wire:click.prevent="sendEmailVerification">Renvoyer le lien de vérification</flux:button>
                            @if ($this->verificationLinkSent)<p class="mt-2 text-sm text-emerald-700 dark:text-emerald-300">Un nouveau lien vient d’être envoyé.</p>@endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-zinc-200 bg-zinc-50/80 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900/60 sm:px-6">
            <x-action-message on="saved">Modifications enregistrées.</x-action-message>
            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="photo,updateProfileInformation">Enregistrer</flux:button>
        </div>
    </flux:card>
</form>
