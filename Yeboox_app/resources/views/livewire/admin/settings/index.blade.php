<div>
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <flux:heading size="xl">Centre de configuration</flux:heading>
            <flux:text class="mt-1">Identité, monnaies, impression, apparence et accès de votre entreprise.</flux:text>
        </div>
        <flux:badge color="emerald" icon="check-circle">Configuration centralisée</flux:badge>
    </div>

    <div class="mb-6 overflow-x-auto rounded-2xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex min-w-max gap-1" role="tablist" aria-label="Rubriques des paramètres">
            @foreach([
                'company' => ['building-office-2', 'Entreprise'],
                'modules' => ['squares-plus', 'Modules'],
                'currencies' => ['banknotes', 'Devises'],
                'documents' => ['document-text', 'Documents PDF'],
                'ticket' => ['printer', 'Ticket thermique'],
                'appearance' => ['swatch', 'Apparence'],
                'loyalty' => ['gift', 'Fidélité'],
                'users' => ['user-group', 'Utilisateurs'],
            ] as $tab => [$icon, $label])
                <flux:button
                    wire:click="selectTab('{{ $tab }}')"
                    :variant="$activeTab === $tab ? 'primary' : 'subtle'"
                    :icon="$icon"
                    role="tab"
                    :aria-selected="$activeTab === $tab ? 'true' : 'false'"
                >{{ $label }}</flux:button>
            @endforeach
        </div>
    </div>

    @if($activeTab === 'company')
        <form wire:submit="saveCompany" class="grid gap-6 xl:grid-cols-[1fr_320px]">
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">Informations de l’entreprise</flux:heading>
                    <flux:text size="sm">Ces informations apparaissent sur vos documents commerciaux.</flux:text>
                </div>
                <div class="grid gap-5 md:grid-cols-2">
                    <flux:input wire:model="name" label="Nom de l’entreprise" required />
                    <flux:input wire:model="slogan" label="Slogan" placeholder="Votre promesse en une phrase" />
                    <flux:select wire:model.live="type" label="Type de magasin">
                        @foreach($types as $storeType)
                            <flux:select.option value="{{ $storeType->value }}">{{ $storeType->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="phone" label="Téléphone" type="tel" />
                    <flux:input wire:model="email" label="E-mail" type="email" />
                    <flux:input wire:model="website" label="Site web" type="url" placeholder="https://" />
                    <flux:input wire:model="rccm" label="RCCM" />
                    <flux:input wire:model="id_nat" label="ID national" />
                    <flux:input wire:model="import_id" label="ID importateur" />
                    <flux:input wire:model="nif" label="NIF" />
                    <flux:input wire:model="vat_number" label="Numéro TVA" />
                    <flux:input wire:model="city" label="Ville" />
                </div>
                <flux:textarea wire:model="address" label="Adresse complète" rows="3" />
                <flux:textarea wire:model="document_footer" label="Pied de page des documents" rows="3" placeholder="Merci de votre confiance…" />
                <div class="flex justify-end"><flux:button type="submit" variant="primary" icon="check">Enregistrer l’entreprise</flux:button></div>
            </flux:card>

            <div class="space-y-6">
                <flux:card>
                    <flux:heading>Logo</flux:heading>
                    <div class="my-5 grid aspect-video place-items-center rounded-xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                        @if($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="Aperçu du logo" class="max-h-28 max-w-[80%] object-contain">
                        @elseif($establishment->logo_url)
                            <img src="{{ $establishment->logo_url }}" alt="Logo" class="max-h-28 max-w-[80%] object-contain">
                        @else
                            <div class="text-center text-zinc-400"><flux:icon.photo class="mx-auto size-9"/><p class="mt-2 text-sm">Aucun logo</p></div>
                        @endif
                    </div>
                    <flux:input wire:model="logo" type="file" accept="image/png,image/jpeg,image/webp" />
                    <flux:text size="sm" class="mt-2">PNG, JPG ou WebP, 2 Mo maximum.</flux:text>
                    @error('logo') <flux:text class="mt-2 text-red-600">{{ $message }}</flux:text> @enderror
                </flux:card>
                <flux:callout icon="information-circle" heading="Identité cohérente">Le nom, le logo, les identifiants fiscaux et le pied de page seront repris sur les tickets et PDF.</flux:callout>
            </div>
        </form>
    @endif

    @if($activeTab === 'modules')
        <form wire:submit="saveModules" class="space-y-6">
            <div class="grid gap-6 lg:grid-cols-2">
                <flux:card class="border-orange-200 bg-gradient-to-br from-orange-50 to-amber-50 dark:border-orange-900 dark:from-orange-950/30 dark:to-amber-950/20">
                    <div class="flex items-start justify-between gap-3">
                        <div class="grid size-12 place-items-center rounded-2xl bg-orange-500 text-white shadow-lg shadow-orange-500/20"><flux:icon.fire class="size-6" /></div>
                        <flux:badge :color="$kitchen_enabled ? 'emerald' : 'zinc'">{{ $kitchen_enabled ? 'Activé' : 'Désactivé' }}</flux:badge>
                    </div>
                    <flux:heading size="lg" class="mt-5">Cuisine et bar</flux:heading>
                    <flux:text size="sm" class="mt-1">Écran de préparation en temps réel et transmission des commandes depuis la caisse.</flux:text>
                    @if(in_array($type, ['restaurant', 'bar'], true))
                        <flux:switch wire:model.live="kitchen_enabled" class="mt-6" label="Activer manuellement le module Cuisine" />
                    @else
                        <flux:callout class="mt-5" variant="warning" icon="information-circle">Disponible uniquement pour les types Restaurant et Bar.</flux:callout>
                    @endif
                </flux:card>

                <flux:card class="border-violet-200 bg-gradient-to-br from-violet-50 to-fuchsia-50 dark:border-violet-900 dark:from-violet-950/30 dark:to-fuchsia-950/20">
                    <div class="flex items-start justify-between gap-3">
                        <div class="grid size-12 place-items-center rounded-2xl bg-violet-600 text-white shadow-lg shadow-violet-500/20"><flux:icon.calendar-days class="size-6" /></div>
                        <flux:badge :color="$reservations_enabled ? 'emerald' : 'zinc'">{{ $reservations_enabled ? 'Activé' : 'Désactivé' }}</flux:badge>
                    </div>
                    <flux:heading size="lg" class="mt-5">Réservations clients</flux:heading>
                    <flux:text size="sm" class="mt-1">Planning mensuel, attribution des tables et suivi des confirmations clients.</flux:text>
                    <flux:switch wire:model.live="reservations_enabled" class="mt-6" label="Activer manuellement le module Réservations" />
                </flux:card>

                <flux:card class="border-rose-200 bg-gradient-to-br from-rose-50 to-pink-50 dark:border-rose-900 dark:from-rose-950/30 dark:to-pink-950/20">
                    <div class="flex items-start justify-between gap-3">
                        <div class="grid size-12 place-items-center rounded-2xl bg-rose-600 text-white shadow-lg shadow-rose-500/20"><flux:icon.clock class="size-6" /></div>
                        <flux:badge :color="$appointments_enabled ? 'emerald' : 'zinc'">{{ $appointments_enabled ? 'Activé' : 'Désactivé' }}</flux:badge>
                    </div>
                    <flux:heading size="lg" class="mt-5">Rendez-vous</flux:heading>
                    <flux:text size="sm" class="mt-1">Agenda de prestations pour Spa, Salon et tout autre magasin travaillant sur rendez-vous.</flux:text>
                    <flux:switch wire:model.live="appointments_enabled" class="mt-6" label="Activer manuellement le module Rendez-vous" />
                </flux:card>

                <flux:card class="border-cyan-200 bg-gradient-to-br from-cyan-50 to-sky-50 dark:border-cyan-900 dark:from-cyan-950/30 dark:to-sky-950/20">
                    <div class="flex items-start justify-between gap-3">
                        <div class="grid size-12 place-items-center rounded-2xl bg-cyan-600 text-white shadow-lg shadow-cyan-500/20"><flux:icon.document-text class="size-6" /></div>
                        <flux:badge :color="$pos_provisional_receipt_enabled ? 'emerald' : 'zinc'">{{ $pos_provisional_receipt_enabled ? 'Activé' : 'Désactivé' }}</flux:badge>
                    </div>
                    <flux:heading size="lg" class="mt-5">Addition / reçu proforma</flux:heading>
                    <flux:text size="sm" class="mt-1">Imprime depuis le point de vente un document provisoire qui n’encaisse pas la commande.</flux:text>
                    @if(in_array($type, ['restaurant', 'bar'], true))
                        <flux:switch wire:model.live="pos_provisional_receipt_enabled" class="mt-6" label="Activer au point de vente" />
                        @if($pos_provisional_receipt_enabled)
                            <flux:select wire:model="pos_provisional_receipt_type" class="mt-4" label="Document proposé">
                                <flux:select.option value="addition">Addition</flux:select.option>
                                <flux:select.option value="proforma">Reçu proforma</flux:select.option>
                            </flux:select>
                        @endif
                    @else
                        <flux:callout class="mt-5" variant="warning" icon="information-circle">Disponible uniquement pour les types Restaurant et Bar.</flux:callout>
                    @endif
                </flux:card>
            </div>
            @if($appointments_enabled)
                <flux:card class="space-y-5">
                    <div><flux:heading>Rappels WhatsApp</flux:heading><flux:text size="sm">Les variables disponibles sont {client}, {service}, {date}, {heure} et {entreprise}.</flux:text></div>
                    <div class="grid gap-5 lg:grid-cols-[220px_1fr]">
                        <flux:input wire:model="appointment_reminder_hours" type="number" min="1" max="720" label="Rappel à préparer" suffix="heures avant" />
                        <flux:textarea wire:model="appointment_whatsapp_template" label="Message WhatsApp" rows="3" />
                    </div>
                </flux:card>
            @endif
            <div class="flex justify-end"><flux:button type="submit" variant="primary" icon="check">Enregistrer les modules</flux:button></div>
        </form>
    @endif

    @if($activeTab === 'currencies')
        <form wire:submit="saveCurrencies" class="grid gap-6 lg:grid-cols-2">
            <flux:card class="space-y-6">
                <div><flux:heading size="lg">Devises acceptées</flux:heading><flux:text size="sm">Choisissez la monnaie principale et les monnaies visibles en caisse.</flux:text></div>
                <flux:select wire:model="default_currency_id" label="Devise principale">
                    @foreach($currencies as $currency)
                        <flux:select.option value="{{ $currency->id }}">{{ $currency->code }} — {{ $currency->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:checkbox.group wire:model="enabled_currencies" label="Devises actives" class="grid gap-3 sm:grid-cols-3">
                    @foreach($currencies as $currency)
                        <flux:checkbox value="{{ $currency->code }}" label="{{ $currency->code }} ({{ $currency->symbol }})" />
                    @endforeach
                </flux:checkbox.group>
            </flux:card>
            <flux:card class="space-y-6">
                <div><flux:heading size="lg">Taux fixe USD / CDF</flux:heading><flux:text size="sm">Le taux est historisé chaque jour et utilisable dans les deux sens.</flux:text></div>
                <div class="rounded-2xl bg-blue-50 p-5 dark:bg-blue-950/30">
                    <flux:text size="sm">1 dollar américain vaut</flux:text>
                    <div class="mt-2 flex items-center gap-3"><span class="text-2xl font-semibold">$ 1</span><flux:icon.equals class="text-zinc-400"/><div class="flex-1"><flux:input wire:model="usd_cdf_rate" type="number" min="0" step="0.01" suffix="CDF" /></div></div>
                    <flux:text size="sm" class="mt-3">Taux inverse : 1 CDF = {{ $usd_cdf_rate > 0 ? number_format(1 / $usd_cdf_rate, 6, ',', ' ') : '0' }} USD</flux:text>
                </div>
                <div class="flex justify-end"><flux:button type="submit" variant="primary" icon="arrows-right-left">Enregistrer les devises</flux:button></div>
            </flux:card>
        </form>
    @endif

    @if($activeTab === 'documents')
        <form wire:submit="saveDocuments" class="space-y-6">
            <flux:card>
                <div class="mb-6"><flux:heading size="lg">Modèle des rapports PDF</flux:heading><flux:text size="sm">Le modèle choisi s’applique aux documents PDF et reçus exportés.</flux:text></div>
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach([
                        'modern' => ['Moderne', 'En-tête aéré, accent de marque et lecture rapide.'],
                        'classic' => ['Classique', 'Cadres structurés et présentation administrative.'],
                        'compact' => ['Compact', 'Consomme moins de papier et maximise les données.'],
                    ] as $key => [$label, $description])
                        <label class="cursor-pointer rounded-2xl border p-5 transition {{ $pdf_template === $key ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/15 dark:bg-blue-950/30' : 'border-zinc-200 hover:border-zinc-400 dark:border-zinc-700' }}">
                            <input type="radio" wire:model.live="pdf_template" value="{{ $key }}" class="sr-only">
                            <div class="mb-4 flex h-32 flex-col gap-2 rounded-lg bg-white p-4 shadow-sm dark:bg-zinc-800">
                                <div class="h-3 w-1/2 rounded bg-blue-500"></div><div class="h-2 w-full rounded bg-zinc-200 dark:bg-zinc-700"></div><div class="h-2 w-4/5 rounded bg-zinc-200 dark:bg-zinc-700"></div><div class="mt-auto h-8 rounded bg-zinc-100 dark:bg-zinc-700"></div>
                            </div>
                            <flux:heading>{{ $label }}</flux:heading><flux:text size="sm">{{ $description }}</flux:text>
                        </label>
                    @endforeach
                </div>
                <div class="mt-6 flex justify-end"><flux:button type="submit" variant="primary" icon="document-check">Enregistrer le modèle</flux:button></div>
            </flux:card>
        </form>
    @endif

    @if($activeTab === 'ticket')
        <form wire:submit="saveTicket" class="space-y-6">
            <flux:card>
                <div class="mb-5"><flux:heading size="lg">Choisissez un modèle de ticket</flux:heading><flux:text size="sm">Six présentations optimisées pour les imprimantes thermiques.</flux:text></div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                    @foreach([
                        'minimal' => ['Minimal', 'Léger et direct', 'bars-2'],
                        'classic' => ['Classique', 'Cadre traditionnel', 'document-text'],
                        'modern' => ['Moderne', 'Clair et aéré', 'sparkles'],
                        'elegant' => ['Élégant', 'Typographie raffinée', 'star'],
                        'bold' => ['Impact', 'Total très visible', 'bolt'],
                        'compact' => ['Compact', 'Économie de papier', 'arrows-pointing-in'],
                    ] as $key => [$label, $description, $icon])
                        <label class="cursor-pointer rounded-2xl border p-4 transition {{ $ticket_template === $key ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/15 dark:bg-blue-950/30' : 'border-zinc-200 hover:border-zinc-400 dark:border-zinc-700' }}">
                            <input type="radio" wire:model.live="ticket_template" value="{{ $key }}" class="sr-only">
                            <div class="mb-3 grid h-20 place-items-center rounded-xl bg-zinc-50 dark:bg-zinc-800"><flux:icon :name="$icon" class="size-7 text-blue-500" /></div>
                            <p class="font-medium">{{ $label }}</p><flux:text size="sm">{{ $description }}</flux:text>
                        </label>
                    @endforeach
                </div>
            </flux:card>

            <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
                <div class="space-y-6">
                    <flux:card class="space-y-6">
                        <div><flux:heading size="lg">Format et typographie</flux:heading><flux:text size="sm">Personnalisez la taille du papier, la police et la couleur.</flux:text></div>
                        <div class="grid gap-5 md:grid-cols-2">
                            <flux:select wire:model.live="paper_size" label="Format du papier"><flux:select.option value="58">Thermique 58 mm</flux:select.option><flux:select.option value="80">Thermique 80 mm</flux:select.option><flux:select.option value="a4">A4</flux:select.option></flux:select>
                            <flux:select wire:model.live="ticket_font" label="Police"><flux:select.option value="DejaVu Sans">DejaVu Sans</flux:select.option><flux:select.option value="Courier New">Courier New</flux:select.option><flux:select.option value="Helvetica">Helvetica</flux:select.option></flux:select>
                            <flux:input wire:model.live="ticket_font_size" label="Taille de police" type="number" min="8" max="18" suffix="px" />
                            <flux:field><flux:label>Couleur du texte</flux:label><div class="flex gap-3"><input wire:model.live="ticket_color" type="color" class="h-10 w-14 cursor-pointer rounded-lg border border-zinc-300 p-1 dark:border-zinc-700"><flux:input wire:model.live="ticket_color" class="flex-1" /></div><flux:error name="ticket_color" /></flux:field>
                            <flux:input wire:model.live="ticket_logo_width" type="number" min="16" max="40" label="Largeur du logo" suffix="mm" />
                            <flux:select wire:model.live="ticket_code_type" label="Code de vérification"><flux:select.option value="none">Aucun code</flux:select.option><flux:select.option value="barcode">Code-barres</flux:select.option><flux:select.option value="qrcode">QR code</flux:select.option></flux:select>
                        </div>
                    </flux:card>

                    <flux:card class="space-y-5">
                        <div><flux:heading size="lg">Contenu du ticket</flux:heading><flux:text size="sm">Choisissez les informations affichées sur la facture ou l’addition.</flux:text></div>
                        <flux:input wire:model.live="ticket_header_text" label="Titre du ticket" placeholder="REÇU DE PAIEMENT" />
                        <flux:textarea wire:model.live="ticket_footer_text" label="Message de pied de page" rows="2" />
                        <div class="grid gap-4 sm:grid-cols-2"><flux:switch wire:model.live="show_logo_on_ticket" label="Logo centré" description="Logo réduit en haut du ticket."/><flux:switch wire:model="auto_print_ticket" label="Impression automatique"/><flux:switch wire:model.live="ticket_show_fiscal_info" label="Informations fiscales" description="RCCM, ID national, NIF et TVA."/><flux:switch wire:model.live="ticket_show_tax_details" label="Détail de la TVA"/><flux:switch wire:model.live="ticket_show_payment_details" label="Cash et monnaie rendue"/></div>
                    </flux:card>
                    <div class="flex justify-end"><flux:button type="submit" variant="primary" icon="printer">Enregistrer et appliquer le ticket</flux:button></div>
                </div>

                <flux:card class="h-fit xl:sticky xl:top-24">
                    <div class="flex items-center justify-between"><flux:heading>Aperçu en direct</flux:heading><flux:badge color="blue">{{ strtoupper($ticket_template) }}</flux:badge></div>
                    <div class="mx-auto mt-5 overflow-hidden bg-white shadow-xl {{ $ticket_template === 'classic' ? 'border-2 border-zinc-800' : '' }} {{ $ticket_template === 'elegant' ? 'rounded-t-[3rem]' : '' }}" style="width: {{ $paper_size === '58' ? '230px' : '320px' }}; color: {{ $ticket_color }}; font-family: '{{ $ticket_font }}', sans-serif; font-size: {{ $ticket_font_size }}px; padding: {{ $ticket_template === 'compact' ? '10px' : '20px' }};">
                        <div class="text-center">
                            @if($show_logo_on_ticket)<div class="mx-auto mb-2 grid place-items-center rounded-lg bg-zinc-900 font-bold text-white" style="width: {{ min($ticket_logo_width * 1.6, 64) }}px; height: 30px;">Y</div>@endif
                            <strong class="{{ $ticket_template === 'bold' ? 'text-lg' : '' }}">{{ $name ?: 'Votre entreprise' }}</strong>
                            @if($slogan)<div>{{ $slogan }}</div>@endif
                            @if($ticket_show_fiscal_info)<div class="mt-1 text-[9px]">RCCM: {{ $rccm ?: 'CD/KIN/0001' }}<br>ID NAT: {{ $id_nat ?: '01-0001' }} · TVA: {{ $vat_number ?: 'A0001' }}</div>@endif
                        </div>
                        <div class="my-3 border-y border-dashed py-2 text-center font-bold">{{ $ticket_header_text }}</div>
                        <div class="flex justify-between text-[10px]"><span>N° FAC-260827-001</span><span>27/08/26 14:30</span></div>
                        <div class="my-3 border-t border-dashed pt-2"><div class="flex justify-between"><span>Article exemple ×2</span><span>10 000 FC</span></div></div>
                        @if($ticket_show_tax_details)<div class="flex justify-between"><span>TVA (16%)</span><span>1 379 FC</span></div>@endif
                        <div class="my-2 flex justify-between border-y-2 border-zinc-800 py-2 text-base font-black"><span>TOTAL</span><span>10 000 FC</span></div>
                        @if($ticket_show_payment_details)<div class="space-y-1"><div class="flex justify-between"><span>CASH</span><span>15 000 FC</span></div><div class="flex justify-between font-bold"><span>CHANGE</span><span>5 000 FC</span></div></div>@endif
                        @if($ticket_code_type !== 'none')
                            <div class="mt-4 text-center">
                                @if($ticket_code_type === 'qrcode')<svg class="mx-auto size-16" viewBox="0 0 32 32" fill="currentColor"><path d="M2 2h12v12H2V2Zm3 3v6h6V5H5Zm13-3h12v12H18V2Zm3 3v6h6V5h-6ZM2 18h12v12H2V18Zm3 3v6h6v-6H5Zm13-3h3v3h-3v-3Zm6 0h6v3h-3v3h3v6h-6v-3h-3v3h-3v-6h3v-3h3v3h3v-3h-3v-3Z"/></svg>@else<svg class="mx-auto h-12 w-full" viewBox="0 0 180 48"><g fill="currentColor">@foreach([4,8,14,17,23,29,32,39,45,49,56,62,67,74,78,85,91,95,102,108,114,119,126,132,139,145,151,157,164,170] as $x)<rect x="{{ $x }}" y="2" width="{{ $loop->even ? 3 : 1 }}" height="36"/>@endforeach</g><text x="90" y="47" text-anchor="middle" font-size="8">FAC-260827-001</text></svg>@endif
                            </div>
                        @endif
                        <p class="mt-4 text-center font-medium">{{ $ticket_footer_text }}</p>
                    </div>
                </flux:card>
            </div>
        </form>
    @endif

    @if($activeTab === 'appearance')
        <form wire:submit="saveAppearance" class="grid gap-6 lg:grid-cols-2">
            <flux:card class="space-y-6">
                <div><flux:heading size="lg">Thème de l’interface</flux:heading><flux:text size="sm">Choisissez le mode d’affichage par défaut de l’administration.</flux:text></div>
                <flux:radio.group wire:model.live="theme_mode" x-on:change="setTheme($event.target.value)" label="Mode" variant="cards" class="grid gap-3 sm:grid-cols-3">
                    <flux:radio value="light" label="Clair" description="Fond lumineux" />
                    <flux:radio value="dark" label="Sombre" description="Confort nocturne" />
                    <flux:radio value="system" label="Système" description="Suit l’appareil" />
                </flux:radio.group>
            </flux:card>
            <flux:card class="space-y-6">
                <div><flux:heading size="lg">Couleur de marque</flux:heading><flux:text size="sm">Utilisée pour les actions importantes et les accents.</flux:text></div>
                <flux:field><flux:label>Couleur principale</flux:label><div class="flex gap-3"><input wire:model.live="theme_color" type="color" class="h-11 w-16 cursor-pointer rounded-xl border border-zinc-300 p-1 dark:border-zinc-700"><flux:input wire:model.live="theme_color" class="flex-1" /></div></flux:field>
                <div class="rounded-2xl p-6 text-white" style="background: {{ $theme_color }}"><p class="text-sm opacity-80">Aperçu</p><p class="mt-1 text-xl font-semibold">{{ $name ?: 'Yeboox POS' }}</p></div>
                <div class="flex justify-end"><flux:button type="submit" variant="primary" icon="swatch">Appliquer le thème</flux:button></div>
            </flux:card>
        </form>
    @endif

    @if($activeTab === 'loyalty')
        <form wire:submit="saveLoyalty" class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <flux:card class="space-y-6">
                <div><flux:heading size="lg">Programme de fidélité</flux:heading><flux:text size="sm">Les points sont ajoutés automatiquement quand une vente est associée à un client.</flux:text></div>
                <flux:switch wire:model="loyalty_enabled" label="Activer les points de fidélité" />
                <div class="grid gap-5 md:grid-cols-2">
                    <flux:input wire:model="loyalty_unit_amount" type="number" min="1" label="Tranche d’achat" suffix="CDF" description="Montant nécessaire pour gagner des points." />
                    <flux:input wire:model="loyalty_points_per_unit" type="number" min="1" label="Points par tranche" suffix="points" />
                    <flux:input wire:model="loyalty_point_value" type="number" min="0" label="Valeur d’un point" suffix="CDF" />
                    <flux:input wire:model="loyalty_expiry_months" type="number" min="0" label="Expiration" suffix="mois" description="0 signifie sans expiration." />
                </div>
                <div class="flex justify-end"><flux:button type="submit" variant="primary" icon="gift">Enregistrer la fidélité</flux:button></div>
            </flux:card>
            <div class="space-y-6">
                <flux:card class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/20">
                    <flux:icon.sparkles class="size-8 text-amber-500"/><flux:heading size="lg" class="mt-4">Exemple de gain</flux:heading>
                    <p class="mt-3 text-3xl font-semibold">{{ $loyalty_unit_amount > 0 ? floor(10000 / $loyalty_unit_amount) * $loyalty_points_per_unit : 0 }} points</p>
                    <flux:text>pour un achat de 10 000 CDF</flux:text>
                </flux:card>
                <flux:button href="{{ route('admin.customers.index') }}" variant="filled" icon="users" class="w-full">Voir les comptes clients</flux:button>
            </div>
        </form>
    @endif

    @if($activeTab === 'users')
        <div class="space-y-6">
            <div class="flex items-center justify-between"><div><flux:heading size="lg">Gérer les utilisateurs</flux:heading><flux:text size="sm">Créez les comptes, attribuez les rôles et suspendez les accès.</flux:text></div>@can('users.create')<flux:button wire:click="openUserForm" variant="primary" icon="user-plus">Nouvel utilisateur</flux:button>@endcan</div>
            @if($showUserForm)
                <flux:card class="border-blue-200 dark:border-blue-900">
                    <form wire:submit="saveUser" class="space-y-5">
                        <div class="flex items-center justify-between"><flux:heading>{{ $editingUserId ? 'Modifier l’utilisateur' : 'Nouvel utilisateur' }}</flux:heading><flux:button wire:click="$set('showUserForm', false)" type="button" variant="subtle" icon="x-mark" /></div>
                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            <flux:input wire:model="user_name" label="Nom complet" required />
                            <flux:input wire:model="user_email" label="E-mail" type="email" required />
                            <flux:input wire:model="user_phone" label="Téléphone" />
                            <flux:select wire:model="user_role" label="Rôle">@foreach($roles as $role)<flux:select.option value="{{ $role->name }}">{{ ucfirst($role->name) }}</flux:select.option>@endforeach</flux:select>
                            <flux:select wire:model="user_status" label="Statut"><flux:select.option value="active">Actif</flux:select.option><flux:select.option value="suspended">Suspendu</flux:select.option></flux:select>
                            <flux:input wire:model="user_password" label="{{ $editingUserId ? 'Nouveau mot de passe (optionnel)' : 'Mot de passe' }}" type="password" viewable />
                        </div>
                        <div class="flex justify-end gap-2"><flux:button wire:click="$set('showUserForm', false)" type="button">Annuler</flux:button><flux:button type="submit" variant="primary">Enregistrer</flux:button></div>
                    </form>
                </flux:card>
            @endif
            <flux:card class="overflow-hidden p-0">
                <flux:table>
                    <flux:table.columns><flux:table.column>Utilisateur</flux:table.column><flux:table.column>Rôle</flux:table.column><flux:table.column>Statut</flux:table.column><flux:table.column align="end">Action</flux:table.column></flux:table.columns>
                    <flux:table.rows>
                        @foreach($users as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell><div class="flex items-center gap-3"><flux:avatar :initials="$user->initials"/><div><p class="font-medium">{{ $user->name }}</p><flux:text size="sm">{{ $user->email }}</flux:text></div></div></flux:table.cell>
                                <flux:table.cell><flux:badge>{{ ucfirst($user->roles->first()?->name ?? 'Sans rôle') }}</flux:badge></flux:table.cell>
                                <flux:table.cell><flux:badge :color="$user->status->value === 'active' ? 'emerald' : 'red'">{{ $user->status->label() }}</flux:badge></flux:table.cell>
                                <flux:table.cell align="end">@can('users.edit')<flux:button wire:click="openUserForm({{ $user->id }})" size="sm" variant="subtle" icon="pencil-square">Modifier</flux:button>@endcan</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        </div>
    @endif
</div>
