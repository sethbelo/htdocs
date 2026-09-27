<?php

namespace App\Livewire;

use App\Models\AccountingRecord;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Subscription;
use App\Support\LoyaltyProgram;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Customers extends Component
{
    use WithPagination;

    // Customer Fields
    public $name;

    public $phone;

    public $email;

    // Subscription Fields
    public $planName = 'Abonnement Premium Spa Mensuel';

    public $price = 150.00;

    public $startsAt;

    public $endsAt;

    // Profile & UI State
    public $selectedCustomerId;

    public $selectedCustomer = null;

    public $viewMode = 'grid'; // 'grid' or 'list'

    public $showCustomerProfileModal = false;

    // WhatsApp State
    public $showWhatsappModal = false;

    public $whatsappCustomer = null;

    public $selectedTemplate = '';

    public $companyPhone = '';

    // Filters & Modals
    public $search = '';

    public $showCreateCustomerModal = false;

    public $isEditing = false;

    public $editingCustomerId = null;

    public $showSubscriptionModal = false;

    public $filterStatus = 'all';

    public $sort = 'name';

    public $perPage = 12;

    public $showArchiveModal = false;

    public $archiveCustomerId = null;

    public function mount()
    {
        $this->startsAt = Carbon::today()->toDateString();
        $this->endsAt = Carbon::today()->addMonth()->toDateString();

        if (class_exists(Setting::class)) {
            $this->companyPhone = Setting::get('company_phone', '+243 81 000 0000');
        }
    }

    public function editCustomer($id)
    {
        $this->isEditing = true;
        $this->editingCustomerId = $id;
        $customer = Customer::findOrFail($id);

        $this->name = $customer->name;
        $this->phone = $customer->phone;
        $this->email = $customer->email;

        $this->showCreateCustomerModal = true;
    }

    public function openCreateCustomerModal()
    {
        $this->resetValidation();
        $this->reset(['name', 'phone', 'email', 'isEditing', 'editingCustomerId']);
        $this->showCreateCustomerModal = true;
    }

    public function updateCustomer()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
        ]);

        $customer = Customer::findOrFail($this->editingCustomerId);
        $customer->update([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
        ]);

        ActivityLog::log('Modification Client', "Client #{$customer->id} ({$customer->name}) modifié.");

        $this->reset(['name', 'phone', 'email', 'isEditing', 'editingCustomerId']);
        $this->showCreateCustomerModal = false;
        $this->dispatch('notify', 'Client modifié avec succès.');
    }

    public function archiveCustomer($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['is_active' => false]);

        ActivityLog::log('Archivage Client', "Client #{$customer->id} ({$customer->name}) archivé.");
        $this->dispatch('notify', 'Client archivé avec succès.');
    }

    public function askArchiveCustomer($id)
    {
        $this->archiveCustomerId = $id;
        $this->showArchiveModal = true;
    }

    public function confirmArchiveCustomer()
    {
        if ($this->archiveCustomerId) {
            $this->archiveCustomer($this->archiveCustomerId);
        }

        $this->reset(['archiveCustomerId', 'showArchiveModal']);
    }

    public function createCustomer()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
        ]);

        $customer = Customer::create([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'points' => 0,
        ]);

        ActivityLog::log('Création Client', "Client #{$customer->id} ({$customer->name}) enregistré.");

        $this->reset(['name', 'phone', 'email']);
        $this->showCreateCustomerModal = false;
        $this->dispatch('notify', 'Client enregistré avec succès.');
    }

    public function openSubscriptionModal($customerId)
    {
        $this->selectedCustomerId = $customerId;
        $this->showSubscriptionModal = true;
    }

    public function addSubscription()
    {
        $this->validate([
            'selectedCustomerId' => 'required|exists:customers,id',
            'planName' => 'required|string',
            'price' => 'required|numeric|min:0',
            'startsAt' => 'required|date',
            'endsAt' => 'required|date|after_or_equal:startsAt',
        ]);

        $customer = Customer::findOrFail($this->selectedCustomerId);
        $pointsGranted = LoyaltyProgram::usesPoints() ? floor($this->price) : 0;

        DB::transaction(function () use ($customer, $pointsGranted) {
            // 1. Create Subscription
            Subscription::create([
                'customer_id' => $customer->id,
                'plan_name' => $this->planName,
                'price' => $this->price,
                'points_granted' => $pointsGranted,
                'starts_at' => Carbon::parse($this->startsAt),
                'ends_at' => Carbon::parse($this->endsAt),
                'status' => 'active',
            ]);

            // 2. Grant points
            $customer->increment('points', $pointsGranted);

            // 3. SYSCOHADA Accounting Record (Partie double)
            $ref = 'ABON-'.$customer->id.'-'.time();

            AccountingRecord::create([
                'reference' => $ref,
                'record_date' => Carbon::now()->toDateString(),
                'syscohada_code' => '5711', // Caisse par défaut
                'debit' => $this->price,
                'credit' => 0,
                'description' => "Vente Abonnement {$this->planName} - Client {$customer->name} (Paiement)",
                'user_id' => auth()->id(),
            ]);

            AccountingRecord::create([
                'reference' => $ref,
                'record_date' => Carbon::now()->toDateString(),
                'syscohada_code' => '706', // Services
                'debit' => 0,
                'credit' => $this->price,
                'description' => "Vente Abonnement {$this->planName} - Client {$customer->name} (Produit)",
                'user_id' => auth()->id(),
            ]);
        });

        ActivityLog::log('Nouvel Abonnement', "Abonnement {$this->planName} ajouté pour {$customer->name}. points accordés: +{$pointsGranted}");

        $this->showSubscriptionModal = false;
        $this->dispatch('notify', 'Abonnement et points de fidélité ajoutés avec succès.');
    }

    public function viewCustomer($id)
    {
        $this->selectedCustomer = Customer::with(['subscriptions', 'sales' => function ($q) {
            $q->orderBy('created_at', 'desc')->take(5);
        }, 'appointments' => function ($q) {
            $q->where('date_time', '>=', now())->orderBy('date_time', 'asc')->take(5)->with('service');
        }])->findOrFail($id);

        $this->showCustomerProfileModal = true;
    }

    public $whatsappMessage = '';

    public function openWhatsappModal($id)
    {
        $this->whatsappCustomer = Customer::findOrFail($id);
        $this->selectedTemplate = 'reminder';
        $this->generateWhatsappMessage();
        $this->showWhatsappModal = true;
    }

    public function updatedSelectedTemplate()
    {
        $this->generateWhatsappMessage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedSort()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function generateWhatsappMessage()
    {
        if (! $this->whatsappCustomer) {
            return;
        }

        $name = $this->whatsappCustomer->name;
        $companyName = Setting::get('company_name', 'Yeboox App');

        $templates = [
            'reminder' => "Bonjour $name, petit rappel de votre rendez-vous chez $companyName. Nous avons hâte de vous accueillir !",
            'promo' => "Bonjour $name, $companyName vous offre une réduction spéciale sur votre prochain achat. Profitez-en rapidement !",
            'birthday' => "Toute l'équipe de $companyName vous souhaite un très joyeux anniversaire $name !",
            'followup' => "Bonjour $name, nous espérons que vous avez apprécié votre dernière visite chez $companyName. Votre avis nous intéresse.",
            'new_service' => "Bonjour $name ! Découvrez nos nouveautés chez $companyName.",
        ];

        $this->whatsappMessage = $templates[$this->selectedTemplate] ?? '';
    }

    public function render()
    {
        $today = Carbon::today();
        $customersQuery = Customer::with(['subscriptions'])
            ->withCount(['sales', 'subscriptions'])
            ->withSum('sales', 'paid_amount')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });

        if ($this->filterStatus === 'subscribed') {
            $customersQuery->whereHas('subscriptions', fn ($query) => $query
                ->where('status', 'active')->where('ends_at', '>=', $today->toDateString()));
        } elseif ($this->filterStatus === 'without_subscription') {
            $customersQuery->whereDoesntHave('subscriptions', fn ($query) => $query
                ->where('status', 'active')->where('ends_at', '>=', $today->toDateString()));
        } elseif ($this->filterStatus === 'loyal') {
            LoyaltyProgram::usesServices()
                ? $customersQuery->where('loyalty_reward_balance', '>', 0)
                : $customersQuery->where('points', '>=', LoyaltyProgram::pointsRequired(5));
        }

        match ($this->sort) {
            'points_desc' => $customersQuery->orderByDesc('points'),
            'spend_desc' => $customersQuery->orderByDesc('sales_sum_paid_amount'),
            'recent' => $customersQuery->latest(),
            default => $customersQuery->orderBy('name'),
        };

        $activeCustomerQuery = Customer::where('is_active', true);
        $activeSubscriptions = Subscription::where('status', 'active')->where('ends_at', '>=', $today->toDateString())->count();

        return view('livewire.customers', [
            'customers' => $customersQuery->paginate($this->perPage),
            'customerCount' => (clone $activeCustomerQuery)->count(),
            'activeSubscriptions' => $activeSubscriptions,
            'totalPoints' => (clone $activeCustomerQuery)->sum('points'),
            'loyalCustomers' => (clone $activeCustomerQuery)->where('points', '>=', 250)->count(),
            'loyaltyEnabled' => LoyaltyProgram::enabled(),
            'loyaltyMode' => LoyaltyProgram::mode(),
            'loyaltyPointValueUsd' => LoyaltyProgram::pointValueUsd(),
            'loyaltyRequiredServices' => LoyaltyProgram::requiredServices(),
            'loyaltyRewardProduct' => LoyaltyProgram::rewardProductId() ? Product::find(LoyaltyProgram::rewardProductId()) : null,
            'totalRewards' => (clone $activeCustomerQuery)->sum('loyalty_reward_balance'),
            'totalServiceProgress' => (clone $activeCustomerQuery)->sum('loyalty_service_count'),
            'currency' => Setting::get('company_currency', 'CDF'),
        ])->layout('layouts.app');
    }
}
