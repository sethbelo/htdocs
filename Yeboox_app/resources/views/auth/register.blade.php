<x-guest-layout>
    <main class="flex min-h-screen items-center justify-center bg-zinc-50 px-6 py-12 dark:bg-zinc-950">
        <flux:card class="w-full max-w-lg p-8! sm:p-10!">
            <div class="mb-8">
                <div class="mb-6 grid size-11 place-items-center rounded-2xl bg-zinc-950 font-bold text-white dark:bg-white dark:text-zinc-950">Y</div>
                <flux:heading size="xl">Créer votre compte</flux:heading>
                <flux:subheading class="mt-2">Configurez votre accès à Yeboox POS.</flux:subheading>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf
                <flux:input name="name" label="Nom complet" value="{{ old('name') }}" autocomplete="name" icon="user" required autofocus />
                <flux:input name="email" type="email" label="Adresse e-mail" value="{{ old('email') }}" autocomplete="username" icon="envelope" required />
                <flux:input name="password" type="password" label="Mot de passe" autocomplete="new-password" icon="lock-closed" viewable required />
                <flux:input name="password_confirmation" type="password" label="Confirmer le mot de passe" autocomplete="new-password" icon="lock-closed" viewable required />

                @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                    <flux:checkbox name="terms" required>
                        <flux:label>J’accepte les <flux:link href="{{ route('terms.show') }}" target="_blank">conditions d’utilisation</flux:link> et la <flux:link href="{{ route('policy.show') }}" target="_blank">politique de confidentialité</flux:link>.</flux:label>
                    </flux:checkbox>
                @endif

                @if ($errors->any())
                    <x-dismissible-alert variant="danger" icon="exclamation-circle">
                        <ul class="list-disc space-y-1 ps-5 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-dismissible-alert>
                @endif

                <flux:button type="submit" variant="primary" class="w-full">Créer mon compte</flux:button>
            </form>

            <flux:text class="mt-8 text-center">Déjà inscrit ? <flux:link href="{{ route('login') }}">Se connecter</flux:link></flux:text>
        </flux:card>
    </main>
</x-guest-layout>
