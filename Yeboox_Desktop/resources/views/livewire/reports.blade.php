@php
    // The report screen and its print layout consume the same resolved model as Dompdf.
    $reportTheme = $reportPreferences['theme'];
    $reportDocumentStyle = sprintf(
        'font-family:%s;font-size:%dpx;font-weight:%d;--report-accent:%s;--report-dark:%s;--report-soft:%s;--report-border:%s',
        \App\Support\DocumentPreferences::fontStack($reportPreferences['font_family']),
        $reportPreferences['font_size'],
        $reportPreferences['font_weight'],
        $reportTheme['accent'],
        $reportTheme['dark'],
        $reportTheme['soft'],
        $reportTheme['border'],
    );
@endphp

<div
    class="mx-auto w-full max-w-[1600px] space-y-6 print:max-w-none"
    data-report-template="{{ $reportPreferences['template'] }}"
    wire:poll.30s="refreshReport"
    style="--report-font-family: {{ \App\Support\DocumentPreferences::fontStack($reportPreferences['font_family']) }}; --report-font-size: {{ $reportPreferences['font_size'] }}px; --report-font-weight: {{ $reportPreferences['font_weight'] }}; --report-accent: {{ $reportTheme['accent'] }}; --report-dark: {{ $reportTheme['dark'] }}; --report-soft: {{ $reportTheme['soft'] }}; --report-border: {{ $reportTheme['border'] }};"
