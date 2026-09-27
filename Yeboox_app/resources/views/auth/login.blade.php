<x-guest-layout>
    <div class="grid min-h-screen lg:grid-cols-2">
        <section class="relative hidden overflow-hidden bg-zinc-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute inset-0 opacity-60 [background-image:radial-gradient(circle_at_20%_20%,rgba(16,185,129,.35),transparent_35%),radial-gradient(circle_at_80%_70%,rgba(59,130,246,.3),transparent_40%)]"></div>
            <div class="relative flex items-center gap-3">
                <div class="grid size-11 place-items-center rounded-2xl bg-white font-bold text-zinc-950">Y</div>
                <span class="text-lg font-semibold">Yeboox POS</span>
            </div>
            <div class="relative max-w-xl">
                <p class="mb-5 text-sm font-medium uppercase tracking-[.25em] text-emerald-300">Restaurant · Bar · Commerce</p>
                <h1 class="text-5xl font-semibold leading-tight tracking-tight">Pilotez votre activité depuis un espace unique.</h1>
                <p class="mt-6 max-w-lg text-lg leading-8 text-zinc-300">Ventes, stocks, cuisine, caisse et comptabilité restent synchronisés, même lorsque la connexion est instable.</p>
            </div>
            <p class="relative text-sm text-zinc-500">Yeboox POS · {{ now()->year }}</p>
        </section>

        <main class="flex items-center justify-center bg-zinc-50 p-6 dark:bg-zinc-950 sm:p-12">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <div class="mb-5 grid size-11 place-items-center rounded-2xl bg-zinc-950 font-bold text-white dark:bg-white dark:text-zinc-950">Y</div>
                </div>

                <flux:heading size="xl">Bon retour</flux:heading>
                <flux:subheading class="mt-2">Connectez-vous à votre espace Yeboox.</flux:subheading>

                @if (session('status'))
                    <x-dismissible-alert variant="success" icon="check-circle" class="mt-6">{{ session('status') }}</x-dismissible-alert>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf
                    <flux:input name="email" type="email" label="Adresse e-mail" value="{{ old('email') }}" autocomplete="username" icon="envelope" required autofocus />
                    <flux:input name="password" type="password" label="Mot de passe" autocomplete="current-password" icon="lock-closed" viewable required />
                    <div class="flex items-center justify-between gap-4">
                        <flux:checkbox name="remember" label="Rester connecté" :checked="old('remember')" />
                        @if (Route::has('password.request'))
                            <flux:link href="{{ route('password.request') }}">Mot de passe oublié ?</flux:link>
                        @endif
                    </div>
                    @if ($errors->any())
                        <x-dismissible-alert variant="danger" icon="exclamation-circle">
                            <ul class="list-disc space-y-1 ps-5 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-dismissible-alert>
                    @endif
                    <flux:button type="submit" variant="primary" class="w-full">Se connecter</flux:button>
                </form>

                @if (Route::has('register'))
                    <flux:text class="mt-8 text-center">Pas encore de compte ? <flux:link href="{{ route('register') }}">Créer un compte</flux:link></flux:text>
                @endif
            </div>
        </main>
    </div>
</x-guest-layout>
