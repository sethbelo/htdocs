<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Component;

class Form extends Component
{
    public ?Customer $customer = null;

    public bool $editing = false;

    public string $first_name = '';

    public string $last_name = '';

    public string $company = '';

    public string $email = '';

    public string $phone = '';

    public string $phone_secondary = '';

    public string $address = '';

    public string $city = '';

    public string $country = 'CD';

    public string $nif = '';

    public string $rccm = '';

    public string $birth_date = '';

    public string $gender = '';

    public string $notes = '';

    public bool $is_active = true;

    public int $loyalty_points = 0;

    protected function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'phone_secondary' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'required|string|size:2',
            'nif' => 'nullable|string|max:50',
            'rccm' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
            'loyalty_points' => 'integer|min:0|max:1000000000',
        ];
    }

    public function mount(?string $customerId = null): void
    {
        if ($customerId) {
            $this->customer = Customer::findOrFail($customerId);
            $this->editing = true;
            $this->fill($this->customer->only([
                'first_name', 'last_name', 'company', 'email', 'phone',
                'phone_secondary', 'address', 'city', 'country', 'nif', 'rccm',
                'gender', 'notes', 'is_active',
                'loyalty_points',
            ]));
            $this->birth_date = $this->customer->birth_date?->format('Y-m-d') ?? '';
        }
    }

    public function save(): void
    {
        $validated = $this->validate();
        $validated['birth_date'] = $validated['birth_date'] ?: null;

        if ($this->editing && ! auth()->user()->can('loyalty.adjust_points')) {
            $validated['loyalty_points'] = $this->customer->loyalty_points;
        }

        if ($this->editing) {
            $this->customer->update($validated);
            $this->customer->updateLoyaltyLevel();
            session()->flash('success', 'Client modifié avec succès.');
        } else {
            $validated['establishment_id'] = auth()->user()->establishment_id;
            $validated['created_by'] = auth()->id();
            Customer::create($validated);
            session()->flash('success', 'Client créé avec succès.');
        }

        $this->redirect(route('admin.customers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.customers.form')
            ->layout('layouts.admin', [
                'title' => $this->editing ? 'Modifier le client' : 'Nouveau client',
            ]);
    }
}