>

    {{-- ╔══════════════════════════════════════════════════════════════╗ --}}
    {{-- ║  HEADER & EXPORT ACTIONS                                     ║ --}}
    {{-- ╚══════════════════════════════════════════════════════════════╝ --}}
    {{-- En-tête partagé : filtres, export et impression des rapports administratifs. --}}
    <x-page-hero
        class="no-print"
        title="Rapports & analyses"
        eyebrow="Pilotage & performance"
        description="Consultez la synthèse financière, les stocks et les sessions de caisse en temps réel."
        icon="chart-bar-square"
        badge-icon="presentation-chart-line"
        illustration="reports"
    >
        <x-slot:actions>
            <flux:badge color="emerald" icon="signal" class="bg-emerald-400/15! text-emerald-100! ring-emerald-300/20!">{{ $lastRefreshedAt }}</flux:badge>
            <flux:badge color="zinc" icon="document" class="bg-white/10! text-white! ring-white/20!">A4 · {{ $reportTemplateMeta['label'] }}</flux:badge>
            @can('export reports')
                <flux:button wire:click="exportExcel" icon="table-cells" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!" tooltip="Exporter vers Excel" aria-label="Exporter vers Excel" />
                <flux:button wire:click="exportWord" icon="document-text" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!" tooltip="Exporter vers Word" aria-label="Exporter vers Word" />
                <flux:button wire:click="exportMarkdown" icon="code-bracket" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!" tooltip="Exporter en Markdown" aria-label="Exporter en Markdown" />
                <flux:button href="{{ route('reports.pdf', array_filter(['type' => $reportType, 'start' => $startDate, 'end' => $endDate, 'department' => $departmentFilter ?: null])) }}" icon="arrow-down-tray" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!" tooltip="Télécharger le PDF structuré" aria-label="Télécharger le PDF structuré" />
            @endcan
            <flux:button type="button" x-on:click="window.printA4Report()" variant="primary" color="zinc" icon="printer" class="bg-white! text-indigo-900! hover:bg-violet-50!">Imprimer A4</flux:button>
        </x-slot:actions>
    </x-page-hero>

    <div class="no-print grid gap-3 md:grid-cols-3">
        <flux:card class="p-4!">
            <div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-300"><flux:icon.document class="size-5" /></span><div><flux:text size="sm">Modèle actif depuis Configuration</flux:text><flux:heading size="lg">{{ $reportTemplateMeta['label'] }}</flux:heading><flux:text size="sm">{{ $reportTemplateMeta['description'] }} · A4 portrait</flux:text></div></div>
        </flux:card>
        <flux:card class="p-4!">
            <div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-300"><flux:icon.signal class="size-5" /></span><div><flux:text size="sm">État des données</flux:text><flux:heading size="lg">Synchronisé</flux:heading><flux:text size="sm">Actualisation automatique toutes les 30 secondes</flux:text></div></div>
        </flux:card>
        <flux:card class="p-4!">
            <div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-300"><flux:icon.computer-desktop class="size-5" /></span><div><flux:text size="sm">Sortie document</flux:text><flux:heading size="lg">PDF & Electron Windows</flux:heading><flux:text size="sm">Couleurs, SVG et mise en page A4 conservés</flux:text></div></div>
        </flux:card>
    </div>

    {{-- ╔══════════════════════════════════════════════════════════════╗ --}}
    {{-- ║  SETTINGS & DATA COMPUTATION                                ║ --}}
    {{-- ╚══════════════════════════════════════════════════════════════╝ --}}
    @php
        $document_layout = \App\Models\Setting::get('document_layout', 'striped');
        // Report colors come from the selected document model, not the application theme.
        $primary_color   = $reportTheme['accent'];
        $company_name    = \App\Models\Setting::get('company_name', 'Yeboox App');
        $company_address = \App\Models\Setting::get('company_address', '');
        $company_phone   = \App\Models\Setting::get('company_phone', '');
        $company_email   = \App\Models\Setting::get('company_email', '');
        $company_rccm    = \App\Models\Setting::get('company_rccm', '');
        $company_nif     = \App\Models\Setting::get('company_nif', '');
        $company_tva     = \App\Models\Setting::get('company_tva', '');
        $company_header  = \App\Models\Setting::get('company_header', '');
        $company_footer  = \App\Models\Setting::get('company_footer', '');
        $logoPath        = \App\Models\Setting::get('company_logo');
        $currentLogoUrl  = $logoPath ? asset('storage/' . ltrim(str_replace('public/', '', $logoPath), '/')) : null;

        $revenue      = $reportType === 'sales' ? $sales->sum('total_amount') : 0;
        $discount     = $reportType === 'sales' ? $sales->sum('discount_amount') : 0;
        $exchangeRate = $exchangeRate ?? 1;
    @endphp

    <style>
        [data-report-template="minimal"] .yb-report-header { border-radius: 0 !important; box-shadow: none !important; border-width: 0 0 2px !important; }
        [data-report-template="minimal"] .yb-report-screen-card { border-radius: 0 !important; box-shadow: none !important; }
        [data-report-template="minimal"] .yb-report-decoration { display: none; }
        [data-report-template="corporate"] .yb-report-header { border-left: 10px solid {{ $primary_color }} !important; border-radius: 0.5rem !important; }
        [data-report-template="corporate"] .yb-report-screen-card { box-shadow: inset 4px 0 0 color-mix(in srgb, {{ $primary_color }} 75%, white); }
        [data-report-template="editorial"] .yb-report-title { font-family: Georgia, serif; letter-spacing: 0.02em !important; text-transform: none !important; }
        [data-report-template="editorial"] .yb-report-screen-card { border-radius: 0.25rem !important; }
        [data-report-template="geometric"] .yb-report-header { border-radius: 0 2rem 0 2rem !important; }
        [data-report-template="geometric"] .yb-report-screen-card { border-radius: 0 1.5rem 0 1.5rem !important; }
        [data-report-template="ledger"] .yb-report-header { border: 2px double #a1a1aa !important; border-radius: 0 !important; }
        [data-report-template="ledger"] .yb-report-screen-card { border-radius: 0 !important; box-shadow: inset 0 0 0 1px #d4d4d8 !important; }
        [data-report-template="elegant"] .yb-report-header { border-width: 0 !important; border-top: 2px solid {{ $primary_color }} !important; border-bottom: 1px solid {{ $primary_color }} !important; border-radius: 0 !important; }
        [data-report-template="elegant"] .yb-report-title { font-family: Georgia, serif; font-weight: 500 !important; }
        [data-report-template="modern"] .yb-report-header { border-bottom: 4px solid {{ $primary_color }} !important; }
        [data-report-template="modern"] .yb-report-screen-card { box-shadow: 0 8px 24px color-mix(in srgb, {{ $primary_color }} 10%, transparent) !important; }
        [data-report-template="framed"] .yb-a4-document { outline: 1px solid color-mix(in srgb, {{ $primary_color }} 55%, white); outline-offset: -2px; padding: 2px; }
        [data-report-template="framed"] .yb-report-header { margin: 0.1rem; }
        [data-report-template="compact"] .yb-report-header { min-height: 0 !important; }
        [data-report-template="compact"] .yb-report-header > div { padding-top: 0.35rem !important; padding-bottom: 0.35rem !important; }
        [data-report-template="official"] .yb-report-header { border: 1px solid {{ $reportTheme['accent'] }} !important; border-top-width: 5px !important; border-radius: .125rem !important; }
        [data-report-template="heritage"] .yb-report-header { border-width: 1px 0 4px !important; border-radius: 0 !important; font-family: Georgia, serif; }
        [data-report-template="sahara"] .yb-report-header { border: 0 !important; border-radius: .75rem .125rem !important; background: {{ $reportTheme['soft'] }} !important; }
        [data-report-template="digital"] .yb-report-header { border: 1px solid {{ $reportTheme['border'] }} !important; border-bottom: 4px solid {{ $reportTheme['accent'] }} !important; border-radius: .25rem !important; }
        [data-report-template="digital"] .yb-report-title { font-family: ui-monospace, monospace; letter-spacing: .08em !important; }

        @media print {
            @page { size: A4 portrait; margin: 38mm 12mm 20mm; }
            .no-print { display: none !important; }
            html, body { width: auto !important; height: auto !important; min-height: 0 !important; margin: 0 !important; padding: 0 !important; overflow: visible !important; background: white !important; color: black !important; }
            main { width: 100% !important; margin: 0 !important; padding: 0 !important; }
            [data-report-template] { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; font-family: var(--report-font-family) !important; font-size: var(--report-font-size) !important; font-weight: var(--report-font-weight) !important; }
            .yb-print-flow { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; break-before: auto !important; page-break-before: auto !important; }
            .yb-print-toc { display: block !important; margin: 0 0 5mm !important; padding: 3mm !important; border: 1px solid var(--report-border) !important; border-left: 5px solid var(--report-accent) !important; background: var(--report-soft) !important; break-after: avoid !important; page-break-after: avoid !important; }
            .yb-print-flow > :first-child { margin-top: 0 !important; break-before: auto !important; page-break-before: auto !important; }
            .yb-report-screen-card { border-color: #e4e4e7 !important; box-shadow: none !important; break-inside: auto !important; page-break-inside: auto !important; }
            .yb-report-screen-card > div:first-child, .yb-table-heading { break-after: avoid !important; page-break-after: avoid !important; }
            .yb-chart-section { break-inside: auto !important; page-break-inside: auto !important; }
            .yb-print-header { position: fixed !important; top: -31mm; left: 0; right: 0; height: 27mm; margin: 0 !important; padding: 0 !important; display: block !important; z-index: 1000; break-before: auto !important; page-break-before: auto !important; }
            .yb-print-footer { position: fixed !important; bottom: -15mm; left: 0; right: 0; height: 11mm; margin: 0 !important; padding: 0 1mm !important; display: flex !important; align-items: center; z-index: 1000; break-before: auto !important; page-break-before: auto !important; }
            .yb-print-footer .yb-page-number::after { content: counter(page); }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            table { break-before: auto !important; page-break-before: auto !important; }
            tr, article { break-inside: avoid !important; page-break-inside: avoid !important; }
            .print\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)) !important; }
            * { 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
                color-adjust: exact !important; 
            }
        }
    </style>

    <header class="yb-print-header yb-a4-document hidden bg-white" style="{{ $reportDocumentStyle }}">
        <div class="yb-report-header flex h-full items-stretch overflow-hidden rounded-lg bg-white ring-1 ring-zinc-200">
            <div class="w-1.5 shrink-0" style="background-color: {{ $reportTheme['accent'] }}"></div>
            <div class="flex min-w-0 flex-1 items-center justify-between gap-6 px-4 py-2.5">
                <div class="flex min-w-0 items-center gap-3">
                    @if($currentLogoUrl)<img src="{{ $currentLogoUrl }}" alt="Logo {{ $company_name }}" class="size-12 shrink-0 object-contain">@endif
                    <div class="min-w-0 text-zinc-700">
                        <p class="truncate text-[13px] font-extrabold uppercase tracking-wide" style="color: {{ $primary_color }}">{{ $company_name ?: 'Entreprise' }}</p>
                        <p class="mt-0.5 truncate text-[8px]">{{ $company_address ?: 'Adresse non définie' }} · {{ $company_phone ?: 'Téléphone non défini' }} · {{ $company_email ?: 'E-mail non défini' }}</p>
                        <p class="mt-0.5 truncate text-[8px] text-zinc-500">RCCM : {{ $company_rccm ?: '—' }} · NIF/Impôt : {{ $company_nif ?: '—' }} · TVA : {{ $company_tva ?: '—' }}</p>
                    </div>
                </div>
                <div class="shrink-0 text-right">
                    <p class="yb-report-title text-[14px] font-extrabold uppercase tracking-[0.12em] text-zinc-900">Rapport @if($reportType === 'sales')des ventes @elseif($reportType === 'stock')de stock @else des sessions de caisse @endif</p>
                    <p class="mt-1 text-[8px] font-medium text-zinc-500">Du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                    <p class="mt-0.5 text-[8px] text-zinc-400">Modèle {{ $reportTemplateMeta['label'] }} · Généré le {{ now()->format('d/m/Y à H:i') }}</p>
                </div>
            </div>
        </div>
    </header>

    <section class="yb-print-toc hidden yb-a4-document" style="{{ $reportDocumentStyle }}" aria-label="Table des matières du rapport">
        <div class="text-[8px] font-bold uppercase tracking-[.16em]" style="color:var(--report-accent)">Table des matières</div>
        <div class="mt-2 grid grid-cols-3 gap-3 text-[9px] font-semibold">
            @if($reportType === 'sales')
                <span>01 · Synthèse financière</span><span>02 · Produits et catégories</span><span>03 · Registre des ventes</span>
            @elseif($reportType === 'stock')
                <span>01 · Indicateurs du stock</span><span>02 · Mouvements de stock</span><span>03 · Alertes de rupture</span>
            @else
                <span>01 · Synthèse des sessions</span><span>02 · Ouvertures et clôtures</span><span>03 · Encaissements par agent</span>
            @endif
        </div>
    </section>

    {{-- ╔══════════════════════════════════════════════════════════════╗ --}}
    {{-- ║  CONTROL BAR: TABS & FILTERS (no-print)                       ║ --}}
    {{-- ╚══════════════════════════════════════════════════════════════╝ --}}
    <flux:card class="p-4! print:hidden sm:p-5!">
        <div class="flex w-full flex-col items-stretch justify-between gap-5 xl:flex-row xl:items-end">
            
            {{-- Tabs Selector (Segmented Bar) --}}
            <div class="flex flex-wrap items-center gap-1 rounded-xl border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:button wire:click="$set('reportType', 'sales')" :variant="$reportType === 'sales' ? 'primary' : 'ghost'" :color="$reportType === 'sales' ? 'violet' : null" icon="chart-bar-square" size="sm">Ventes</flux:button>
                <flux:button wire:click="$set('reportType', 'stock')" :variant="$reportType === 'stock' ? 'primary' : 'ghost'" :color="$reportType === 'stock' ? 'violet' : null" icon="cube" size="sm">Stocks</flux:button>
                <flux:button wire:click="$set('reportType', 'sessions')" :variant="$reportType === 'sessions' ? 'primary' : 'ghost'" :color="$reportType === 'sessions' ? 'violet' : null" icon="lock-closed" size="sm">Clôtures caisse</flux:button>
            </div>

            {{-- Date & Department Filters --}}
            <div class="flex flex-wrap items-end gap-3">
                @if($reportType === 'sales')
                    <flux:button.group>
                        <flux:button size="sm" wire:click="setPeriod('today')">Aujourd’hui</flux:button>
                        <flux:button size="sm" wire:click="setPeriod('yesterday')">Hier</flux:button>
                        <flux:button size="sm" wire:click="setPeriod('week')">7 jours</flux:button>
                        <flux:button size="sm" wire:click="setPeriod('month')">Mois</flux:button>
                    </flux:button.group>
                @endif
                <div class="grid flex-1 gap-3 sm:grid-cols-2 xl:min-w-[28rem]">
                    <flux:input type="date" wire:model.live="startDate" label="Date de début" />
                    <flux:input type="date" wire:model.live="endDate" label="Date de fin" />
                </div>

                @if($reportType === 'sales')
                    <div class="min-w-52 flex-1 xl:max-w-64">
                        <flux:select wire:model.live="departmentFilter" label="Secteur">
                            <flux:select.option value="">Tous les secteurs</flux:select.option>
                            <flux:select.option value="bar">Bar</flux:select.option>
                            <flux:select.option value="salon">Salon / Spa</flux:select.option>
                        </flux:select>
                    </div>
                @endif
            </div>

        </div>
    </flux:card>

    <div class="yb-print-flow space-y-6">
    {{-- ╔══════════════════════════════════════════════════════════════╗ --}}
    {{-- ║  ROW COL-4 STAT CARDS (Now visible in print too)             ║ --}}
    {{-- ╚══════════════════════════════════════════════════════════════╝ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 print:grid-cols-4 gap-4 mb-6">

        @if($reportType === 'sales')
            {{-- Card 1: CA Brut --}}
            <div class="relative overflow-hidden bg-white dark:bg-[#141414] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none group transition-all duration-300 hover:shadow-lg dark:hover:shadow-purple-900/20 hover:-translate-y-1">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-purple-100 dark:bg-purple-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">CA Brut</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ \App\Support\DisplayCurrency::format($revenue + $discount) }}</h3>
                        <p class="text-[10px] text-purple-700 dark:text-purple-400 font-medium bg-purple-50 dark:bg-purple-500/10 px-2 py-1 rounded mt-2 inline-block">Équiv. {{ \App\Support\DisplayCurrency::format($revenue + $discount, \App\Support\DisplayCurrency::secondaryCode()) }}</p>
                    </div>
                    <div class="p-3 bg-purple-50 dark:bg-purple-500/10 rounded-2xl border border-purple-100 dark:border-purple-500/20">
                        <flux:icon.banknotes class="size-5 text-purple-600 dark:text-purple-300" />
                    </div>
                </div>
            </div>

            {{-- Card 2: Montant Net Encaissé --}}
            <div class="relative overflow-hidden bg-white dark:bg-[#141414] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none group transition-all duration-300 hover:shadow-lg dark:hover:shadow-emerald-900/20 hover:-translate-y-1">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-emerald-100 dark:bg-emerald-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Net Encaissé</p>
                        <h3 class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">{{ \App\Support\DisplayCurrency::format($revenue) }}</h3>
                        <p class="text-[10px] text-emerald-700 dark:text-emerald-400 font-medium bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded mt-2 inline-block">Équiv. {{ \App\Support\DisplayCurrency::format($revenue, \App\Support\DisplayCurrency::secondaryCode()) }}</p>
                    </div>
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-500/10 rounded-2xl border border-emerald-100 dark:border-emerald-500/20">
                        <flux:icon.check-circle class="size-5 text-emerald-600 dark:text-emerald-300" />
                    </div>
                </div>
            </div>

            {{-- Card 3: Total Remises Accordées --}}
            <div class="relative overflow-hidden bg-white dark:bg-[#141414] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none group transition-all duration-300 hover:shadow-lg dark:hover:shadow-rose-900/20 hover:-translate-y-1">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-rose-100 dark:bg-rose-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Remises Accordées</p>
                        <h3 class="text-3xl font-extrabold text-rose-600 dark:text-rose-400 mt-2">{{ \App\Support\DisplayCurrency::format($discount) }}</h3>
                        <p class="text-[10px] text-rose-700 dark:text-rose-400 font-medium bg-rose-50 dark:bg-rose-500/10 px-2 py-1 rounded mt-2 inline-block">Équiv. {{ \App\Support\DisplayCurrency::format($discount, \App\Support\DisplayCurrency::secondaryCode()) }}</p>
                    </div>
                    <div class="p-3 bg-rose-50 dark:bg-rose-500/10 rounded-2xl border border-rose-100 dark:border-rose-500/20">
                        <flux:icon.tag class="size-5 text-rose-600 dark:text-rose-300" />
                    </div>
                </div>
            </div>

            {{-- Card 4: Nombre de Ventes --}}
            <div class="relative overflow-hidden bg-white dark:bg-[#141414] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none group transition-all duration-300 hover:shadow-lg dark:hover:shadow-purple-900/20 hover:-translate-y-1">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-purple-100 dark:bg-purple-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Total Transactions</p>
                        <h3 class="text-3xl font-extrabold text-purple-600 dark:text-purple-400 mt-2">{{ count($sales) }}</h3>
                        <p class="text-[10px] text-purple-700 dark:text-purple-400 font-medium bg-purple-50 dark:bg-purple-500/10 px-2 py-1 rounded mt-2 inline-block">Ventes enregistrées</p>
                    </div>
                    <div class="p-3 bg-purple-50 dark:bg-purple-500/10 rounded-2xl border border-purple-100 dark:border-purple-500/20">
                        <flux:icon.shopping-bag class="size-5 text-purple-600 dark:text-purple-300" />
                    </div>
                </div>
            </div>
        @endif

        @if($reportType === 'stock')
            {{-- Card 1: Total Mouvements --}}
            <div class="relative overflow-hidden bg-white dark:bg-[#141414] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none group transition-all duration-300 hover:shadow-lg dark:hover:shadow-purple-900/20 hover:-translate-y-1">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-purple-100 dark:bg-purple-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Total Mouvements</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ count($movements) }}</h3>
                        <p class="text-[10px] text-purple-700 dark:text-purple-400 font-medium bg-purple-50 dark:bg-purple-500/10 px-2 py-1 rounded mt-2 inline-block">Opérations enregistrées</p>
                    </div>
                    <div class="p-3 bg-purple-50 dark:bg-purple-500/10 rounded-2xl border border-purple-100 dark:border-purple-500/20">
                        <flux:icon.cube class="size-5 text-purple-600 dark:text-purple-300" />
                    </div>
                </div>
            </div>

            {{-- Card 2: Entrées Stock --}}
            <div class="relative overflow-hidden bg-white dark:bg-[#141414] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none group transition-all duration-300 hover:shadow-lg dark:hover:shadow-emerald-900/20 hover:-translate-y-1">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-emerald-100 dark:bg-emerald-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Entrées Stock</p>
                        <h3 class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">+{{ $movements->where('type', 'in')->sum('quantity') }}</h3>
                        <p class="text-[10px] text-emerald-700 dark:text-emerald-400 font-medium bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded mt-2 inline-block">Unités approvisionnées</p>
                    </div>
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-500/10 rounded-2xl border border-emerald-100 dark:border-emerald-500/20">
                        <flux:icon.arrow-down-tray class="size-5 text-emerald-600 dark:text-emerald-300" />
                    </div>
                </div>
            </div>

            {{-- Card 3: Sorties Stock --}}
            <div class="relative overflow-hidden bg-white dark:bg-[#141414] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none group transition-all duration-300 hover:shadow-lg dark:hover:shadow-amber-900/20 hover:-translate-y-1">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-amber-100 dark:bg-amber-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Sorties Stock</p>
                        <h3 class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">-{{ $movements->where('type', 'out')->sum('quantity') }}</h3>
                        <p class="text-[10px] text-amber-700 dark:text-amber-400 font-medium bg-amber-50 dark:bg-amber-500/10 px-2 py-1 rounded mt-2 inline-block">Unités vendues / retirées</p>
                    </div>
                    <div class="p-3 bg-amber-50 dark:bg-amber-500/10 rounded-2xl border border-amber-100 dark:border-amber-500/20">
                        <flux:icon.arrow-up-tray class="size-5 text-amber-600 dark:text-amber-300" />
                    </div>
                </div>
            </div>

            {{-- Card 4: Alertes Ruptures --}}
            <div class="relative overflow-hidden bg-white dark:bg-[#141414] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none group transition-all duration-300 hover:shadow-lg dark:hover:shadow-rose-900/20 hover:-translate-y-1">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-rose-100 dark:bg-rose-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Alertes Ruptures</p>
                        <h3 class="text-3xl font-extrabold text-rose-600 dark:text-rose-400 mt-2">{{ count($lowStockProducts) }}</h3>
                        <p class="text-[10px] text-rose-700 dark:text-rose-400 font-medium bg-rose-50 dark:bg-rose-500/10 px-2 py-1 rounded mt-2 inline-block">Produits à réapprovisionner</p>
                    </div>
                    <div class="p-3 bg-rose-50 dark:bg-rose-500/10 rounded-2xl border border-rose-100 dark:border-rose-500/20">
                        <flux:icon.exclamation-triangle class="size-5 text-rose-600 dark:text-rose-300" />
                    </div>
                </div>
            </div>
        @endif

        @if($reportType === 'sessions')
            {{-- Card 1: Sessions Totales --}}
            <div class="relative overflow-hidden bg-white dark:bg-[#141414] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none group transition-all duration-300 hover:shadow-lg dark:hover:shadow-purple-900/20 hover:-translate-y-1">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-purple-100 dark:bg-purple-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Sessions Totales</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ is_countable($sessions) ? count($sessions) : 0 }}</h3>
                        <p class="text-[10px] text-purple-700 dark:text-purple-400 font-medium bg-purple-50 dark:bg-purple-500/10 px-2 py-1 rounded mt-2 inline-block">Clôtures enregistrées</p>
                    </div>
                    <div class="p-3 bg-purple-50 dark:bg-purple-500/10 rounded-2xl border border-purple-100 dark:border-purple-500/20">
                        <flux:icon.key class="size-5 text-purple-600 dark:text-purple-300" />
                    </div>
                </div>
            </div>

            {{-- Card 2: Total Fonds Ouverture --}}
            <div class="relative overflow-hidden bg-white dark:bg-[#141414] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none group transition-all duration-300 hover:shadow-lg dark:hover:shadow-purple-900/20 hover:-translate-y-1">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-purple-100 dark:bg-purple-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Fonds d'Ouverture</p>
                        <h3 class="text-3xl font-extrabold text-purple-600 dark:text-purple-400 mt-2">{{ \App\Support\DisplayCurrency::format(is_iterable($sessions) ? collect($sessions)->sum('opening_balance') : 0) }}</h3>
                        <p class="text-[10px] text-purple-700 dark:text-purple-400 font-medium bg-purple-50 dark:bg-purple-500/10 px-2 py-1 rounded mt-2 inline-block">Cumul d'ouverture</p>
                    </div>
                    <div class="p-3 bg-purple-50 dark:bg-purple-500/10 rounded-2xl border border-purple-100 dark:border-purple-500/20">
                        <flux:icon.briefcase class="size-5 text-emerald-600 dark:text-emerald-300" />
                    </div>
                </div>
            </div>

            {{-- Card 3: Caissiers Actifs --}}
            <div class="relative overflow-hidden bg-white dark:bg-[#141414] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none group transition-all duration-300 hover:shadow-lg dark:hover:shadow-purple-900/20 hover:-translate-y-1">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-purple-100 dark:bg-purple-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">Caissiers Actifs</p>
                        <h3 class="text-3xl font-extrabold text-purple-600 dark:text-purple-400 mt-2">{{ is_iterable($sessions) ? collect($sessions)->pluck('user_id')->unique()->count() : 0 }}</h3>
                        <p class="text-[10px] text-purple-700 dark:text-purple-400 font-medium bg-purple-50 dark:bg-purple-500/10 px-2 py-1 rounded mt-2 inline-block">Agents ayant opéré</p>
                    </div>
                    <div class="p-3 bg-purple-50 dark:bg-purple-500/10 rounded-2xl border border-purple-100 dark:border-purple-500/20">
                        <flux:icon.user class="size-5 text-purple-600 dark:text-purple-300" />
                    </div>
                </div>
            </div>

            {{-- Card 4: Sessions Ouvertes --}}
            <div class="relative overflow-hidden bg-white dark:bg-[#141414] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm dark:shadow-none group transition-all duration-300 hover:shadow-lg dark:hover:shadow-amber-900/20 hover:-translate-y-1">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-amber-100 dark:bg-amber-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-gray-400 uppercase tracking-wider">En cours</p>
                        <h3 class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">{{ is_iterable($sessions) ? collect($sessions)->where('closed_at', null)->count() : 0 }}</h3>
                        <p class="text-[10px] text-amber-700 dark:text-amber-400 font-medium bg-amber-50 dark:bg-amber-500/10 px-2 py-1 rounded mt-2 inline-block">Sessions actives</p>
                    </div>
                    <div class="p-3 bg-amber-50 dark:bg-amber-500/10 rounded-2xl border border-amber-100 dark:border-amber-500/20">
                        <flux:icon.clock class="size-5 text-amber-600 dark:text-amber-300" />
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- ╔══════════════════════════════════════════════════════════════╗ --}}
    {{-- ║  MAIN DATA TABLE CONTAINER                                   ║ --}}
    {{-- ╚══════════════════════════════════════════════════════════════╝ --}}

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  TAB 1: VENTES                                                 --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if($reportType === 'sales')

        @php
            $trendMax = max(1, (float) $salesTrend->max('revenue'));
            $trendCount = max(1, $salesTrend->count());
            $trendPoints = $salesTrend->map(function ($point, $index) use ($trendMax, $trendCount) {
                $x = $trendCount === 1 ? 380 : 28 + ($index * (704 / ($trendCount - 1)));
                $y = 182 - (($point['revenue'] / $trendMax) * 142);
                return round($x, 1).','.round($y, 1);
            })->implode(' ');
            $topQuantityMax = max(1, (float) $topProducts->max('quantity'));
            $categoryColors = ['#7c3aed', '#0ea5e9', '#10b981', '#f59e0b', '#f43f5e', '#6366f1', '#14b8a6', '#8b5cf6'];
        @endphp

        <section class="yb-chart-section grid gap-5 xl:grid-cols-5" aria-label="Statistiques de vente">
            <flux:card class="yb-report-screen-card p-0! xl:col-span-3">
                <div class="flex flex-wrap items-start justify-between gap-3 p-5 sm:p-6">
                    <div class="flex items-start gap-3"><span class="grid size-10 place-items-center rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-300"><flux:icon.presentation-chart-line class="size-5" /></span><div><flux:heading size="lg">Évolution des ventes</flux:heading><flux:text size="sm">Chiffre d’affaires net par jour · graphique vectoriel PDF</flux:text></div></div>
                    <flux:badge color="violet">{{ $salesTrend->count() }} jour(s)</flux:badge>
                </div>
                <div class="px-4 pb-5 sm:px-6 sm:pb-6">
                    @if($salesTrend->isNotEmpty())
                        <svg class="h-auto w-full overflow-visible" viewBox="0 0 760 225" role="img" aria-label="Évolution du chiffre d'affaires sur la période">
                            <defs><linearGradient id="reportTrendFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="{{ $primary_color }}" stop-opacity=".26"/><stop offset="1" stop-color="{{ $primary_color }}" stop-opacity=".02"/></linearGradient></defs>
                            @foreach([40, 87, 134, 181] as $gridY)<line x1="28" y1="{{ $gridY }}" x2="732" y2="{{ $gridY }}" stroke="currentColor" class="text-zinc-200 dark:text-zinc-800" stroke-width="1" stroke-dasharray="4 7"/>@endforeach
                            <polygon points="28,182 {{ $trendPoints }} 732,182" fill="url(#reportTrendFill)"/>
                            <polyline points="{{ $trendPoints }}" fill="none" stroke="{{ $primary_color }}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                            @foreach($salesTrend as $index => $point)
                                @php
                                    $x = $trendCount === 1 ? 380 : 28 + ($index * (704 / ($trendCount - 1)));
                                    $y = 182 - (($point['revenue'] / $trendMax) * 142);
                                @endphp
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="white" stroke="{{ $primary_color }}" stroke-width="3"><title>{{ $point['label'] }} · {{ number_format($point['revenue'], 2) }}</title></circle>
                                @if($trendCount <= 12 || $loop->first || $loop->last)<text x="{{ $x }}" y="208" text-anchor="middle" fill="currentColor" class="text-zinc-500" font-size="10">{{ $point['label'] }}</text>@endif
                            @endforeach
                        </svg>
                    @else
                        <div class="grid min-h-56 place-items-center rounded-2xl bg-zinc-50 text-center dark:bg-zinc-900/60"><div><flux:icon.chart-bar class="mx-auto size-8 text-zinc-300" /><flux:text class="mt-2">Aucune vente sur cette période.</flux:text></div></div>
                    @endif
                </div>
            </flux:card>

            <flux:card class="yb-report-screen-card p-0! xl:col-span-2">
                <div class="flex items-start gap-3 p-5 sm:p-6"><span class="grid size-10 place-items-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-300"><flux:icon.trophy class="size-5" /></span><div><flux:heading size="lg">Produits les plus commandés</flux:heading><flux:text size="sm">Classement par quantité vendue</flux:text></div></div>
                <div class="space-y-4 px-5 pb-5 sm:px-6 sm:pb-6">
                    @forelse($topProducts as $product)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm"><span class="min-w-0 truncate font-medium text-zinc-900 dark:text-white">{{ $product['name'] }}</span><span class="shrink-0 font-semibold text-zinc-700 dark:text-zinc-200">{{ number_format($product['quantity'], 0) }} vendu(s)</span></div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"><div class="h-full rounded-full bg-linear-to-r from-violet-600 to-fuchsia-500" style="width: {{ max(4, ($product['quantity'] / $topQuantityMax) * 100) }}%"></div></div>
                            <div class="mt-1 flex justify-between text-[11px] text-zinc-500"><span>{{ $product['category'] }} · {{ $product['orders'] }} commande(s)</span><span>{{ \App\Support\DisplayCurrency::format($product['revenue']) }}</span></div>
                        </div>
                    @empty
                        <div class="grid min-h-56 place-items-center text-center"><div><flux:icon.cube-transparent class="mx-auto size-8 text-zinc-300" /><flux:text class="mt-2">Aucun produit commandé.</flux:text></div></div>
                    @endforelse
                </div>
            </flux:card>
        </section>

        <flux:card class="yb-report-screen-card p-0!">
            <div class="flex flex-wrap items-start justify-between gap-3 p-5 sm:p-6"><div class="flex items-start gap-3"><span class="grid size-10 place-items-center rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-300"><flux:icon.squares-2x2 class="size-5" /></span><div><flux:heading size="lg">Ventes groupées par catégorie</flux:heading><flux:text size="sm">Répartition des produits, commandes et chiffre d’affaires</flux:text></div></div><flux:badge color="sky">{{ $categorySales->count() }} catégorie(s)</flux:badge></div>
            <div class="grid gap-3 px-5 pb-5 sm:grid-cols-2 sm:px-6 sm:pb-6 xl:grid-cols-4">
                @forelse($categorySales as $index => $category)
                    <article class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-900/70">
                        <div class="flex items-center justify-between gap-3"><span class="grid size-9 place-items-center rounded-xl text-white" style="background-color: {{ $categoryColors[$index % count($categoryColors)] }}"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z" stroke-width="1.8"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9" stroke-width="1.8"/></svg></span><flux:badge color="zinc">{{ $category['share'] }}%</flux:badge></div>
                        <h3 class="mt-3 truncate font-semibold text-zinc-900 dark:text-white">{{ $category['name'] }}</h3>
                        <p class="mt-1 text-xl font-bold" style="color: {{ $categoryColors[$index % count($categoryColors)] }}">{{ \App\Support\DisplayCurrency::format($category['revenue']) }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ number_format($category['quantity'], 0) }} article(s) · {{ $category['orders'] }} commande(s)</p>
                    </article>
                @empty
                    <div class="col-span-full py-10 text-center"><flux:text>Aucune catégorie à analyser sur la période.</flux:text></div>
                @endforelse
            </div>
        </flux:card>

        <!-- ODOO INSPIRED SALES DASHBOARD -->
        @php
            // Groupement des ventes pour le tableau détaillé.
            $salesByDate = collect($sales)->groupBy(function($sale) {
                return $sale->created_at->format('Y-m-d');
            })->sortKeys();
        @endphp

        <flux:card class="yb-report-screen-card overflow-hidden p-0!">
            <div class="flex flex-col gap-5 bg-zinc-50/70 p-5 dark:bg-zinc-950/40 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 items-start gap-3.5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-violet-600 text-white shadow-sm shadow-violet-600/20"><flux:icon.table-cells class="size-5" /></span>
                    <div class="min-w-0">
                        <flux:heading size="lg">Analyse détaillée des ventes</flux:heading>
                        <flux:text size="sm" class="mt-1 max-w-2xl">Lecture hiérarchique du chiffre d’affaires par journée et par secteur d’activité.</flux:text>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                    <flux:badge color="zinc" icon="calendar-days">{{ $salesByDate->count() }} jour(s)</flux:badge>
                    <flux:badge color="violet" icon="shopping-bag">{{ count($sales) }} transaction(s)</flux:badge>
                </div>
            </div>

            <div class="grid gap-px bg-zinc-200/70 dark:bg-zinc-800 sm:grid-cols-3">
                <div class="bg-white px-5 py-4 dark:bg-zinc-900 sm:px-6"><flux:text size="sm">Chiffre d’affaires brut</flux:text><p class="mt-1 text-lg font-semibold tabular-nums text-zinc-950 dark:text-white">{{ \App\Support\DisplayCurrency::format($revenue + $discount) }}</p></div>
                <div class="bg-white px-5 py-4 dark:bg-zinc-900 sm:px-6"><flux:text size="sm">Remises accordées</flux:text><p class="mt-1 text-lg font-semibold tabular-nums text-rose-600 dark:text-rose-400">− {{ \App\Support\DisplayCurrency::format($discount) }}</p></div>
                <div class="bg-white px-5 py-4 dark:bg-zinc-900 sm:px-6"><flux:text size="sm">Net encaissé</flux:text><p class="mt-1 text-lg font-semibold tabular-nums text-violet-700 dark:text-violet-300">{{ \App\Support\DisplayCurrency::format($revenue) }}</p></div>
            </div>

            <div class="overflow-x-auto">
                <flux:table class="w-full text-sm">
                    <flux:table.columns>
                        <flux:table.row class="bg-zinc-50/80 text-[11px] font-semibold uppercase tracking-[0.08em] text-zinc-500 dark:bg-zinc-950/40 dark:text-zinc-400">
                            <flux:table.column class="w-[38%] px-5 py-3.5 sm:px-6"><x-table-heading icon="calendar-days">Période / secteur</x-table-heading></flux:table.column>
                            <flux:table.column class="px-4 py-3.5 text-center"><x-table-heading icon="receipt-percent">Transactions</x-table-heading></flux:table.column>
                            <flux:table.column class="max-md:hidden px-4 py-3.5 text-right"><x-table-heading icon="banknotes">Montant brut</x-table-heading></flux:table.column>
                            <flux:table.column class="max-md:hidden px-4 py-3.5 text-right"><x-table-heading icon="tag">Remises</x-table-heading></flux:table.column>
                            <flux:table.column class="px-5 py-3.5 text-right sm:px-6"><x-table-heading icon="calculator">Net encaissé</x-table-heading></flux:table.column>
                        </flux:table.row>
                    </flux:table.columns>
                    <flux:table.rows class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                        @forelse($salesByDate as $date => $daySales)
                            @php
                                $dayRevenue = (float) $daySales->sum('total_amount');
                                $dayDiscount = (float) $daySales->sum('discount_amount');
                                $dayGross = $dayRevenue + $dayDiscount;
                                $daySalesByDept = $daySales->groupBy('department');
                            @endphp
                            <flux:table.row class="bg-violet-50/55 text-zinc-900 dark:bg-violet-500/7 dark:text-white">
                                <flux:table.cell class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-xl bg-white text-violet-600 shadow-sm ring-1 ring-violet-100 dark:bg-zinc-900 dark:text-violet-300 dark:ring-violet-500/20"><flux:icon.calendar-days class="size-4" /></span><div><p class="font-semibold leading-tight">{{ \Carbon\Carbon::parse($date)->translatedFormat('l d F Y') }}</p><p class="mt-1 text-xs font-normal text-zinc-500 dark:text-zinc-400">{{ $daySalesByDept->count() }} secteur(s) actif(s)</p></div></div>
                                </flux:table.cell>
                                <flux:table.cell class="px-4 py-4 text-center"><span class="inline-flex min-w-8 justify-center rounded-lg bg-white px-2.5 py-1 font-semibold tabular-nums text-zinc-700 shadow-sm dark:bg-zinc-800 dark:text-zinc-200">{{ $daySales->count() }}</span></flux:table.cell>
                                <flux:table.cell class="max-md:hidden px-4 py-4 text-right font-medium tabular-nums text-zinc-700 dark:text-zinc-200">{{ \App\Support\DisplayCurrency::format($dayGross) }}</flux:table.cell>
                                <flux:table.cell class="max-md:hidden px-4 py-4 text-right font-medium tabular-nums text-rose-600 dark:text-rose-400">− {{ \App\Support\DisplayCurrency::format($dayDiscount) }}</flux:table.cell>
                                <flux:table.cell class="px-5 py-4 text-right font-bold tabular-nums text-violet-700 dark:text-violet-300 sm:px-6">{{ \App\Support\DisplayCurrency::format($dayRevenue) }}</flux:table.cell>
                            </flux:table.row>

                            @foreach($daySalesByDept as $dept => $deptSales)
                                @php
                                    $deptRevenue = (float) $deptSales->sum('total_amount');
                                    $deptDiscount = (float) $deptSales->sum('discount_amount');
                                    $deptLabel = match($dept) { 'salon' => 'Salon / Spa', 'bar' => 'Bar & restauration', default => ucfirst($dept ?: 'Non défini') };
                                @endphp
                                <flux:table.row class="text-zinc-600 transition-colors hover:bg-zinc-50/80 dark:text-zinc-300 dark:hover:bg-zinc-800/40">
                                    <flux:table.cell class="px-5 py-3.5 sm:px-6">
                                        <div class="flex items-center gap-3 pl-2 sm:pl-10"><span class="grid size-7 shrink-0 place-items-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 20V9l8-5 8 5v11M8 20v-6h8v6M3 20h18" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="font-medium">{{ $deptLabel }}</span></div>
                                    </flux:table.cell>
                                    <flux:table.cell class="px-4 py-3.5 text-center tabular-nums text-zinc-500 dark:text-zinc-400">{{ $deptSales->count() }}</flux:table.cell>
                                    <flux:table.cell class="max-md:hidden px-4 py-3.5 text-right tabular-nums">{{ \App\Support\DisplayCurrency::format($deptRevenue + $deptDiscount) }}</flux:table.cell>
                                    <flux:table.cell class="max-md:hidden px-4 py-3.5 text-right tabular-nums text-rose-500">− {{ \App\Support\DisplayCurrency::format($deptDiscount) }}</flux:table.cell>
                                    <flux:table.cell class="px-5 py-3.5 text-right font-semibold tabular-nums text-zinc-900 dark:text-white sm:px-6">{{ \App\Support\DisplayCurrency::format($deptRevenue) }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        @empty
                            <flux:table.row><flux:table.cell colspan="5" class="px-6 py-16 text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800"><flux:icon.chart-bar class="size-6" /></span><flux:heading size="lg" class="mt-4">Aucune vente trouvée</flux:heading><flux:text class="mt-1">Modifiez la période ou le filtre de secteur.</flux:text></flux:table.cell></flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <div x-data="{ showList: false }" class="border-t border-zinc-100 bg-zinc-50/60 p-3 dark:border-zinc-800 dark:bg-zinc-950/30 print:hidden">
                <flux:button type="button" variant="ghost" class="w-full justify-between! px-3! py-2.5!" x-on:click="showList = !showList" aria-controls="sales-transaction-details" x-bind:aria-expanded="showList">
                    <span class="flex items-center gap-2"><flux:icon.list-bullet class="size-4" /><span x-text="showList ? 'Masquer les transactions' : 'Afficher les transactions individuelles'"></span></span>
                    <flux:icon.chevron-down class="size-4 transition-transform" x-bind:class="showList && 'rotate-180'" />
                </flux:button>

                <div id="sales-transaction-details" x-cloak x-show="showList" x-transition.opacity class="mt-3 overflow-hidden rounded-xl bg-white ring-1 ring-zinc-200/70 dark:bg-zinc-900 dark:ring-zinc-800">
                    <div class="overflow-x-auto">
                        <flux:table class="w-full text-sm">
                            <flux:table.columns><flux:table.row class="bg-zinc-50/80 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-950/40"><flux:table.column class="px-5 py-3"><x-table-heading icon="calendar-days">Date et heure</x-table-heading></flux:table.column><flux:table.column class="px-5 py-3"><x-table-heading icon="user">Client</x-table-heading></flux:table.column><flux:table.column class="px-5 py-3"><x-table-heading icon="squares-2x2">Secteur</x-table-heading></flux:table.column><flux:table.column class="px-5 py-3"><x-table-heading icon="credit-card">Paiement</x-table-heading></flux:table.column><flux:table.column class="px-5 py-3 text-right"><x-table-heading icon="banknotes">Montant net</x-table-heading></flux:table.column></flux:table.row></flux:table.columns>
                            <flux:table.rows class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach($sales as $sale)
                                    <flux:table.row class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40"><flux:table.cell class="whitespace-nowrap px-5 py-3.5 text-zinc-500 dark:text-zinc-400">{{ $sale->created_at->format('d/m/Y · H:i') }}</flux:table.cell><flux:table.cell class="px-5 py-3.5 font-medium text-zinc-900 dark:text-white">{{ $sale->customer?->name ?? 'Client passant' }}</flux:table.cell><flux:table.cell class="px-5 py-3.5 capitalize">{{ $sale->department ?: '—' }}</flux:table.cell><flux:table.cell class="px-5 py-3.5"><flux:badge color="zinc">{{ str_replace('_', ' ', $sale->payment_method) }}</flux:badge></flux:table.cell><flux:table.cell class="px-5 py-3.5 text-right font-semibold tabular-nums text-zinc-900 dark:text-white">{{ \App\Support\DisplayCurrency::format($sale->total_amount) }}</flux:table.cell></flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                </div>
            </div>
        </flux:card>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  TAB 2: STOCKS                                                 --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if($reportType === 'stock')
        <div class="grid gap-6 xl:grid-cols-3">
            <flux:card class="yb-report-screen-card overflow-hidden p-0! xl:col-span-2">
                <div class="flex flex-col gap-4 bg-zinc-50/70 p-5 dark:bg-zinc-950/40 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="flex items-start gap-3"><span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-sky-600 text-white shadow-sm"><flux:icon.arrows-right-left class="size-5" /></span><div><flux:heading size="lg">Mouvements de stock</flux:heading><flux:text size="sm" class="mt-1">Journal administratif des entrées, sorties et ajustements.</flux:text></div></div>
                    <flux:badge color="sky" icon="clipboard-document-list">{{ count($movements) }} opération(s)</flux:badge>
                </div>
                <div class="overflow-x-auto">
                    <flux:table class="w-full text-sm">
                        <flux:table.columns><flux:table.row class="bg-zinc-50/70 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-950/30 dark:text-zinc-400"><flux:table.column class="px-5 py-3.5 sm:px-6"><x-table-heading icon="calendar-days">Date / référence</x-table-heading></flux:table.column><flux:table.column class="px-4 py-3.5"><x-table-heading icon="cube">Produit</x-table-heading></flux:table.column><flux:table.column class="px-4 py-3.5"><x-table-heading icon="arrows-right-left">Opération</x-table-heading></flux:table.column><flux:table.column class="px-4 py-3.5 text-right"><x-table-heading icon="calculator">Quantité</x-table-heading></flux:table.column><flux:table.column class="max-lg:hidden px-5 py-3.5 sm:px-6"><x-table-heading icon="identification">Justification / agent</x-table-heading></flux:table.column></flux:table.row></flux:table.columns>
                        <flux:table.rows class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                            @forelse($movements as $mvt)
                                <flux:table.row class="transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-800/35">
                                    <flux:table.cell class="whitespace-nowrap px-5 py-4 sm:px-6"><p class="font-medium text-zinc-900 dark:text-white">{{ $mvt->created_at->format('d/m/Y') }}</p><p class="mt-1 text-xs text-zinc-500">{{ $mvt->created_at->format('H:i') }} · {{ $mvt->reference ?: 'Sans référence' }}</p></flux:table.cell>
                                    <flux:table.cell class="px-4 py-4"><p class="font-semibold text-zinc-900 dark:text-white">{{ $mvt->product?->name ?? 'Article supprimé' }}</p><p class="mt-1 text-xs text-zinc-500">{{ $mvt->product?->sku ?: 'SKU non défini' }}</p></flux:table.cell>
                                    <flux:table.cell class="px-4 py-4">@if($mvt->type === 'in')<flux:badge color="emerald" icon="arrow-down-tray">Entrée</flux:badge>@else<flux:badge color="amber" icon="arrow-up-tray">Sortie</flux:badge>@endif<p class="mt-1.5 text-xs capitalize text-zinc-500">{{ str_replace('_', ' ', $mvt->reason ?: 'ajustement') }}</p></flux:table.cell>
                                    <flux:table.cell class="px-4 py-4 text-right"><p class="font-bold tabular-nums {{ $mvt->type === 'in' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $mvt->type === 'in' ? '+' : '−' }}{{ number_format((float) $mvt->quantity, 3, ',', ' ') }}</p>@if($mvt->stock_after !== null)<p class="mt-1 text-xs tabular-nums text-zinc-500">Solde {{ number_format((float) $mvt->stock_after, 3, ',', ' ') }}</p>@endif</flux:table.cell>
                                    <flux:table.cell class="max-lg:hidden px-5 py-4 sm:px-6"><p class="text-zinc-700 dark:text-zinc-300">{{ $mvt->description ?: 'Aucune note' }}</p><p class="mt-1 text-xs text-zinc-500">Par {{ $mvt->user?->name ?? 'Système' }}</p></flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row><flux:table.cell colspan="5" class="px-6 py-16 text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800"><flux:icon.arrows-right-left class="size-6" /></span><flux:heading size="lg" class="mt-4">Aucun mouvement</flux:heading><flux:text class="mt-1">Aucune opération enregistrée pour cette période.</flux:text></flux:table.cell></flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>
            </flux:card>

            <flux:card class="yb-report-screen-card h-max overflow-hidden p-0!">
                <div class="flex items-start justify-between gap-3 bg-rose-50/80 p-5 dark:bg-rose-500/8 sm:p-6"><div class="flex items-start gap-3"><span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-rose-600 text-white"><flux:icon.exclamation-triangle class="size-5" /></span><div><flux:heading size="lg">Alertes de rupture</flux:heading><flux:text size="sm" class="mt-1">Articles au seuil ou en dessous.</flux:text></div></div><flux:badge color="rose">{{ count($lowStockProducts) }}</flux:badge></div>
                <div class="space-y-3 p-5 sm:p-6">
                    @forelse($lowStockProducts as $lowProd)
                        @php $stockRatio = max(0, min(100, ((float) $lowProd->stock_qty / max(1, (float) $lowProd->alert_qty)) * 100)); @endphp
                        <article class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/45">
                            <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $lowProd->name }}</p><p class="mt-1 text-xs text-zinc-500">{{ $lowProd->sku ?: 'Sans SKU' }} · Seuil {{ number_format((float) $lowProd->alert_qty, 3, ',', ' ') }}</p></div><span class="shrink-0 rounded-lg bg-rose-600 px-2.5 py-1 text-xs font-bold tabular-nums text-white">{{ number_format((float) $lowProd->stock_qty, 3, ',', ' ') }}</span></div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800"><div class="h-full rounded-full bg-rose-500" style="width: {{ max(4, $stockRatio) }}%"></div></div>
                            <p class="mt-2 text-[11px] font-medium text-rose-600 dark:text-rose-400">Réapprovisionnement recommandé</p>
                        </article>
                    @empty
                        <div class="py-10 text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-emerald-500/10 text-emerald-600"><flux:icon.check-circle class="size-6" /></span><flux:heading size="lg" class="mt-4">Stock sous contrôle</flux:heading><flux:text class="mt-1">Aucun article sous son seuil d’alerte.</flux:text></div>
                    @endforelse
                </div>
            </flux:card>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  TAB 3: SESSIONS                                               --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if($reportType === 'sessions')
        <div class="space-y-6">
            <flux:card class="yb-report-screen-card overflow-hidden p-0!">
                <div class="flex flex-col gap-4 bg-zinc-50/70 p-5 dark:bg-zinc-950/40 sm:flex-row sm:items-center sm:justify-between sm:p-6"><div class="flex items-start gap-3"><span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-violet-600 text-white shadow-sm"><flux:icon.lock-open class="size-5" /></span><div><flux:heading size="lg">Sessions de caisse</flux:heading><flux:text size="sm" class="mt-1">Registre des ouvertures, clôtures et encaissements par agent.</flux:text></div></div><flux:badge color="violet" icon="clock">{{ count($sessions) }} session(s)</flux:badge></div>
                <div class="bg-white p-3 dark:bg-zinc-900 sm:p-4">
                    <div class="overflow-x-auto rounded-2xl ring-1 ring-zinc-200/80 dark:ring-zinc-800">
                    <flux:table class="w-full text-sm">
                        <flux:table.columns><flux:table.row class="bg-zinc-50/90 text-[11px] font-semibold uppercase tracking-[0.08em] text-zinc-500 dark:bg-zinc-950/60 dark:text-zinc-400"><flux:table.column class="w-[34%] px-5 py-4 sm:px-6"><x-table-heading icon="user">Session / caissier</x-table-heading></flux:table.column><flux:table.column class="px-5 py-4"><x-table-heading icon="play">Ouverture</x-table-heading></flux:table.column><flux:table.column class="max-xl:hidden px-5 py-4 print:table-cell"><x-table-heading icon="stop">Clôture</x-table-heading></flux:table.column><flux:table.column class="max-xl:hidden px-5 py-4 text-right print:table-cell"><x-table-heading icon="wallet">Fond initial</x-table-heading></flux:table.column><flux:table.column class="px-5 py-4 text-right"><x-table-heading icon="banknotes">Ventes</x-table-heading></flux:table.column><flux:table.column class="px-5 py-4 text-center sm:px-6"><x-table-heading icon="signal">Statut</x-table-heading></flux:table.column></flux:table.row></flux:table.columns>
                        <flux:table.rows class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                            @forelse($sessions as $session)
                                @php $sessionSales = (float) $session->sales->sum('total_amount'); @endphp
                                <flux:table.row wire:click="$set('selectedSessionId', {{ $session->id }})" class="cursor-pointer transition-colors hover:bg-violet-50/55 dark:hover:bg-violet-500/6 {{ $selectedSessionId == $session->id ? 'bg-violet-50 ring-1 ring-inset ring-violet-200 dark:bg-violet-500/10 dark:ring-violet-500/30' : '' }}">
                                    <flux:table.cell class="px-5 py-4.5 sm:px-6"><div class="flex items-center gap-3.5"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-violet-600 text-white shadow-sm"><flux:icon.user class="size-4.5" /></span><div class="min-w-0"><p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $session->user?->name ?? 'Agent inconnu' }}</p><p class="mt-1 flex items-center gap-1.5 text-xs text-zinc-500"><span class="font-mono">#{{ $session->id }}</span><span>·</span><span>{{ $session->sales->count() }} transaction(s)</span></p></div></div></flux:table.cell>
                                    <flux:table.cell class="whitespace-nowrap px-5 py-4.5"><p class="font-medium tabular-nums text-zinc-900 dark:text-white">{{ optional($session->opened_at ?? $session->created_at)->format('d/m/Y') }}</p><p class="mt-1 flex items-center gap-1 text-xs tabular-nums text-zinc-500"><flux:icon.clock class="size-3.5" />{{ optional($session->opened_at ?? $session->created_at)->format('H:i') }}</p></flux:table.cell>
                                    <flux:table.cell class="max-xl:hidden whitespace-nowrap px-5 py-4.5 print:table-cell">@if($session->closed_at)<p class="font-medium tabular-nums text-zinc-900 dark:text-white">{{ $session->closed_at->format('d/m/Y') }}</p><p class="mt-1 flex items-center gap-1 text-xs tabular-nums text-zinc-500"><flux:icon.clock class="size-3.5" />{{ $session->closed_at->format('H:i') }}</p>@else<flux:badge color="amber" size="sm" icon="clock">En cours</flux:badge>@endif</flux:table.cell>
                                    <flux:table.cell class="max-xl:hidden px-5 py-4.5 text-right font-semibold tabular-nums text-zinc-700 dark:text-zinc-200 print:table-cell">{{ \App\Support\DisplayCurrency::format((float) $session->opening_balance) }}</flux:table.cell>
                                    <flux:table.cell class="px-5 py-4.5 text-right font-bold tabular-nums text-violet-700 dark:text-violet-300">{{ \App\Support\DisplayCurrency::format($sessionSales) }}</flux:table.cell>
                                    <flux:table.cell class="px-5 py-4.5 text-center sm:px-6">@if($session->status === 'open' || !$session->closed_at)<flux:badge color="emerald" icon="signal">Ouverte</flux:badge>@else<flux:badge color="zinc" icon="lock-closed">Fermée</flux:badge>@endif</flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row><flux:table.cell colspan="6" class="px-6 py-16 text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800"><flux:icon.lock-open class="size-6" /></span><flux:heading size="lg" class="mt-4">Aucune session</flux:heading><flux:text class="mt-1">Aucune ouverture de caisse sur cette période.</flux:text></flux:table.cell></flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                    </div>
                </div>
            </flux:card>

            @if($selectedSession)
                @php
                    $selectedSalesTotal = (float) $selectedSession->sales->sum('total_amount');
                    $selectedExpected = (float) $selectedSession->opening_balance + $selectedSalesTotal;
                    $selectedVariance = (float) $selectedSession->closing_balance - $selectedExpected;
                @endphp
                <flux:card class="yb-report-screen-card p-5! sm:p-6!">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><flux:badge color="violet" icon="document-text">Session #{{ $selectedSession->id }}</flux:badge><flux:heading size="lg" class="mt-3">Synthèse de clôture — {{ $selectedSession->user?->name ?? 'Agent' }}</flux:heading><flux:text size="sm" class="mt-1">Ouverte le {{ optional($selectedSession->opened_at ?? $selectedSession->created_at)->format('d/m/Y à H:i') }}</flux:text></div>@if($selectedSession->status === 'open' || !$selectedSession->closed_at)<flux:badge color="emerald" icon="signal">Session en cours</flux:badge>@else<flux:badge color="zinc" icon="check-circle">Clôturée le {{ $selectedSession->closed_at->format('d/m/Y à H:i') }}</flux:badge>@endif</div>
                    <div class="mt-6 grid gap-px overflow-hidden rounded-2xl bg-zinc-200 dark:bg-zinc-800 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="bg-zinc-50 p-4 dark:bg-zinc-950/50"><flux:text size="sm">Fond initial</flux:text><p class="mt-1 text-xl font-semibold tabular-nums">{{ \App\Support\DisplayCurrency::format((float) $selectedSession->opening_balance) }}</p></div>
                        <div class="bg-zinc-50 p-4 dark:bg-zinc-950/50"><flux:text size="sm">Ventes encaissées</flux:text><p class="mt-1 text-xl font-semibold tabular-nums text-violet-700 dark:text-violet-300">{{ \App\Support\DisplayCurrency::format($selectedSalesTotal) }}</p></div>
                        <div class="bg-zinc-50 p-4 dark:bg-zinc-950/50"><flux:text size="sm">Clôture déclarée</flux:text><p class="mt-1 text-xl font-semibold tabular-nums">{{ \App\Support\DisplayCurrency::format((float) $selectedSession->closing_balance) }}</p></div>
                        <div class="bg-zinc-50 p-4 dark:bg-zinc-950/50"><flux:text size="sm">Écart de caisse</flux:text><p class="mt-1 text-xl font-semibold tabular-nums {{ $selectedVariance < 0 ? 'text-rose-600 dark:text-rose-400' : ($selectedVariance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">{{ $selectedVariance > 0 ? '+' : '' }}{{ \App\Support\DisplayCurrency::format($selectedVariance) }}</p></div>
                    </div>
                    @if($selectedSession->note)<div class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200"><span class="font-semibold">Observation :</span> {{ $selectedSession->note }}</div>@endif
                </flux:card>
            @endif

        </div>
    @endif

    </div>

    <footer class="yb-print-footer hidden justify-between border-t bg-white px-1 text-[8px] text-zinc-500" style="border-color: {{ $primary_color }}; {{ $reportDocumentStyle }}">
        <div class="min-w-0"><p class="truncate font-semibold text-zinc-700">{{ $company_footer ?: $company_name.' · Document administratif de gestion' }}</p><p>Document généré automatiquement · Confidentiel — usage interne</p></div>
        <div class="shrink-0 text-right"><p>Édité par {{ auth()->user()->name ?? 'Système' }}</p><p>Page <span class="yb-page-number"></span></p></div>
    </footer>

</div>
