@php
    $slides = [
        ['tag' => 'Bienvenue chez vous', 'title' => 'Votre commerce. Un nouveau départ.', 'text' => 'Restaurant, boutique, hôtel, pharmacie ou espace bien-être : réunissez vos opérations dans un espace de travail simple et agréable.', 'image' => 'african-restaurant.jpg', 'icon' => 'sparkles', 'features' => ['Une interface pensée pour votre équipe', 'Vos données sur ce poste, avec SQLite', 'PHP et les composants Windows inclus'], 'label' => 'Votre espace de travail'],
        ['tag' => '01 · Vendre avec fluidité', 'title' => 'Une caisse qui suit votre rythme.', 'text' => 'Retrouvez vos articles, préparez les commandes et encaissez en USD ou en CDF. Sur place, à emporter ou en livraison : chaque vente garde sa place.', 'image' => 'african-bar.jpg', 'icon' => 'shopping-cart', 'features' => ['Paiements multiples et monnaie rendue', 'Tables, commandes et tickets de caisse', 'Ouverture et clôture de caisse'], 'label' => 'Point de vente'],
        ['tag' => '02 · Maîtriser votre catalogue', 'title' => 'Le bon produit. Le bon prix.', 'text' => 'Créez vos produits, services et formules. Renseignez vos prix, votre fiscalité et vos quantités ; retrouvez votre catalogue directement au point de vente.', 'image' => 'african-pharmacy.jpg', 'icon' => 'cube', 'features' => ['Prix USD / CDF et taxes configurables', 'Stocks, approvisionnements et alertes', 'Catégories, unités et import Excel'], 'label' => 'Produits & stocks'],
        ['tag' => '03 · Cultiver la relation', 'title' => 'Des clients qui aiment revenir.', 'text' => 'Centralisez les fiches clients, organisez les rendez-vous et accompagnez votre équipe dans le suivi des prestations.', 'image' => 'african-spa.jpg', 'icon' => 'heart', 'features' => ['Clients et historique des opérations', 'Rendez-vous et prestations', 'Fidélité, points et récompenses'], 'label' => 'Clients & rendez-vous'],
        ['tag' => '04 · Piloter sereinement', 'title' => 'Une vue claire. Une équipe alignée.', 'text' => 'Suivez votre activité avec les tableaux de bord, les factures et la comptabilité. Attribuez les accès adaptés à chaque membre de votre équipe.', 'image' => 'african-hotel.jpg', 'icon' => 'chart-bar', 'features' => ['Rapports, factures et comptabilité', 'Rôles, permissions et journal d’activité', 'Base vide : commencez avec vos données'], 'label' => 'Pilotage & équipe'],
    ];
    $slide = $slides[max(0, min(4, $welcomeStep))];
