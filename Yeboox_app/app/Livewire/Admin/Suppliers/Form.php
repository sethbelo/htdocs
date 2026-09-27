<?php

namespace App\Livewire\Admin\Suppliers;

use App\Models\Supplier;
use Livewire\Component;

class Form extends Component
{
    public ?Supplier $supplier = null;

    public bool $editing = false;

    public string $name = '';

    public string $contact_name = '';

    public string $email = '';

    public string $phone = '';

    public string $phone_secondary = '';

    public string $address = '';

    public string $city = '';

    public string $nif = '';

    public string $rccm = '';

    public string $bank_name = '';

    public string $bank_account = '';

    public int $payment_terms_days = 0;

    public string $notes = '';

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'phone_secondary' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'nif' => 'nullable|string|max:50',
            'rccm' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:100',
            'payment_terms_days' => 'integer|min:0',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ];
    }

    public function mount(?string $supplierId = null): void
    {
        if ($supplierId) {
            $this->supplier = Supplier::findOrFail($supplierId);
            $this->editing = true;
            $this->fill($this->supplier->only([
                'name', 'contact_name', 'email', 'phone', 'phone_secondary',
                'address', 'city', 'nif', 'rccm', 'bank_name', 'bank_account',
                'payment_terms_days', 'notes', 'is_active',
            ]));
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            $this->supplier->update($validated);
            session()->flash('success', 'Fournisseur modifié avec succès.');
        } else {
            $validated['establishment_id'] = auth()->user()->establishment_id;
            $validated['created_by'] = auth()->id();
            $validated['country'] = 'CD';
            Supplier::create($validated);
            session()->flash('success', 'Fournisseur créé avec succès.');
        }

        $this->redirect(route('admin.suppliers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.suppliers.form')
            ->layout('layouts.admin', [
                'title' => $this->editing ? 'Modifier le fournisseur' : 'Nouveau fournisseur',
            ]);
    }
}
