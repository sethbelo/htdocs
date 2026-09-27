<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\InteractsWithToasts;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('PIN caisse')]
class CashierPin extends Component
{
    use InteractsWithToasts;

    public string $password = '';

    public string $pin = '';

    public string $pin_confirmation = '';

    public function save(): void
    {
        $this->validate([
            'password' => ['required', 'current_password'],
            'pin' => ['required', 'digits_between:4,6', 'confirmed'],
        ], [], ['password' => 'mot de passe', 'pin' => 'PIN']);

        auth()->user()->setPin($this->pin);
        $this->reset();
        session()->flash('status', 'PIN caisse enregistré.');
        $this->redirectRoute('home', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.cashier-pin');
    }
}