@endphp
<section class="relative mx-auto w-full max-w-6xl overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl dark:bg-zinc-900" aria-label="Présentation de Yeboox" wire:key="welcome-{{ $welcomeStep }}">
    <header class="flex items-center justify-between gap-4 border-b border-zinc-100 px-6 py-5 dark:border-zinc-800 sm:px-10">
        <img src="{{ asset('images/yeboox-logo.png') }}" alt="Yeboox" class="h-9 w-auto rounded-lg bg-white p-1" />
        <span class="rounded-full bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 dark:bg-violet-500/15 dark:text-violet-200">Desktop 9.0 · SQLite</span>
    </header>
    <div class="grid lg:min-h-[34rem] lg:grid-cols-2">
        <div class="flex flex-col justify-center p-6 sm:p-10 lg:p-12" aria-live="polite" aria-atomic="true">
            <span class="mb-6 grid size-12 place-items-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-200"><flux:icon :name="$slide['icon']" class="size-6" /></span>
            <p class="text-xs font-bold uppercase tracking-widest text-violet-600 dark:text-violet-300">{{ $slide['tag'] }}</p>
            <h1 class="mt-4 text-3xl font-semibold leading-tight tracking-tight text-zinc-950 dark:text-white sm:text-4xl">{{ $slide['title'] }}</h1>
            <p class="mt-5 text-base leading-7 text-zinc-600 dark:text-zinc-300">{{ $slide['text'] }}</p>
            <ul class="mt-7 space-y-3 text-sm text-zinc-700 dark:text-zinc-200">
                @foreach($slide['features'] as $feature)
                    <li class="flex items-start gap-3"><flux:icon.check-circle class="size-5 shrink-0 text-emerald-600" /><span>{{ $feature }}</span></li>
                @endforeach
            </ul>
        </div>
        <div class="relative isolate min-h-[22rem] overflow-hidden bg-violet-950 p-6 sm:p-10 lg:flex lg:items-center">
            <img src="{{ asset('images/'.$slide['image']) }}" alt="Illustration d’un établissement : {{ $slide['label'] }}" class="absolute inset-0 -z-20 size-full object-cover opacity-45" />
            <div class="absolute inset-0 -z-10 bg-linear-to-t from-violet-950 via-violet-950/60 to-transparent"></div>
            <svg class="pointer-events-none absolute right-0 top-0 -z-10 h-full w-full text-violet-300/20" viewBox="0 0 500 500" fill="none" aria-hidden="true"><circle cx="430" cy="65" r="185" stroke="currentColor" stroke-width="1"/><circle cx="430" cy="65" r="140" stroke="currentColor" stroke-width="1"/><path d="M0 350C140 180 290 500 500 270M0 390C140 220 290 540 500 310" stroke="currentColor" stroke-width="2"/></svg>
            <div class="w-full overflow-hidden rounded-2xl border border-white/25 bg-white/95 shadow-2xl">
                <div class="flex items-center gap-1.5 border-b border-zinc-100 bg-zinc-50 p-3"><span class="size-2 rounded-full bg-rose-400"></span><span class="size-2 rounded-full bg-amber-400"></span><span class="size-2 rounded-full bg-emerald-400"></span><span class="ml-3 text-[10px] font-semibold text-zinc-500">YEBOOX / {{ $slide['label'] }}</span></div>
                <div class="p-5 text-zinc-900 sm:p-6">
                    <div class="mb-5 flex items-center justify-between"><span class="text-sm font-bold">{{ $slide['label'] }}</span><span class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-700">Aperçu illustratif</span></div>
                    @if($welcomeStep === 1 || $welcomeStep === 2)
                        <div class="grid grid-cols-3 gap-2">
                            @foreach(['Article A', 'Article B', 'Service'] as $index => $name)
                                <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-3"><div class="mb-3 grid h-14 place-items-center rounded-lg bg-violet-100 text-violet-500"><flux:icon :name="$index === 2 ? 'sparkles' : 'cube'" class="size-7" /></div><p class="text-xs font-semibold">{{ $name }}</p><p class="mt-1 text-[10px] text-violet-700">{{ $index === 0 ? '1 000' : '2 500' }} CDF</p></div>
                            @endforeach
                        </div>
                        <div class="mt-4 flex items-center justify-between rounded-xl bg-violet-600 p-4 text-white"><span class="text-xs">{{ $welcomeStep === 1 ? 'Total de la commande' : 'Prix saisi = prix en caisse*' }}</span><strong class="text-sm">1 000 CDF</strong></div>
                        <p class="mt-3 text-[10px] text-zinc-500">* À taux de change et mode de taxe identiques.</p>
                    @elseif($welcomeStep === 3)
                        @foreach(['09:00' => 'Accueil & conseil', '10:30' => 'Prestation réservée', '14:00' => 'Suivi client'] as $hour => $title)
                            <div class="mb-3 flex items-center gap-3 rounded-xl border border-zinc-100 p-3"><span class="text-xs font-bold text-violet-600">{{ $hour }}</span><span class="h-8 w-1 rounded-full bg-violet-300"></span><span class="text-xs font-semibold">{{ $title }}</span><flux:icon.calendar-days class="ml-auto size-5 text-zinc-400" /></div>
                        @endforeach
                    @else
                        <div class="grid grid-cols-3 gap-2 text-center">@foreach(['Ventes', 'Clients', 'Stocks'] as $metric)<div class="rounded-xl bg-violet-50 p-3"><p class="text-[10px] text-zinc-500">{{ $metric }}</p><p class="mt-2 text-xl font-bold text-violet-700">—</p></div>@endforeach</div>
                        <svg viewBox="0 0 360 120" role="img" aria-label="Illustration d’un graphique d’activité" class="mt-5 w-full"><path d="M0 100H360M0 60H360M0 20H360" stroke="#e4e4e7"/><path d="M5 95L55 80L105 89L155 48L205 61L255 32L305 42L355 10V115H5Z" fill="#ede9fe"/><path d="M5 95L55 80L105 89L155 48L205 61L255 32L305 42L355 10" fill="none" stroke="#7c3aed" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <footer class="flex flex-wrap items-center justify-between gap-4 border-t border-zinc-100 px-6 py-5 dark:border-zinc-800 sm:px-10">
        <div class="flex items-center gap-3" aria-label="Étape {{ $welcomeStep + 1 }} sur 5"><span class="text-xs font-medium text-zinc-500">{{ $welcomeStep + 1 }} / 5</span><div class="flex gap-1.5" aria-hidden="true">@for($dot = 0; $dot < 5; $dot++)<span class="h-1.5 rounded-full {{ $dot === $welcomeStep ? 'w-7 bg-violet-600' : 'w-2 bg-zinc-200' }}"></span>@endfor</div></div>
        <div class="flex gap-2">
            @if($welcomeStep > 0)<flux:button wire:click="previousWelcome" wire:loading.attr="disabled" icon="arrow-left">Retour</flux:button>@endif
            <flux:button wire:click="nextWelcome" wire:loading.attr="disabled" variant="primary" color="violet" icon:trailing="arrow-right">{{ $welcomeStep === 4 ? 'Créer mon administrateur' : 'Continuer' }}</flux:button>
        </div>
    </footer>
</section>
