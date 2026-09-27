<?php

namespace App\Filament\Pages;

use App\Models\FeeType;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ProFeeManagement extends Page
{
    protected static ?string $title = 'Gestion des Frais Scolaires';
    protected static ?string $navigationLabel = 'Frais Scolaires';
    protected static ?string $navigationGroup = 'Finances';
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $slug = 'frais-scolaires';

    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-fee-management';

    public $feeTypes = [];
    public $searchQuery = '';

    // Form fields for create/edit
    public $fee_type_id;
    public $name;
    public $amount;
    public $currency = 'USD';
    public $exchange_rate = 2500.00;
    public $ohada_account_code = '706';

    public $isModalOpen = false;
    public $editMode = false;

    public function mount()
    {
        abort_unless(Auth::user() && Auth::user()->hasAnyRole(['Super Admin', 'Comptable', 'Caissier', 'Directeur General', 'Directeur Général']), 403, 'Accès refusé. Réservé au service financier.');

        $this->loadFeeTypes();
    }

    public function updatedSearchQuery()
    {
        $this->loadFeeTypes();
    }

    public function loadFeeTypes()
    {
        $query = FeeType::query();
        if ($this->searchQuery) {
            $query->where('name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('ohada_account_code', 'like', '%' . $this->searchQuery . '%');
        }
        $this->feeTypes = $query->orderBy('name')->get()->toArray();
    }

    public function openCreateModal()
    {
        abort_if(Auth::user()->hasRole('Caissier'), 403, 'Accès non autorisé.');
        $this->resetValidation();
        $this->reset(['fee_type_id', 'name', 'amount', 'currency', 'exchange_rate', 'ohada_account_code']);
        $this->currency = 'USD';
        $this->exchange_rate = 2500.00;
        $this->ohada_account_code = '706';
        $this->editMode = false;
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        abort_if(Auth::user()->hasRole('Caissier'), 403, 'Accès non autorisé.');
        $this->resetValidation();
        $feeType = FeeType::findOrFail($id);
        $this->fee_type_id = $feeType->id;
        $this->name = $feeType->name;
        $this->amount = $feeType->amount;
        $this->currency = $feeType->currency;
        $this->exchange_rate = $feeType->exchange_rate;
        $this->ohada_account_code = $feeType->ohada_account_code;
        $this->editMode = true;
        $this->isModalOpen = true;
    }

    protected $rules = [
        'name' => 'required|string|max:255',
        'amount' => 'required|numeric|min:0',
        'currency' => 'required|in:USD,CDF',
        'exchange_rate' => 'required|numeric|min:1',
        'ohada_account_code' => 'nullable|string|max:10',
    ];

    public function save()
    {
        abort_if(Auth::user()->hasRole('Caissier'), 403, 'Accès non autorisé.');
        $this->validate();

        $data = [
            'name' => $this->name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'ohada_account_code' => $this->ohada_account_code,
        ];

        if ($this->editMode && $this->fee_type_id) {
            FeeType::findOrFail($this->fee_type_id)->update($data);
            session()->flash('success', 'Type de frais modifié avec succès !');
        } else {
            FeeType::create($data);
            session()->flash('success', 'Nouveau type de frais créé avec succès !');
        }

        $this->isModalOpen = false;
        $this->loadFeeTypes();
    }

    public function delete($id)
    {
        abort_if(Auth::user()->hasRole('Caissier'), 403, 'Accès non autorisé.');
        FeeType::findOrFail($id)->delete();
        $this->loadFeeTypes();
        session()->flash('success', 'Type de frais supprimé.');
    }
}
