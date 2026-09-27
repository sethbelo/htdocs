<?php

namespace App\Livewire\Marketing;

use App\Models\CommercialRequest;
use App\Models\Partner;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class HomePage extends Component
{
    /**
     * @var array<string, array{number: string, icon: string, title: string, description: string, eyebrow: string, mockup: string, features: array<int, string>}>
     */
    private const MODULES = [
        'pos' => ['number' => '01', 'icon' => 'shopping-cart', 'title' => 'Caisse & ventes', 'description' => 'Encaissement rapide, tickets, remises, retours et paiements multiples.', 'eyebrow' => 'Vendez sans ralentir', 'mockup' => 'pos', 'features' => ['Catalogue tactile et recherche instantanée', 'Paiements USD, CDF et modes multiples', 'Tickets, remises, retours et clôture de caisse']],
        'catalog' => ['number' => '02', 'icon' => 'squares-2x2', 'title' => 'Produits & catalogue', 'description' => 'Articles, variantes, codes-barres, prix et combos faciles à organiser.', 'eyebrow' => 'Un catalogue toujours net', 'mockup' => 'catalog', 'features' => ['Produits, services, variantes et combos', 'Tarifs par établissement ou canal', 'Import rapide et étiquettes codes-barres']],
        'inventory' => ['number' => '03', 'icon' => 'cube', 'title' => 'Stocks & achats', 'description' => 'Mouvements, inventaires, alertes, fournisseurs et réapprovisionnement.', 'eyebrow' => 'Le bon stock au bon endroit', 'mockup' => 'inventory', 'features' => ['Stocks disponibles en temps réel', 'Alertes de rupture et seuils personnalisés', 'Achats, fournisseurs et inventaires guidés']],
        'customers' => ['number' => '04', 'icon' => 'users', 'title' => 'Clients & fidélité', 'description' => 'Historique, profils, points et suivi personnalisé de chaque client.', 'eyebrow' => 'Transformez une vente en relation', 'mockup' => 'customers', 'features' => ['Fiches clients et historique d’achat', 'Points, récompenses et segments', 'Suivi des habitudes et préférences']],
        'invoicing' => ['number' => '05', 'icon' => 'document-text', 'title' => 'Facturation', 'description' => 'Devis, factures, avoirs, paiements et documents professionnels.', 'eyebrow' => 'Des documents professionnels', 'mockup' => 'invoicing', 'features' => ['Devis convertibles en factures', 'Suivi des échéances et paiements', 'Documents PDF prêts à envoyer']],
        'accounting' => ['number' => '06', 'icon' => 'calculator', 'title' => 'Comptabilité SYSCOHADA', 'description' => 'Écritures et suivi financier adaptés aux exigences de la région.', 'eyebrow' => 'Vos comptes restent lisibles', 'mockup' => 'accounting', 'features' => ['Plan comptable SYSCOHADA', 'Journaux et écritures centralisés', 'États financiers et exports']],
        'locations' => ['number' => '07', 'icon' => 'building-storefront', 'title' => 'Multi-sites', 'description' => 'Une vue consolidée de tous vos établissements et points de vente.', 'eyebrow' => 'Pilotez tout votre réseau', 'mockup' => 'locations', 'features' => ['Comparaison de chaque établissement', 'Stocks et ventes consolidés', 'Paramètres locaux depuis un seul compte']],
        'teams' => ['number' => '08', 'icon' => 'user-group', 'title' => 'Équipes & rôles', 'description' => 'Accès sécurisés, permissions et suivi de l’activité des collaborateurs.', 'eyebrow' => 'Chacun accède à l’essentiel', 'mockup' => 'teams', 'features' => ['Rôles et permissions détaillés', 'Activité sensible toujours tracée', 'Accès adaptés à chaque établissement']],
        'reports' => ['number' => '09', 'icon' => 'chart-bar', 'title' => 'Rapports & pilotage', 'description' => 'Ventes, marges, tendances et indicateurs lisibles en temps réel.', 'eyebrow' => 'Décidez avec vos chiffres', 'mockup' => 'reports', 'features' => ['Indicateurs actualisés en temps réel', 'Comparaisons par période et site', 'Rapports lisibles et exportables']],
        'offline' => ['number' => '10', 'icon' => 'cloud-arrow-down', 'title' => 'Mode hors-ligne', 'description' => 'Continuez à vendre sans internet, puis synchronisez automatiquement.', 'eyebrow' => 'Toujours prêt à encaisser', 'mockup' => 'offline', 'features' => ['Ventes continues sans connexion', 'Synchronisation automatique au retour du réseau', 'État de synchronisation toujours visible']],
        'mobile' => ['number' => '11', 'icon' => 'device-phone-mobile', 'title' => 'Mobile & API', 'description' => 'Gardez vos chiffres à portée de main et connectez vos outils.', 'eyebrow' => 'Votre activité vous accompagne', 'mockup' => 'mobile', 'features' => ['Indicateurs accessibles en mobilité', 'Alertes importantes sur votre téléphone', 'API pour relier vos outils métiers']],
        'security' => ['number' => '12', 'icon' => 'shield-check', 'title' => 'Sécurité & sauvegarde', 'description' => 'Données protégées, traçabilité et continuité de vos opérations.', 'eyebrow' => 'Une base solide pour grandir', 'mockup' => 'security', 'features' => ['Sauvegardes automatiques', 'Journal d’activité et contrôle des accès', 'Données isolées pour chaque organisation']],
    ];

    public string $billing = 'monthly';

    public string $requestType = 'demo';

    public ?int $planId = null;

    public string $companyName = '';

    public string $contactName = '';

    public string $email = '';

    public string $phone = '';

    public string $country = 'RDC';

    public string $city = '';

    public string $businessType = 'restaurant';

    public int $locationsCount = 1;

    public string $preferredContact = 'whatsapp';

    public string $appointmentDate = '';

    public string $appointmentTime = '';

    public string $message = '';

    public bool $consent = false;

    public string $website = '';

    public ?string $confirmationNumber = null;

    public bool $showBookingPanel = false;

    public ?int $selectedPartnerId = null;

    public ?string $selectedModule = null;

    public function showModule(string $module): void
    {
        abort_unless(array_key_exists($module, self::MODULES), 422);

        $this->selectedModule = $module;
    }

    public function closeModule(): void
    {
        $this->selectedModule = null;
    }

    public function selectPlan(int $planId): void
    {
        $this->planId = SubscriptionPlan::query()->where('is_active', true)->findOrFail($planId)->id;
        $this->requestType = 'order';
        $this->confirmationNumber = null;
        $this->selectedModule = null;
        $this->showBookingPanel = true;
    }

    public function chooseRequest(string $type): void
    {
        abort_unless(in_array($type, ['demo', 'order', 'information', 'appointment'], true), 422);
        $this->requestType = $type;
        $this->confirmationNumber = null;
        $this->selectedModule = null;
        $this->showBookingPanel = true;
    }

    public function closeBookingPanel(): void
    {
        $this->showBookingPanel = false;
    }

    public function showPartner(int $partnerId): void
    {
        $this->selectedPartnerId = Partner::query()
            ->where('is_active', true)
            ->findOrFail($partnerId)
            ->id;
    }

    public function closePartner(): void
    {
        $this->selectedPartnerId = null;
    }

    public function submitRequest(): void
    {
        if ($this->website !== '') {
            $this->confirmationNumber = 'YBX-'.now()->format('ymd').'-'.Str::upper(Str::random(5));

            return;
        }

        $rateKey = 'commercial-request:'.request()->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 4)) {
            $this->addError('email', 'Trop de demandes ont été envoyées. Réessayez dans quelques minutes.');

            return;
        }

        $validated = $this->validate([
            'requestType' => ['required', Rule::in(['demo', 'order', 'information', 'appointment'])],
            'planId' => ['nullable', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'companyName' => ['required', 'string', 'min:2', 'max:160'],
            'contactName' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'min:7', 'max:40'],
            'country' => ['required', 'string', 'max:80'],
            'city' => ['required', 'string', 'max:100'],
            'businessType' => ['required', Rule::in(['restaurant', 'commerce', 'beaute', 'services', 'hotel', 'autre'])],
            'locationsCount' => ['required', 'integer', 'between:1,999'],
            'preferredContact' => ['required', Rule::in(['whatsapp', 'telephone', 'email'])],
            'appointmentDate' => ['nullable', 'required_if:requestType,appointment', 'date', 'after_or_equal:today'],
            'appointmentTime' => ['nullable', 'required_if:requestType,appointment', 'date_format:H:i'],
            'message' => ['nullable', 'string', 'max:1500'],
            'consent' => ['accepted'],
        ], [
            'companyName.required' => 'Indiquez le nom de votre entreprise.',
            'contactName.required' => 'Indiquez le nom de la personne à contacter.',
            'email.required' => 'Indiquez une adresse e-mail.',
            'email.email' => 'L’adresse e-mail n’est pas valide.',
            'phone.required' => 'Indiquez un numéro de téléphone ou WhatsApp.',
            'city.required' => 'Indiquez votre ville.',
            'appointmentDate.required_if' => 'Choisissez une date pour le rendez-vous.',
            'appointmentDate.after_or_equal' => 'Choisissez une date à partir d’aujourd’hui.',
            'appointmentTime.required_if' => 'Choisissez une heure pour le rendez-vous.',
            'consent.accepted' => 'Votre accord est requis pour traiter la demande.',
        ]);

        RateLimiter::hit($rateKey, 300);
        $uuid = (string) Str::uuid();
        CommercialRequest::query()->create([
            'uuid' => $uuid,
            'request_type' => $validated['requestType'],
            'subscription_plan_id' => $validated['planId'],
            'company_name' => $validated['companyName'],
            'contact_name' => $validated['contactName'],
            'email' => mb_strtolower($validated['email']),
            'phone' => $validated['phone'],
            'country' => $validated['country'],
            'city' => $validated['city'],
            'business_type' => $validated['businessType'],
            'locations_count' => $validated['locationsCount'],
            'preferred_contact' => $validated['preferredContact'],
            'appointment_date' => $validated['appointmentDate'] ?: null,
            'appointment_time' => $validated['appointmentTime'] ?: null,
            'timezone' => $validated['requestType'] === 'appointment' ? config('app.timezone') : null,
            'message' => $validated['message'] ?: null,
            'status' => 'new',
            'consent' => true,
            'source' => 'website-home',
            'metadata' => ['billing' => $this->billing, 'locale' => app()->getLocale()],
        ]);

        $this->confirmationNumber = 'YBX-'.now()->format('ymd').'-'.Str::upper(Str::substr(str_replace('-', '', $uuid), 0, 6));
        $this->reset(['companyName', 'contactName', 'email', 'phone', 'city', 'appointmentDate', 'appointmentTime', 'message', 'consent', 'website']);
    }

    public function render(): View
    {
        $salesWhatsapp = preg_replace('/\D+/', '', (string) config('services.yeboox.sales_whatsapp'));
        $whatsappMessage = rawurlencode('Bonjour l’équipe Yeboox, je souhaite découvrir la solution POS et échanger avec un conseiller commercial.');
        $selectedPartner = $this->selectedPartnerId === null
            ? null
            : Partner::query()->where('is_active', true)->find($this->selectedPartnerId);
        $activeModule = $this->selectedModule === null
            ? null
            : self::MODULES[$this->selectedModule];

        return view('livewire.marketing.home-page', [
            'modules' => self::MODULES,
            'activeModule' => $activeModule,
            'plans' => SubscriptionPlan::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'partners' => Partner::query()->where('is_active', true)->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('id')->get(),
            'selectedPartner' => $selectedPartner,
            'whatsappUrl' => 'https://wa.me/'.$salesWhatsapp.'?text='.$whatsappMessage,
        ])->layout('components.layouts.marketing');
    }
}
