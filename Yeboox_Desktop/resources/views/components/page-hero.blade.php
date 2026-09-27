{{--
    En-tête visuel partagé par les pages métier.

    Les textes, les icônes Flux et l'illustration SVG sont fournis par chaque page.
    Le slot « actions » reste optionnel afin de conserver la même structure lorsque
    le module ne possède aucun bouton principal.
--}}
@props([
    'title',
    'eyebrow',
    'description',
    'icon' => 'squares-2x2',
    'badgeIcon' => 'sparkles',
    'illustration' => 'dashboard',
])

{{-- Carte externe : normalise le rayon, l'ombre et le débordement de tous les en-têtes. --}}
<flux:card {{ $attributes->class('overflow-hidden p-0! shadow-sm') }}>
    <section class="relative isolate overflow-hidden bg-linear-to-br from-zinc-950 via-indigo-950 to-violet-800 p-6 text-white sm:p-8">
        {{-- Illustration décorative propre au module, ignorée par les lecteurs d'écran. --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 right-0 w-[42rem] max-w-[72%] text-white/10">
            <svg class="size-full" viewBox="0 0 700 260" fill="none" preserveAspectRatio="xMaxYMid slice">
                {{-- Chaque variante utilise un dessin SVG léger, local et compatible Electron. --}}
                @switch($illustration)
                    @case('appointments')
                        <rect x="385" y="42" width="238" height="178" rx="28" stroke="currentColor" stroke-width="10"/>
                        <path d="M385 92h238M440 26v42M568 26v42" stroke="currentColor" stroke-width="14" stroke-linecap="round"/>
                        <path d="M433 128h34m42 0h34m42 0h10M433 168h34m42 0h34m42 0h10" stroke="currentColor" stroke-width="12" stroke-linecap="round"/>
                        @break
                    @case('customers')
                        <circle cx="505" cy="72" r="44" stroke="currentColor" stroke-width="14"/>
                        <circle cx="390" cy="106" r="30" stroke="currentColor" stroke-width="10"/>
                        <circle cx="620" cy="106" r="30" stroke="currentColor" stroke-width="10"/>
                        <path d="M420 230c10-74 44-108 85-108s75 34 85 108M325 230c8-47 30-72 65-72 18 0 33 7 44 21M675 230c-8-47-30-72-65-72-18 0-33 7-44 21" stroke="currentColor" stroke-width="15" stroke-linecap="round"/>
                        @break
                    @case('inventory')
                        <path d="m505 29 103 57v108l-103 57-103-57V86l103-57Z" stroke="currentColor" stroke-width="12"/>
                        <path d="m402 86 103 59 103-59M505 145v106M455 58l103 59" stroke="currentColor" stroke-width="10" stroke-linejoin="round"/>
                        @break
                    @case('invoices')
                        <path d="M414 34h150l58 58v143H414V34Z" stroke="currentColor" stroke-width="11" stroke-linejoin="round"/>
                        <path d="M564 34v60h58M452 128h126M452 166h126M452 204h78" stroke="currentColor" stroke-width="11" stroke-linecap="round"/>
                        <path d="M365 68h-25v150h42" stroke="currentColor" stroke-width="10" stroke-linecap="round"/>
                        @break
                    @case('accounting')
                        <rect x="372" y="34" width="260" height="194" rx="24" stroke="currentColor" stroke-width="10"/>
                        <path d="M426 182v-38M488 182v-82M550 182V72M410 182h160M404 69h88" stroke="currentColor" stroke-width="14" stroke-linecap="round"/>
                        <circle cx="594" cy="70" r="15" fill="currentColor"/>
                        @break
                    @case('reports')
                        <path d="M372 214h275M413 186v-48M476 186V92M539 186v-76M602 186V48" stroke="currentColor" stroke-width="18" stroke-linecap="round"/>
                        <path d="m400 112 72-52 68 24 79-59" stroke="currentColor" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="619" cy="25" r="16" fill="currentColor"/>
                        @break
                    @case('sales')
                        <path d="M370 204h270M403 172l66-66 52 38 93-103" stroke="currentColor" stroke-width="15" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M550 41h64v64" stroke="currentColor" stroke-width="15" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="406" cy="172" r="22" stroke="currentColor" stroke-width="10"/>
                        @break
                    @case('operations')
                        <path d="M370 202h72v-55h72V92h72V37h72" stroke="currentColor" stroke-width="16" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="406" cy="202" r="18" fill="currentColor"/><circle cx="478" cy="147" r="18" fill="currentColor"/><circle cx="550" cy="92" r="18" fill="currentColor"/><circle cx="622" cy="37" r="18" fill="currentColor"/>
                        @break
                    @case('settings')
                        <path d="M390 68h230M390 130h230M390 192h230" stroke="currentColor" stroke-width="12" stroke-linecap="round"/>
                        <circle cx="452" cy="68" r="24" fill="currentColor"/><circle cx="555" cy="130" r="24" fill="currentColor"/><circle cx="475" cy="192" r="24" fill="currentColor"/>
                        @break
                    @default
                        <circle cx="545" cy="52" r="142" stroke="currentColor" stroke-width="3"/>
                        <circle cx="610" cy="205" r="82" stroke="currentColor" stroke-width="24"/>
                        <path d="M310 238C390 92 493 79 690 137" stroke="currentColor" stroke-width="4" stroke-dasharray="10 12"/>
                @endswitch
            </svg>
        </div>

        {{-- Halo discret commun à toutes les variantes pour renforcer la profondeur visuelle. --}}
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_0%,rgba(139,92,246,.24),transparent_42%)]"></div>

        {{-- Contenu principal : identité du module à gauche et actions contextuelles à droite. --}}
        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                {{-- Icône principale fournie par la bibliothèque Flux UI. --}}
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-white/15 ring-1 ring-white/20 backdrop-blur">
                    <flux:icon :name="$icon" class="size-6" />
                </span>
                <div class="min-w-0">
                    <flux:badge color="zinc" :icon="$badgeIcon" class="mb-3 bg-white/10! text-white! ring-white/20!">{{ $eyebrow }}</flux:badge>
                    <flux:heading level="1" size="xl" class="text-white!">{{ $title }}</flux:heading>
                    <flux:text class="mt-2 max-w-2xl text-white/70!">{{ $description }}</flux:text>
                </div>
            </div>

            {{-- Actions facultatives définies dans la vue appelante. --}}
            @isset($actions)
                <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    </section>
</flux:card>
