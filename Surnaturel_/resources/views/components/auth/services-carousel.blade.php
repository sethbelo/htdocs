{{--
    Carrousel des services de l’entreprise (écran de connexion).
    Chaque slide affiche une illustration SVG ; si une photo existe dans public/images/services/{clé}.jpg|webp|png,
    elle est utilisée à la place (effet « Ken Burns »).
--}}
@php
    $slides = [
        ['key' => 'printing', 'bg' => '#1e1b4b', 'accent' => 'bg-cyan-400', 'eyebrow' => 'Imprimerie', 'title' => 'Impression offset & numérique',
            'text' => 'Cartes de visite, flyers, brochures, registres et affiches : vos commandes sont suivies machine par machine, du BAT validé au contrôle qualité.',
            'chips' => ['Quadrichromie', 'Petits et grands tirages', 'Contrôle qualité']],
        ['key' => 'large-format', 'bg' => '#1c1917', 'accent' => 'bg-orange-400', 'eyebrow' => 'Grand format', 'title' => 'Bâches, enseignes & affichage',
            'text' => 'Bâches, roll-up, stickers et habillage de façades pour être vu partout dans la ville, avec pose par nos équipes.',
            'chips' => ['Bâche 510 g', 'Roll-up', 'Pose sur site']],
        ['key' => 'design', 'bg' => '#0f172a', 'accent' => 'bg-pink-500', 'eyebrow' => 'Studio graphique', 'title' => 'Création graphique & bon à tirer',
            'text' => 'Logos, maquettes et identité visuelle : nos graphistes proposent, corrigent et verrouillent le BAT avant tout tirage.',
            'chips' => ['Maquettes', 'Corrections illimitées', 'BAT verrouillé']],
        ['key' => 'shop', 'bg' => '#422006', 'accent' => 'bg-yellow-400', 'eyebrow' => 'Boutique', 'title' => 'Fournitures scolaires & bureau',
            'text' => 'Cahiers, stylos, classeurs, papier et consommables en boutique : paiement rapide, ticket, carte de fidélité et Mobile Money.',
            'chips' => ['Rentrée scolaire', 'Mobile Money', 'Carte fidélité']],
        ['key' => 'finishing', 'bg' => '#134e4a', 'accent' => 'bg-emerald-400', 'eyebrow' => 'Finition', 'title' => 'Découpe, reliure & plastification',
            'text' => 'Massicot, reliure, pelliculage et plastification pour des documents prêts à être remis : rapports, menus, badges et cartes.',
            'chips' => ['Reliure', 'Pelliculage', 'Découpe à façon']],
        ['key' => 'delivery', 'bg' => '#172554', 'accent' => 'bg-indigo-400', 'eyebrow' => 'Livraison', 'title' => 'Livraison rapide dans toute la ville',
            'text' => 'Retrait en boutique ou livraison à moto jusqu’à votre bureau, avec suivi de la commande et preuve de remise signée.',
            'chips' => ['Suivi en temps réel', 'Preuve de livraison', 'Retrait au siège']],
    ];

    foreach ($slides as &$slide) {
        $slide['photo'] = collect(['jpg', 'webp', 'png'])
            ->map(fn (string $extension): string => "images/services/{$slide['key']}.{$extension}")
            ->first(fn (string $path): bool => file_exists(public_path($path)));
    }
    unset($slide);
@endphp

