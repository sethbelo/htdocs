<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Support\DemoAccounts;
use App\Support\HomeRoute;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Connexion unique : nom d'utilisateur ou e-mail + mot de passe,
 * puis redirection vers l'espace correspondant aux attributions de l'utilisateur.
 */
#[Layout('layouts.guest')]
#[Title('Connexion')]
class Login extends Component
{
    public string $identifier = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * Pré-remplit le formulaire avec un compte de démonstration (environnement local uniquement).
     */
    public function useDemoAccount(string $username): void
    {
        abort_unless(DemoAccounts::enabled(), 404);
        abort_unless(collect(DemoAccounts::all())->contains('username', $username), 404);

        [$this->identifier, $this->password] = [$username, DemoAccounts::PASSWORD];
        $this->resetErrorBag();
    }

    public function login(): void
    {
        $this->validate(
            ['identifier' => ['required', 'string', 'max:255'], 'password' => ['required', 'string']],
            [],
            ['identifier' => 'nom d’utilisateur ou e-mail', 'password' => 'mot de passe'],
        );

        $identifier = Str::lower(trim($this->identifier));
        $throttleKey = Str::transliterate($identifier.'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout(request()));

            throw ValidationException::withMessages(['identifier' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($throttleKey).' secondes.']);
        }

        $user = User::query()->where('email', $identifier)->orWhere('username', $identifier)->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($throttleKey);
            event(new Failed('web', $user, ['username' => $identifier, 'method' => 'password']));
            $this->reset('password');

            throw ValidationException::withMessages(['identifier' => 'Identifiant ou mot de passe incorrect.']);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['identifier' => 'Ce compte est désactivé. Contactez l’administrateur.']);
        }

        RateLimiter::clear($throttleKey);

        session()->put('login_method', 'password');
        Auth::login($user, $this->remember);
        session()->regenerate();
        session()->put('last_activity_at', now()->timestamp);

        $this->redirectIntended(HomeRoute::url($user), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login', [
            'demoAccounts' => DemoAccounts::enabled() ? collect(DemoAccounts::all()) : collect(),
        ]);
    }
}
