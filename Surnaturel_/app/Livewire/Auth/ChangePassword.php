<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Changer le mot de passe')]
class ChangePassword extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function save(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'different:current_password'],
        ], [], ['current_password' => 'mot de passe actuel', 'password' => 'nouveau mot de passe']);

        auth()->user()->forceFill([
            'password' => Hash::make($this->password),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        session()->flash('status', 'Mot de passe mis à jour.');
        $this->redirectRoute('home', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.change-password', ['forced' => auth()->user()->must_change_password]);
    }
}
