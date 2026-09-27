<div class="relative overflow-hidden">
    <header x-data="{ mobileMenuOpen: false }" @keydown.escape.window="mobileMenuOpen = false" class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#0d163d]/90 text-white shadow-lg shadow-slate-950/10 backdrop-blur-xl">
        <div class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-2 px-3 min-[390px]:px-4 sm:h-20 sm:px-5 lg:px-8">
            <a href="#accueil" class="flex items-center gap-3" aria-label="Yeboox — Accueil">
                <img src="{{ asset('images/brand/yeboox-icon.png') }}" alt="" class="size-10 rounded-xl bg-white p-1.5 shadow-sm sm:size-11 sm:rounded-2xl">
                <span class="hidden text-xl font-black tracking-tight min-[430px]:inline">Yeboox</span>
            </a>
            <nav class="hidden items-center gap-7 text-sm font-semibold text-white/75 lg:flex" aria-label="Navigation principale">
                <a class="transition hover:text-white" href="#modules">Modules</a>
                <a class="transition hover:text-white" href="#solutions">Solutions</a>
                <a class="transition hover:text-white" href="#tarifs">Tarifs</a>
                <a class="transition hover:text-white" href="#partenaires">Partenaires</a>
                <a class="transition hover:text-white" href="#a-propos">Qui sommes-nous ?</a>
                <a class="transition hover:text-white" href="#faq">FAQ</a>
            </nav>
            <div class="flex items-center gap-1.5 sm:gap-3">
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="hidden rounded-full border border-white/20 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/10 sm:inline-flex">WhatsApp</a>
                <button type="button" onclick="window.yebooxTheme.toggle()" class="grid size-9 place-items-center rounded-full border border-white/15 bg-white/8 text-white transition hover:bg-white/15 sm:size-10" aria-label="Changer le thème">
                    <svg class="size-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3V1m0 22v-2m9-9h2M1 12h2m16.36-6.36 1.42-1.42M3.22 20.78l1.42-1.42m14.72 0 1.42 1.42M3.22 3.22l1.42 1.42"/><circle cx="12" cy="12" r="4.5"/></svg>
                    <svg class="hidden size-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.6 15.6A9 9 0 0 1 8.4 3.4 9 9 0 1 0 20.6 15.6Z"/></svg>
                </button>
                <button type="button" wire:click="chooseRequest('appointment')" class="rounded-full bg-[#ffb547] px-3 py-2.5 text-xs font-black text-[#101a46] shadow-lg shadow-amber-500/20 transition hover:-translate-y-0.5 hover:bg-[#ffc66f] sm:px-5 sm:text-sm"><span class="sm:hidden">Échange</span><span class="hidden sm:inline">Prendre rendez-vous</span></button>
                <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" :aria-expanded="mobileMenuOpen" class="grid size-9 place-items-center rounded-full border border-white/15 bg-white/8 lg:hidden" aria-label="Ouvrir le menu">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path x-show="!mobileMenuOpen" d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/><path x-show="mobileMenuOpen" d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
                </button>
            </div>
        </div>
        <nav x-show="mobileMenuOpen" x-transition.origin.top @click.outside="mobileMenuOpen = false" class="absolute inset-x-3 top-[4.85rem] rounded-2xl border border-white/10 bg-[#111b46] p-2 shadow-2xl lg:hidden" aria-label="Navigation mobile">
            @foreach ([['modules', 'Modules'], ['solutions', 'Solutions'], ['tarifs', 'Tarifs'], ['partenaires', 'Partenaires'], ['a-propos', 'Qui sommes-nous ?'], ['faq', 'FAQ']] as [$anchor, $label])
                <a wire:key="mobile-nav-{{ $anchor }}" href="#{{ $anchor }}" @click="mobileMenuOpen = false" class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-bold text-white/80 hover:bg-white/8 hover:text-white"><span>{{ $label }}</span><span aria-hidden="true">→</span></a>
            @endforeach
        </nav>
    </header>

    <main id="contenu">
        <section id="accueil" class="relative isolate min-h-[760px] overflow-hidden bg-[#101a46] pt-18 text-white sm:min-h-[820px] sm:pt-20">
            <div class="absolute inset-0 yb-grid-pattern opacity-35"></div>
            <svg class="yb-orbit absolute -right-36 top-12 size-[620px] opacity-20" viewBox="0 0 620 620" fill="none" aria-hidden="true"><circle cx="310" cy="310" r="222" stroke="url(#orbit)"/><circle cx="310" cy="310" r="142" stroke="white" stroke-dasharray="6 16"/><path d="M90 290c91-103 203-154 337-110 61 20 103 66 119 139" stroke="white" stroke-linecap="round"/><circle cx="477" cy="205" r="12" fill="#ffb547"/><defs><linearGradient id="orbit"><stop stop-color="#fff" stop-opacity="0"/><stop offset=".5" stop-color="#fff"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient></defs></svg>
            <div class="absolute -left-40 top-20 size-[520px] rounded-full bg-blue-600/30 blur-[120px]"></div>
            <div class="absolute -right-32 bottom-0 size-[520px] rounded-full bg-violet-600/30 blur-[120px]"></div>
            <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-5 py-14 sm:py-20 lg:grid-cols-[.92fr_1.08fr] lg:px-8 lg:py-28">
                <div class="max-w-2xl">
                    <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/8 px-4 py-2 text-sm font-bold text-blue-100">
                        <span class="size-2 rounded-full bg-emerald-400 shadow-[0_0_18px_#34d399]"></span>
                        Conçu en Afrique, pour les entreprises africaines
                    </div>
                    <h1 class="text-[clamp(2.65rem,13vw,4.5rem)] font-black leading-[.96] tracking-[-.055em]">Votre commerce.<br><span class="yb-gradient-text">Plus simple.</span><br>Plus puissant.</h1>
                    <p class="mt-7 max-w-xl text-lg leading-8 text-slate-300 sm:text-xl">Yeboox réunit la caisse, le stock, les clients et la comptabilité dans une plateforme rapide, claire et prête à grandir avec vous.</p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <button type="button" wire:click="chooseRequest('demo')" class="group inline-flex items-center justify-center gap-3 rounded-full bg-[#ffb547] px-7 py-4 font-black text-[#101a46] shadow-xl shadow-amber-500/20 transition hover:-translate-y-1 hover:bg-[#ffc66f]">
                            Voir Yeboox en action <span class="transition group-hover:translate-x-1">→</span>
                        </button>
                        <a href="#modules" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/8 px-7 py-4 font-bold text-white transition hover:bg-white/15">Explorer les modules</a>
                    </div>
                    <div class="mt-10 flex flex-wrap gap-x-7 gap-y-3 text-sm font-semibold text-slate-300">
                        <span>✓ Installation accompagnée</span><span>✓ Fonctionne hors-ligne</span><span>✓ Support local</span>
                    </div>
                </div>
                <div class="relative lg:pl-5">
                    <div class="yb-hero-frame overflow-hidden rounded-[2rem] border border-white/15 bg-white/10 p-2 shadow-2xl shadow-black/40">
                        <img src="{{ asset('images/marketing/yeboox-african-retail-pos.png') }}" alt="Commerçante africaine encaissant un client avec une solution de point de vente moderne" class="aspect-[16/10] w-full rounded-[1.55rem] object-cover" fetchpriority="high">
                    </div>
                    <div class="absolute -bottom-7 left-2 rounded-2xl border border-white/15 bg-[#152152]/95 p-3 shadow-2xl backdrop-blur sm:-left-8 sm:p-4">
                        <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-200">Aujourd’hui</p>
                        <p class="mt-1 text-2xl font-black">+24,8% <span class="text-sm font-semibold text-emerald-300">de ventes</span></p>
                    </div>
                    <div class="absolute -right-1 -top-5 rounded-2xl bg-white p-3 text-[#101a46] shadow-2xl sm:-right-5 sm:-top-6 sm:p-4">
                        <p class="text-xs font-bold text-slate-500">SYNCHRONISATION</p>
                        <p class="mt-1 flex items-center gap-2 font-black"><span class="size-2 rounded-full bg-emerald-500"></span> À jour</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="modules" class="relative bg-[#f7f7f2] py-16 dark:bg-[#070b19] sm:py-24">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="grid gap-8 lg:grid-cols-[.8fr_1.2fr] lg:items-end">
                    <div>
                        <p class="yb-kicker">Tout est déjà là</p>
                        <h2 class="mt-4 text-4xl font-black tracking-[-.04em] text-[#101a46] dark:text-white sm:text-5xl">Tous les modules.<br>Une seule équipe.</h2>
                    </div>
                    <p class="max-w-2xl text-lg leading-8 text-slate-600 dark:text-slate-300">De la première vente au bilan, chaque module partage les mêmes données. Plus de doubles saisies, moins d’erreurs et une vision nette de votre activité.</p>
                </div>
                <div class="mt-12 grid gap-px overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-200 shadow-xl shadow-slate-900/5 dark:border-white/10 dark:bg-white/10 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($modules as $moduleKey => $module)
                        <button wire:key="module-{{ $moduleKey }}" type="button" wire:click="showModule('{{ $moduleKey }}')" class="group bg-white p-6 text-left transition hover:bg-[#fff9ee] focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-offset-[-3px] focus-visible:outline-blue-600 dark:bg-[#0d1328] dark:hover:bg-[#121b37] sm:p-7" aria-label="Découvrir le module {{ $module['title'] }}">
                            <div class="flex items-start justify-between gap-5">
                                <span class="grid size-12 place-items-center rounded-2xl bg-blue-50 text-blue-600 transition group-hover:-rotate-3 group-hover:scale-110 dark:bg-blue-500/10 dark:text-blue-300"><flux:icon :name="$module['icon']" class="size-6" /></span>
                                <span class="flex size-9 items-center justify-center rounded-full bg-slate-100 text-lg text-slate-400 transition group-hover:rotate-45 group-hover:bg-[#ffb547] group-hover:text-[#101a46] dark:bg-white/10">↗</span>
                            </div>
                            <p class="mt-7 text-xs font-black tracking-[.16em] text-blue-600 dark:text-blue-400">{{ $module['number'] }}</p>
                            <h3 class="mt-2 text-xl font-black text-[#101a46] dark:text-white">{{ $module['title'] }}</h3>
                            <p class="mt-3 leading-7 text-slate-600 dark:text-slate-400">{{ $module['description'] }}</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-black text-blue-600 dark:text-blue-300">Voir l’interface <span class="transition group-hover:translate-x-1">→</span></span>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="solutions" class="overflow-hidden bg-white py-16 dark:bg-[#0b1022] sm:py-24">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div class="relative">
                        <img src="{{ asset('images/marketing/yeboox-restaurant-pos.png') }}" alt="Restaurant africain utilisant Yeboox au comptoir" loading="lazy" class="aspect-[4/3] w-full rounded-[2rem] object-cover shadow-2xl shadow-slate-900/15">
                        <div class="absolute bottom-5 left-5 right-5 rounded-2xl bg-white/95 p-5 text-[#101a46] shadow-xl backdrop-blur sm:right-auto sm:max-w-xs">
                            <p class="font-black">Chaque vente compte.</p><p class="mt-1 text-sm text-slate-600">Sur place, à emporter ou dans plusieurs établissements.</p>
                        </div>
                    </div>
                    <div>
                        <p class="yb-kicker">Pensé pour le terrain</p>
                        <h2 class="mt-4 text-4xl font-black tracking-[-.04em] text-[#101a46] dark:text-white sm:text-5xl">Rapide à la caisse.<br>Solide en coulisses.</h2>
                        <p class="mt-6 text-lg leading-8 text-slate-600 dark:text-slate-300">Restaurant, boutique, salon, hôtel ou entreprise de services : Yeboox épouse votre façon de travailler et reste fluide pendant les heures de pointe.</p>
                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            @foreach ([['Restez opérationnel', 'Continuez à encaisser même si la connexion internet disparaît.'], ['Décidez avec vos chiffres', 'Visualisez ventes, marges et stocks sans attendre la fin du mois.'], ['Grandissez sans repartir', 'Ajoutez des utilisateurs, caisses et sites au même environnement.'], ['Gardez le contrôle', 'Chaque action sensible est tracée et chaque accès est maîtrisé.']] as [$title, $text])
                                <div wire:key="benefit-{{ $loop->index }}" class="rounded-2xl border border-slate-200 p-5 dark:border-white/10">
                                    <h3 class="font-black text-[#101a46] dark:text-white">{{ $title }}</h3><p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $text }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-16 grid items-center gap-12 sm:mt-24 lg:grid-cols-2">
                    <div class="lg:order-2">
                        <img src="{{ asset('images/marketing/yeboox-business-analytics.png') }}" alt="Entrepreneur africain analysant les performances de son commerce avec Yeboox" loading="lazy" class="aspect-[4/3] w-full rounded-[2rem] object-cover shadow-2xl shadow-slate-900/15">
                    </div>
                    <div class="lg:order-1">
                        <p class="yb-kicker">Le tableau de bord qui vous parle</p>
                        <h2 class="mt-4 text-4xl font-black tracking-[-.04em] text-[#101a46] dark:text-white sm:text-5xl">Vos chiffres deviennent des décisions.</h2>
                        <p class="mt-6 text-lg leading-8 text-slate-600 dark:text-slate-300">Repérez les meilleures ventes, anticipez les ruptures et comparez vos établissements depuis une lecture simple, sans jargon inutile.</p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            @foreach (['Ventes en temps réel', 'Marge & rentabilité', 'Alertes de stock', 'Clôture de caisse', 'Suivi multi-sites'] as $metric)
                                <span wire:key="metric-{{ $loop->index }}" class="rounded-full bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ $metric }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="tarifs" class="bg-[#101a46] py-16 text-white sm:py-24">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="yb-kicker yb-kicker-light">Des tarifs clairs</p>
                    <h2 class="mt-4 text-4xl font-black tracking-[-.04em] sm:text-5xl">Choisissez votre rythme de croissance.</h2>
                    <p class="mt-5 text-lg text-slate-300">Pas de surprise : chaque offre affiche son abonnement et ses frais de mise en service.</p>
                    <div class="mt-8 inline-grid w-full max-w-sm grid-cols-2 rounded-2xl border border-white/15 bg-white/8 p-1.5 sm:rounded-full">
                        <button type="button" wire:click="$set('billing', 'monthly')" @class(['rounded-xl px-3 py-2.5 text-xs font-black transition sm:rounded-full sm:px-5 sm:text-sm', 'bg-white text-[#101a46]' => $billing === 'monthly', 'text-slate-300' => $billing !== 'monthly'])>Mensuel</button>
                        <button type="button" wire:click="$set('billing', 'annual')" @class(['rounded-xl px-3 py-2.5 text-xs font-black transition sm:rounded-full sm:px-5 sm:text-sm', 'bg-[#ffb547] text-[#101a46]' => $billing === 'annual', 'text-slate-300' => $billing !== 'annual'])>Annuel · 2 mois offerts</button>
                    </div>
                </div>
                <div class="mt-14 grid items-stretch gap-6 lg:grid-cols-3">
                    @forelse ($plans as $plan)
                        <article wire:key="plan-{{ $plan->id }}" @class(['relative flex flex-col rounded-[2rem] border p-7 sm:p-8', 'border-[#ffb547] bg-white text-[#101a46] shadow-2xl shadow-black/25 lg:-translate-y-4' => $plan->is_highlighted, 'border-white/15 bg-white/7' => ! $plan->is_highlighted])>
                            @if ($plan->is_highlighted)<span class="absolute right-6 top-0 -translate-y-1/2 rounded-full bg-[#ffb547] px-4 py-2 text-xs font-black uppercase tracking-[.12em] text-[#101a46]">Le plus choisi</span>@endif
                            <p @class(['text-sm font-black uppercase tracking-[.16em]', 'text-blue-600' => $plan->is_highlighted, 'text-blue-200' => ! $plan->is_highlighted])>{{ $plan->eyebrow }}</p>
                            <h3 class="mt-3 text-3xl font-black">{{ $plan->name }}</h3>
                            <p @class(['mt-3 min-h-14 leading-7', 'text-slate-600' => $plan->is_highlighted, 'text-slate-300' => ! $plan->is_highlighted])>{{ $plan->tagline }}</p>
                            <div class="mt-7 flex items-end gap-2"><span class="text-5xl font-black tracking-tight">${{ number_format((float) ($billing === 'monthly' ? $plan->monthly_price : $plan->annual_price / 10), 0, ',', ' ') }}</span><span @class(['pb-1 text-sm', 'text-slate-500' => $plan->is_highlighted, 'text-slate-300' => ! $plan->is_highlighted])>/ mois</span></div>
                            @if ($billing === 'annual')<p class="mt-2 text-sm font-bold text-emerald-500">Facturé ${{ number_format((float) $plan->annual_price, 0, ',', ' ') }} par an</p>@endif
                            <p @class(['mt-2 text-xs', 'text-slate-500' => $plan->is_highlighted, 'text-slate-400' => ! $plan->is_highlighted])>Mise en service : ${{ number_format((float) $plan->setup_fee, 0, ',', ' ') }} une seule fois</p>
                            <ul class="mt-7 flex-1 space-y-3 text-sm">
                                @foreach ($plan->features as $feature)<li wire:key="plan-{{ $plan->id }}-feature-{{ $loop->index }}" class="flex gap-3"><span class="font-black text-emerald-500">✓</span><span>{{ $feature }}</span></li>@endforeach
                            </ul>
                            <button type="button" wire:click="selectPlan({{ $plan->id }})" @class(['mt-8 w-full rounded-full px-5 py-3.5 font-black transition hover:-translate-y-0.5', 'bg-[#101a46] text-white' => $plan->is_highlighted, 'bg-white text-[#101a46]' => ! $plan->is_highlighted])>{{ $plan->cta_label }}</button>
                        </article>
                    @empty
                        <div class="rounded-3xl border border-white/15 bg-white/5 p-8 text-slate-300 lg:col-span-3">Les offres sont en cours de synchronisation. Contactez notre équipe pour recevoir la grille tarifaire.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="partenaires" class="overflow-hidden bg-[#f7f7f2] py-16 dark:bg-[#070b19] sm:py-24">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                    <div><p class="yb-kicker">Galerie partenaires</p><h2 class="mt-3 text-3xl font-black text-[#101a46] dark:text-white sm:text-5xl">Des établissements qui font bouger la ville.</h2></div>
                    <p class="max-w-lg text-slate-600 dark:text-slate-400">Découvrez leurs univers et la façon dont Yeboox accompagne leurs opérations.</p>
                </div>
            </div>
            @if ($partners->isNotEmpty())
                <div class="yb-partner-mask mt-12">
                    <div class="yb-partner-track">
                        @foreach ($partners->concat($partners) as $partner)
                            <button wire:key="partner-slide-{{ $loop->index }}-{{ $partner->id }}" type="button" wire:click="showPartner({{ $partner->id }})" class="group flex w-[270px] shrink-0 items-center gap-4 rounded-[1.4rem] border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-1 hover:border-blue-300 hover:shadow-xl dark:border-white/10 dark:bg-[#10162b] sm:w-[310px] sm:p-5">
                                @if ($partner->logo_path)<img src="{{ asset($partner->logo_path) }}" alt="Logo {{ $partner->name }}" class="size-14 rounded-2xl object-cover">@else<span class="grid size-14 place-items-center rounded-2xl font-black text-white" style="background-color: {{ $partner->brand_color }}">{{ $partner->initials }}</span>@endif
                                <span class="min-w-0"><span class="flex items-center gap-2 font-black text-[#101a46] dark:text-white">{{ $partner->name }} @if($partner->is_verified)<span class="text-blue-500">✓</span>@endif</span><span class="mt-1 block truncate text-sm text-slate-500 dark:text-slate-400">{{ $partner->category }} · Voir la galerie</span></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <section id="a-propos" class="relative overflow-hidden bg-white py-16 dark:bg-[#0b1022] sm:py-24">
            <div class="absolute left-1/2 top-1/2 size-[520px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-500/8 blur-[110px]"></div>
            <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-5 lg:grid-cols-[1.05fr_.95fr] lg:px-8">
                <div>
                    <p class="yb-kicker">Qui sommes-nous ?</p>
                    <h2 class="mt-4 text-4xl font-black tracking-[-.04em] text-[#101a46] dark:text-white sm:text-5xl">Une technologie proche de ceux qui entreprennent.</h2>
                    <p class="mt-6 text-lg leading-8 text-slate-600 dark:text-slate-300">Yeboox est une solution de Yetu Technologie née pour simplifier la gestion quotidienne des entreprises africaines. Nous combinons expertise logicielle, connaissance du terrain et accompagnement humain.</p>
                    <p class="mt-4 leading-7 text-slate-600 dark:text-slate-400">Notre ambition est simple : rendre les outils puissants accessibles aux commerces de toutes tailles, sans exiger une connexion parfaite ni une équipe technique.</p>
                    <button type="button" wire:click="chooseRequest('information')" class="mt-8 rounded-full bg-[#101a46] px-6 py-3.5 font-black text-white transition hover:-translate-y-1 dark:bg-white dark:text-[#101a46]">Rencontrer notre équipe →</button>
                </div>
                <div class="yb-bento grid grid-cols-2 gap-4">
                    <div class="col-span-2 rounded-[2rem] bg-[#101a46] p-7 text-white">
                        <svg viewBox="0 0 240 80" class="h-16 w-full" fill="none" aria-hidden="true"><path d="M6 58C42 10 74 74 112 28s74 35 122-12" stroke="url(#story)" stroke-width="5" stroke-linecap="round"/><circle cx="112" cy="28" r="8" fill="#ffb547"/><defs><linearGradient id="story"><stop stop-color="#3154f4"/><stop offset=".5" stop-color="#fff"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs></svg>
                        <p class="mt-5 text-2xl font-black">Construire ici. Grandir partout.</p>
                    </div>
                    <div class="rounded-[2rem] bg-[#ffb547] p-6 text-[#101a46]"><p class="text-4xl font-black">100%</p><p class="mt-2 text-sm font-bold">pensé pour le terrain</p></div>
                    <div class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6 dark:border-white/10 dark:bg-white/5"><p class="text-4xl font-black text-blue-600">3</p><p class="mt-2 text-sm font-bold text-slate-600 dark:text-slate-300">expériences : Web, Desktop, Mobile</p></div>
                </div>
            </div>
        </section>

        <section id="faq" class="bg-[#f7f7f2] py-16 dark:bg-[#070b19] sm:py-24">
            <div class="mx-auto grid max-w-6xl gap-12 px-5 lg:grid-cols-[.7fr_1.3fr] lg:px-8">
                <div><p class="yb-kicker">FAQ</p><h2 class="mt-4 text-4xl font-black tracking-[-.04em] text-[#101a46] dark:text-white">Vos questions,<br>nos réponses.</h2><p class="mt-5 leading-7 text-slate-600 dark:text-slate-400">Vous ne trouvez pas votre réponse ? Ouvrez l’échange avec notre équipe.</p></div>
                <div class="space-y-3">
                    @foreach ([
                        ['Yeboox fonctionne-t-il sans connexion internet ?', 'Oui. Le point de vente Desktop continue d’enregistrer vos ventes hors-ligne et synchronise les données dès que la connexion revient.'],
                        ['Quelle formule choisir pour commencer ?', 'Essentiel correspond à un premier point de vente. Pro ajoute la comptabilité, la fidélité et les rapports. Réseau convient aux entreprises multi-sites.'],
                        ['Puis-je utiliser plusieurs devises ?', 'Oui. Yeboox accompagne les opérations en USD et en CDF avec un taux de change configurable selon votre organisation.'],
                        ['Mes données sont-elles sécurisées ?', 'Les rôles, permissions, journaux d’activité et sauvegardes permettent de garder le contrôle sur les accès et les opérations sensibles.'],
                        ['Combien de temps prend l’installation ?', 'Après l’échange de cadrage, notre équipe prépare votre environnement, importe les données utiles et accompagne vos équipes au démarrage.'],
                    ] as [$question, $answer])
                        <details wire:key="faq-{{ $loop->index }}" class="group rounded-2xl border border-slate-200 bg-white p-1 dark:border-white/10 dark:bg-white/5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-4 py-4 font-black text-[#101a46] dark:text-white sm:gap-5 sm:px-5 sm:py-5"><span>{{ $question }}</span><span class="grid size-8 shrink-0 place-items-center rounded-full bg-slate-100 text-xl transition group-open:rotate-45 dark:bg-white/10">+</span></summary>
                            <p class="px-4 pb-5 leading-7 text-slate-600 dark:text-slate-300 sm:px-5 sm:pr-16">{{ $answer }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="demande" class="bg-white py-16 dark:bg-[#0b1022] sm:py-24">
            <div class="mx-auto grid max-w-7xl gap-12 px-5 lg:grid-cols-[.8fr_1.2fr] lg:px-8">
                <div>
                    <p class="yb-kicker">Parlons de votre activité</p>
                    <h2 class="mt-4 text-4xl font-black tracking-[-.04em] text-[#101a46] dark:text-white sm:text-5xl">Votre prochaine vente commence ici.</h2>
                    <p class="mt-6 text-lg leading-8 text-slate-600 dark:text-slate-300">Réservez un échange, demandez une démonstration ou commandez votre offre. Notre équipe vous recontacte selon le canal que vous préférez.</p>
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="mt-8 inline-flex items-center gap-3 rounded-full bg-[#20b45a] px-6 py-4 font-black text-white shadow-lg shadow-emerald-600/20 transition hover:-translate-y-1 hover:bg-[#169c4b]">
                        <span class="text-xl">◉</span> Discuter maintenant sur WhatsApp
                    </a>
                    <p class="mt-4 text-sm text-slate-500">Une question rapide ? Écrivez directement à l’équipe commerciale.</p>
                </div>

                <div class="rounded-[2rem] border border-slate-200 bg-[#fbfbf8] p-6 shadow-xl shadow-slate-900/5 dark:border-white/10 dark:bg-white/5 sm:p-9">
                    @if ($confirmationNumber)
                        <div class="flex min-h-[520px] flex-col items-center justify-center text-center" role="status">
                            <span class="flex size-18 items-center justify-center rounded-full bg-emerald-100 text-3xl text-emerald-700">✓</span>
                            <h3 class="mt-6 text-3xl font-black text-[#101a46] dark:text-white">Votre demande est bien arrivée.</h3>
                            <p class="mt-3 max-w-md leading-7 text-slate-600 dark:text-slate-300">Un conseiller Yeboox vous recontactera rapidement. Gardez cette référence : <strong>{{ $confirmationNumber }}</strong>.</p>
                            <button type="button" wire:click="$set('confirmationNumber', null)" class="mt-7 rounded-full border border-slate-300 px-5 py-3 font-bold dark:border-white/20">Faire une autre demande</button>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach ([['demo', 'Démo'], ['appointment', 'Rendez-vous'], ['order', 'Commander'], ['information', 'Information']] as [$type, $label])
                                <button wire:key="request-type-{{ $type }}" type="button" wire:click="chooseRequest('{{ $type }}')" @class(['rounded-xl px-3 py-3 text-sm font-black transition', 'bg-[#101a46] text-white' => $requestType === $type, 'bg-white text-slate-600 hover:bg-slate-100 dark:bg-white/8 dark:text-slate-300' => $requestType !== $type])>{{ $label }}</button>
                            @endforeach
                        </div>
                        <form wire:submit="submitRequest" class="mt-8 space-y-5">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <flux:input label="Entreprise" wire:model="companyName" placeholder="Nom de votre entreprise" required />
                                <flux:input label="Votre nom" wire:model="contactName" placeholder="Nom complet" required />
                                <flux:input type="email" label="E-mail" wire:model="email" placeholder="vous@entreprise.com" required />
                                <flux:input type="tel" label="Téléphone / WhatsApp" wire:model="phone" placeholder="+243…" required />
                                <flux:input label="Ville" wire:model="city" placeholder="Kinshasa" required />
                                <flux:select label="Pays" wire:model="country">
                                    <flux:select.option value="RDC">RD Congo</flux:select.option><flux:select.option value="Congo">Congo-Brazzaville</flux:select.option><flux:select.option value="Côte d’Ivoire">Côte d’Ivoire</flux:select.option><flux:select.option value="Cameroun">Cameroun</flux:select.option><flux:select.option value="Sénégal">Sénégal</flux:select.option><flux:select.option value="Autre">Autre pays</flux:select.option>
                                </flux:select>
                                <flux:select label="Type d’activité" wire:model="businessType">
                                    <flux:select.option value="restaurant">Restaurant / Bar</flux:select.option><flux:select.option value="commerce">Commerce / Boutique</flux:select.option><flux:select.option value="beaute">Beauté / Bien-être</flux:select.option><flux:select.option value="services">Services</flux:select.option><flux:select.option value="hotel">Hôtel</flux:select.option><flux:select.option value="autre">Autre</flux:select.option>
                                </flux:select>
                                <flux:input type="number" min="1" max="999" label="Nombre de sites" wire:model="locationsCount" required />
                            </div>

                            @if ($requestType === 'appointment')
                                <div class="grid gap-5 rounded-2xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-500/20 dark:bg-blue-500/10 sm:grid-cols-2">
                                    <flux:input type="date" min="{{ now()->toDateString() }}" label="Date souhaitée" wire:model="appointmentDate" required />
                                    <flux:input type="time" label="Heure souhaitée" wire:model="appointmentTime" required />
                                    <p class="text-sm text-blue-700 dark:text-blue-300 sm:col-span-2">Horaire indicatif, heure de Kinshasa. L’équipe confirmera le créneau avec vous.</p>
                                </div>
                            @endif

                            @if ($requestType === 'order')
                                <flux:select label="Offre choisie" wire:model="planId">
                                    <flux:select.option value="">Je souhaite être conseillé</flux:select.option>
                                    @foreach ($plans as $plan)<flux:select.option wire:key="form-plan-{{ $plan->id }}" value="{{ $plan->id }}">{{ $plan->name }} — ${{ number_format((float) $plan->monthly_price, 0) }}/mois</flux:select.option>@endforeach
                                </flux:select>
                            @endif

                            <flux:select label="Comment vous recontacter ?" wire:model="preferredContact">
                                <flux:select.option value="whatsapp">WhatsApp</flux:select.option><flux:select.option value="telephone">Téléphone</flux:select.option><flux:select.option value="email">E-mail</flux:select.option>
                            </flux:select>
                            <flux:textarea label="Parlez-nous de votre besoin" wire:model="message" rows="4" placeholder="Votre activité, vos priorités, vos questions…" />
                            <div class="hidden" aria-hidden="true"><label>Site web<input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label></div>
                            <flux:checkbox wire:model="consent" label="J’accepte que Yeboox utilise ces informations pour répondre à ma demande." />
                            <flux:error name="consent" />
                            <button type="submit" wire:loading.attr="disabled" class="flex w-full items-center justify-center gap-3 rounded-full bg-[#3154f4] px-6 py-4 font-black text-white shadow-lg shadow-blue-600/20 transition hover:-translate-y-0.5 hover:bg-[#2544d8] disabled:cursor-wait disabled:opacity-70">
                                <span wire:loading.remove>{{ $requestType === 'appointment' ? 'Demander ce rendez-vous' : ($requestType === 'order' ? 'Envoyer ma commande' : 'Envoyer ma demande') }}</span>
                                <span wire:loading>Envoi en cours…</span>
                            </button>
                            <p class="text-center text-xs leading-5 text-slate-500">Vos informations restent confidentielles et servent uniquement au traitement de votre demande.</p>
                        </form>
                    @endif
                </div>
            </div>
        </section>
    </main>

    @if ($activeModule)
        <div wire:key="module-modal-{{ $selectedModule }}" class="fixed inset-0 z-[90] flex items-end justify-center bg-slate-950/75 backdrop-blur-md sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="module-title">
            <button type="button" wire:click="closeModule" class="absolute inset-0" aria-label="Fermer la présentation du module"></button>
            <article class="relative max-h-[94dvh] w-full overflow-y-auto rounded-t-[2rem] bg-white shadow-2xl dark:bg-[#0b1022] sm:max-w-6xl sm:rounded-[2rem]">
                <div class="sticky top-0 z-20 flex items-center justify-between border-b border-slate-200 bg-white/90 px-5 py-3 backdrop-blur-xl dark:border-white/10 dark:bg-[#0b1022]/90 sm:hidden">
                    <p class="text-sm font-black text-[#101a46] dark:text-white">{{ $activeModule['title'] }}</p>
                    <button type="button" wire:click="closeModule" class="grid size-9 place-items-center rounded-full bg-slate-100 text-xl text-slate-500 dark:bg-white/10 dark:text-white" aria-label="Fermer">×</button>
                </div>
                <button type="button" wire:click="closeModule" class="absolute right-5 top-5 z-20 hidden size-11 place-items-center rounded-full bg-slate-950/70 text-2xl text-white shadow-lg backdrop-blur transition hover:rotate-90 sm:grid" aria-label="Fermer">×</button>

                <div class="grid lg:grid-cols-[.68fr_1.32fr]">
                    <div class="flex flex-col justify-center p-6 sm:p-9 lg:p-11">
                        <div class="flex items-center gap-3">
                            <span class="grid size-12 place-items-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300"><flux:icon :name="$activeModule['icon']" class="size-6" /></span>
                            <span class="text-xs font-black tracking-[.18em] text-blue-600 dark:text-blue-300">MODULE {{ $activeModule['number'] }}</span>
                        </div>
                        <p class="mt-7 text-xs font-black uppercase tracking-[.16em] text-amber-600 dark:text-amber-400">{{ $activeModule['eyebrow'] }}</p>
                        <h2 id="module-title" class="mt-3 text-3xl font-black tracking-[-.035em] text-[#101a46] dark:text-white sm:text-4xl">{{ $activeModule['title'] }}</h2>
                        <p class="mt-4 leading-7 text-slate-600 dark:text-slate-300">{{ $activeModule['description'] }}</p>
                        <ul class="mt-7 space-y-3">
                            @foreach ($activeModule['features'] as $feature)
                                <li wire:key="active-module-feature-{{ $loop->index }}" class="flex items-start gap-3 text-sm font-semibold text-slate-700 dark:text-slate-200"><span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-emerald-100 text-[10px] font-black text-emerald-700">✓</span><span>{{ $feature }}</span></li>
                            @endforeach
                        </ul>
                        <button type="button" wire:click="chooseRequest('demo')" class="mt-8 inline-flex w-full items-center justify-center rounded-full bg-[#3154f4] px-6 py-3.5 font-black text-white shadow-lg shadow-blue-600/20 transition hover:-translate-y-0.5 sm:w-auto">Voir ce module en démo</button>
                    </div>
                    <div class="flex items-center overflow-hidden bg-gradient-to-br from-[#e9efff] via-white to-[#fff0d8] p-4 dark:from-[#101b3d] dark:via-[#11162b] dark:to-[#281d2a] sm:p-8 lg:p-11">
                        <x-marketing.module-mockup :type="$activeModule['mockup']" :title="$activeModule['title']" />
                    </div>
                </div>
            </article>
        </div>
    @endif

    @if ($showBookingPanel)
        <div class="fixed inset-0 z-[70]" role="dialog" aria-modal="true" aria-labelledby="booking-title">
            <button type="button" wire:click="closeBookingPanel" class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" aria-label="Fermer le panneau"></button>
            <aside class="yb-offcanvas absolute inset-y-0 right-0 w-full overflow-y-auto border-l border-white/10 bg-white p-6 shadow-2xl dark:bg-[#090e20] sm:max-w-2xl sm:p-9">
                <div class="flex items-start justify-between gap-5">
                    <div><p class="yb-kicker">Échange commercial</p><h2 id="booking-title" class="mt-3 text-3xl font-black text-[#101a46] dark:text-white">Réservez votre échange.</h2><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Réponse rapide, sans engagement.</p></div>
                    <button type="button" wire:click="closeBookingPanel" class="grid size-11 shrink-0 place-items-center rounded-full border border-slate-200 text-2xl text-slate-500 transition hover:rotate-90 dark:border-white/10" aria-label="Fermer">×</button>
                </div>
                @if ($confirmationNumber)
                    <div class="flex min-h-[70vh] flex-col items-center justify-center text-center" role="status"><span class="grid size-20 place-items-center rounded-full bg-emerald-100 text-3xl text-emerald-700">✓</span><h3 class="mt-6 text-3xl font-black text-[#101a46] dark:text-white">Votre demande est bien arrivée.</h3><p class="mt-3 max-w-md leading-7 text-slate-600 dark:text-slate-300">Un conseiller vous recontactera rapidement. Référence : <strong>{{ $confirmationNumber }}</strong>.</p><button type="button" wire:click="closeBookingPanel" class="mt-7 rounded-full bg-[#101a46] px-6 py-3 font-black text-white dark:bg-white dark:text-[#101a46]">Fermer</button></div>
                @else
                    <div class="mt-7 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach ([['demo', 'Démo'], ['appointment', 'Rendez-vous'], ['order', 'Commander'], ['information', 'Information']] as [$type, $label])
                            <button wire:key="panel-type-{{ $type }}" type="button" wire:click="chooseRequest('{{ $type }}')" @class(['rounded-xl px-3 py-3 text-sm font-black transition', 'bg-[#101a46] text-white dark:bg-blue-500' => $requestType === $type, 'bg-slate-100 text-slate-600 dark:bg-white/8 dark:text-slate-300' => $requestType !== $type])>{{ $label }}</button>
                        @endforeach
                    </div>
                    <form wire:submit="submitRequest" class="mt-7 space-y-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <flux:input label="Entreprise" wire:model="companyName" required />
                            <flux:input label="Votre nom" wire:model="contactName" required />
                            <flux:input type="email" label="E-mail" wire:model="email" required />
                            <flux:input type="tel" label="Téléphone / WhatsApp" wire:model="phone" placeholder="+243…" required />
                            <flux:input label="Ville" wire:model="city" required />
                            <flux:input type="number" min="1" max="999" label="Nombre de sites" wire:model="locationsCount" required />
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <flux:select label="Pays" wire:model="country"><flux:select.option value="RDC">RD Congo</flux:select.option><flux:select.option value="Congo">Congo-Brazzaville</flux:select.option><flux:select.option value="Côte d’Ivoire">Côte d’Ivoire</flux:select.option><flux:select.option value="Cameroun">Cameroun</flux:select.option><flux:select.option value="Sénégal">Sénégal</flux:select.option><flux:select.option value="Autre">Autre pays</flux:select.option></flux:select>
                            <flux:select label="Activité" wire:model="businessType"><flux:select.option value="restaurant">Restaurant / Bar</flux:select.option><flux:select.option value="commerce">Commerce / Boutique</flux:select.option><flux:select.option value="beaute">Beauté / Bien-être</flux:select.option><flux:select.option value="services">Services</flux:select.option><flux:select.option value="hotel">Hôtel</flux:select.option><flux:select.option value="autre">Autre</flux:select.option></flux:select>
                        </div>
                        @if ($requestType === 'appointment')
                            <div class="grid gap-5 rounded-2xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-500/20 dark:bg-blue-500/10 sm:grid-cols-2"><flux:input type="date" min="{{ now()->toDateString() }}" label="Date souhaitée" wire:model="appointmentDate" required /><flux:input type="time" label="Heure souhaitée" wire:model="appointmentTime" required /><p class="text-sm text-blue-700 dark:text-blue-300 sm:col-span-2">Heure de Kinshasa. Le créneau sera confirmé par notre équipe.</p></div>
                        @endif
                        @if ($requestType === 'order')
                            <flux:select label="Offre choisie" wire:model="planId"><flux:select.option value="">Je souhaite être conseillé</flux:select.option>@foreach ($plans as $plan)<flux:select.option wire:key="panel-plan-{{ $plan->id }}" value="{{ $plan->id }}">{{ $plan->name }} — ${{ number_format((float) $plan->monthly_price, 0) }}/mois</flux:select.option>@endforeach</flux:select>
                        @endif
                        <flux:select label="Canal préféré" wire:model="preferredContact"><flux:select.option value="whatsapp">WhatsApp</flux:select.option><flux:select.option value="telephone">Téléphone</flux:select.option><flux:select.option value="email">E-mail</flux:select.option></flux:select>
                        <flux:textarea label="Votre besoin" wire:model="message" rows="3" placeholder="Quelques mots sur votre projet…" />
                        <div class="hidden" aria-hidden="true"><label>Site web<input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label></div>
                        <flux:checkbox wire:model="consent" label="J’accepte d’être recontacté au sujet de ma demande." /><flux:error name="consent" />
                        <button type="submit" wire:loading.attr="disabled" class="flex w-full items-center justify-center rounded-full bg-[#3154f4] px-6 py-4 font-black text-white transition hover:-translate-y-0.5 disabled:opacity-60"><span wire:loading.remove>{{ $requestType === 'appointment' ? 'Demander ce rendez-vous' : ($requestType === 'order' ? 'Envoyer ma commande' : 'Envoyer ma demande') }}</span><span wire:loading>Envoi en cours…</span></button>
                    </form>
                @endif
            </aside>
        </div>
    @endif

    @if ($selectedPartner)
        @php($gallery = array_slice($selectedPartner->gallery_images ?? [], 0, 3))
        <div class="fixed inset-0 z-[80] grid place-items-center overflow-y-auto bg-slate-950/75 p-4 backdrop-blur-md" role="dialog" aria-modal="true" aria-labelledby="partner-title" x-data="{ slide: 0 }">
            <button type="button" wire:click="closePartner" class="absolute inset-0" aria-label="Fermer la fiche partenaire"></button>
            <article class="relative w-full max-w-4xl overflow-hidden rounded-[2rem] bg-white shadow-2xl dark:bg-[#0b1022]">
                <button type="button" wire:click="closePartner" class="absolute right-4 top-4 z-20 grid size-11 place-items-center rounded-full bg-slate-950/60 text-2xl text-white backdrop-blur transition hover:rotate-90" aria-label="Fermer">×</button>
                @if ($gallery !== [])
                    <div class="relative aspect-[16/9] overflow-hidden bg-slate-100 dark:bg-slate-900">
                        @foreach ($gallery as $image)<img wire:key="partner-gallery-{{ $loop->index }}" x-show="slide === {{ $loop->index }}" x-transition.opacity src="{{ asset($image) }}" alt="{{ $selectedPartner->name }} — image {{ $loop->iteration }}" class="absolute inset-0 size-full object-cover">@endforeach
                        <div class="absolute inset-x-0 bottom-5 flex justify-center gap-2">@foreach ($gallery as $image)<button wire:key="partner-dot-{{ $loop->index }}" type="button" @click="slide = {{ $loop->index }}" :class="slide === {{ $loop->index }} ? 'w-8 bg-white' : 'w-2 bg-white/55'" class="h-2 rounded-full transition-all" aria-label="Afficher l’image {{ $loop->iteration }}"></button>@endforeach</div>
                        @if (count($gallery) > 1)<button type="button" @click="slide = (slide - 1 + {{ count($gallery) }}) % {{ count($gallery) }}" class="absolute left-4 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-slate-950/50 text-xl text-white" aria-label="Image précédente">‹</button><button type="button" @click="slide = (slide + 1) % {{ count($gallery) }}" class="absolute right-4 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-slate-950/50 text-xl text-white" aria-label="Image suivante">›</button>@endif
                    </div>
                @endif
                <div class="grid gap-6 p-7 sm:grid-cols-[auto_1fr] sm:p-9">@if($selectedPartner->logo_path)<img src="{{ asset($selectedPartner->logo_path) }}" alt="Logo {{ $selectedPartner->name }}" class="size-20 rounded-2xl object-cover">@endif<div><p class="text-xs font-black uppercase tracking-[.16em] text-blue-600">{{ $selectedPartner->category }}</p><h2 id="partner-title" class="mt-2 text-3xl font-black text-[#101a46] dark:text-white">{{ $selectedPartner->name }}</h2><p class="mt-3 leading-7 text-slate-600 dark:text-slate-300">{{ $selectedPartner->description ?: $selectedPartner->tagline }}</p>@if($selectedPartner->website_url)<a href="{{ $selectedPartner->website_url }}" target="_blank" rel="noopener" class="mt-5 inline-flex font-black text-blue-600">Visiter le partenaire →</a>@endif</div></div>
            </article>
        </div>
    @endif

    <footer class="border-t border-white/10 bg-[#080d24] py-12 text-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-8 px-5 lg:flex-row lg:items-end lg:justify-between lg:px-8">
            <div><div class="flex items-center gap-3"><img src="{{ asset('images/brand/yeboox-icon.png') }}" alt="" class="size-10 rounded-xl bg-white p-1.5"><span class="text-xl font-black">Yeboox</span></div><p class="mt-4 max-w-md text-sm leading-6 text-slate-400">La plateforme de gestion qui comprend le rythme, les ambitions et les réalités des entreprises africaines.</p></div>
            <div class="flex flex-wrap gap-x-7 gap-y-3 text-sm font-semibold text-slate-300"><a href="#modules">Modules</a><a href="#tarifs">Tarifs</a><a href="#partenaires">Partenaires</a><a href="#a-propos">À propos</a><a href="#faq">FAQ</a><a href="mailto:{{ config('services.yeboox.sales_email') }}">{{ config('services.yeboox.sales_email') }}</a></div>
        </div>
        <div class="mx-auto mt-10 max-w-7xl border-t border-white/10 px-5 pt-7 text-xs text-slate-500 lg:px-8">© {{ now()->year }} Yeboox, une solution Yetu Technologie. Tous droits réservés.</div>
    </footer>

    <div class="fixed bottom-5 right-5 z-50 flex flex-col items-end gap-2" x-data="{ open: false }" @keydown.escape.window="open = false">
        <div x-show="open" x-transition.origin.bottom.right class="flex flex-col items-end gap-2">
            <a href="#modules" @click="open = false" class="yb-float-action"><span>Voir les modules</span><span class="yb-float-icon"><flux:icon.squares-2x2 class="size-5" /></span></a>
            <a href="#tarifs" @click="open = false" class="yb-float-action"><span>Comparer les tarifs</span><span class="yb-float-icon"><flux:icon.credit-card class="size-5" /></span></a>
            <button type="button" wire:click="chooseRequest('demo')" @click="open = false" class="yb-float-action"><span>Demander une démo</span><span class="yb-float-icon"><flux:icon.play class="size-5" /></span></button>
            <button type="button" wire:click="chooseRequest('appointment')" @click="open = false" class="yb-float-action"><span>Réserver un échange</span><span class="yb-float-icon"><flux:icon.calendar-days class="size-5" /></span></button>
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" @click="open = false" class="yb-float-action"><span>WhatsApp</span><span class="yb-float-icon bg-emerald-500! text-white!"><flux:icon.chat-bubble-left-right class="size-5" /></span></a>
        </div>
        <button type="button" @click="open = !open" :aria-expanded="open" class="grid size-15 place-items-center rounded-full bg-[#3154f4] text-white shadow-2xl shadow-blue-950/30 transition hover:scale-105" aria-label="Ouvrir les actions rapides">
            <svg :class="open && 'rotate-45'" class="size-6 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
        </button>
    </div>
</div>