<div x-data="serviceCarousel({ count: {{ count($slides) }}, interval: 6500 })"
    x-on:mouseenter="pause()" x-on:mouseleave="resume()"
    x-on:keydown.left.prevent="prev()" x-on:keydown.right.prevent="next()"
    x-on:pointerdown="swipeStart($event)" x-on:pointerup="swipeEnd($event)"
    tabindex="0" role="region" aria-roledescription="carrousel" aria-label="Nos services"
    {{ $attributes->class('group relative isolate h-full w-full touch-pan-y select-none overflow-hidden outline-none') }}>

    {{-- Slides --}}
    @foreach ($slides as $index => $slide)
        <div class="absolute inset-0 transition-all duration-1000 ease-out" style="background: radial-gradient(120% 80% at 50% 0%, rgb(255 255 255 / .08), transparent 60%), {{ $slide['bg'] }}"
            :class="current === {{ $index }} ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'"
            role="group" aria-roledescription="diapositive" aria-label="{{ $index + 1 }} sur {{ count($slides) }} : {{ $slide['title'] }}"
            :aria-hidden="current !== {{ $index }}" wire:key="service-slide-{{ $slide['key'] }}">
            @if ($slide['photo'])
                <img src="{{ asset($slide['photo']) }}" alt="{{ $slide['title'] }}" class="h-full w-full object-cover" :class="current === {{ $index }} && 'erp-kenburns'" loading="{{ $index ? 'lazy' : 'eager' }}">
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-black/10"></div>
            @else
                <div class="erp-scene absolute inset-x-0 top-0 h-[78%] sm:h-[74%] md:h-[62%] lg:h-[64%]" :class="current === {{ $index }} && 'erp-kenburns'">
                    @include('components.auth.scenes.'.$slide['key'])
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
            @endif
        </div>
    @endforeach

    {{-- En-tête : marque + compteur --}}
    <div class="absolute inset-x-0 top-0 z-20 flex items-center justify-between p-4 sm:p-5">
        <div class="flex items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-2.5 py-1.5 text-white backdrop-blur-xl sm:px-3 sm:py-2">
            <x-app-logo class="size-7" />
            <div class="leading-tight">
                <div class="text-sm font-semibold">{{ \App\Models\Setting::get('company.name', 'ERP Imprimerie') }}</div>
                <div class="hidden text-[11px] text-white/70 sm:block">Imprimerie · Boutique · Livraison</div>
            </div>
        </div>
        <div class="rounded-full border border-white/15 bg-black/25 px-3 py-1 text-xs font-medium tabular-nums text-white backdrop-blur" aria-live="polite">
            <span x-text="String(current + 1).padStart(2, '0')"></span> / {{ str_pad((string) count($slides), 2, '0', STR_PAD_LEFT) }}
        </div>
    </div>

    {{-- Carte de texte (verre) : change à chaque slide --}}
    <div class="absolute inset-x-0 bottom-0 z-20 p-3 sm:p-5 lg:p-6">
        <div class="relative rounded-2xl border border-white/15 bg-black/25 p-3.5 text-white shadow-2xl backdrop-blur-xl sm:rounded-3xl sm:bg-white/10 sm:p-6">
            <div class="relative min-h-[3.2rem] sm:min-h-[8.5rem] lg:min-h-[9.5rem]">
                @foreach ($slides as $index => $slide)
                    <div x-show="current === {{ $index }}" @if ($index) x-cloak @endif
                        x-transition:enter="transition duration-700 ease-out delay-150" x-transition:enter-start="opacity-0 translate-y-3 blur-sm" x-transition:enter-end="opacity-100 translate-y-0 blur-0"
                        x-transition:leave="transition duration-200 ease-in absolute inset-0" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[.18em] text-white/80">
                            <span class="h-1.5 w-6 rounded-full {{ $slide['accent'] }}"></span>{{ $slide['eyebrow'] }}
                        </div>
                        <h2 class="mt-1 text-base font-semibold sm:mt-1.5 sm:text-2xl leading-tight tracking-tight  lg:text-3xl">{{ $slide['title'] }}</h2>
                        <p class="mt-2 hidden max-w-xl text-sm leading-relaxed text-white/80 sm:block lg:text-[15px]">{{ $slide['text'] }}</p>
                        <div class="mt-3 hidden flex-wrap gap-1.5 sm:flex">
                            @foreach ($slide['chips'] as $chip)
                                <span class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-xs text-white/90">{{ $chip }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-2 flex items-center gap-3 sm:mt-5">
                <div class="flex flex-1 gap-1.5" role="tablist" aria-label="Choisir un service">
                    @foreach ($slides as $index => $slide)
                        <button type="button" x-on:click="go({{ $index }})" role="tab" :aria-selected="current === {{ $index }}" aria-label="{{ $slide['title'] }}"
                            class="relative h-1.5 flex-1 overflow-hidden rounded-full bg-white/25 transition hover:bg-white/40">
                            <span class="absolute inset-y-0 left-0 rounded-full bg-white"
                                :style="`width: ${current > {{ $index }} ? 100 : (current === {{ $index }} ? progress : 0)}%`"></span>
                        </button>
                    @endforeach
                </div>
                <div class="flex gap-1.5">
                    <button type="button" x-on:click="prev()" class="flex size-8 items-center sm:size-9 justify-center rounded-full border border-white/20 bg-white/10 text-white transition hover:bg-white/25" aria-label="Service précédent">
                        <flux:icon.chevron-left variant="micro" />
                    </button>
                    <button type="button" x-on:click="next()" class="flex size-8 items-center sm:size-9 justify-center rounded-full border border-white/20 bg-white/10 text-white transition hover:bg-white/25" aria-label="Service suivant">
                        <flux:icon.chevron-right variant="micro" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
