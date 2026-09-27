<?php

namespace App\Livewire\Auth;

use App\Enums\LoginMode;
use App\Models\Employee;
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
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Connexion séparée par mode : bureau (mot de passe), caisse / atelier (PIN), badge (badge + PIN).
 * Chaque utilisateur ne peut se connecter que par le mode défini sur son compte,
 * puis il est redirigé vers l'espace correspondant à ses attributions.
 */
#[Layout('layouts.guest')]
#[Title('Connexion')]
class Login extends Component
{
    #[Url(as: 'mode')]
    public string $mode = 'password';

    public string $identifier = '';

    public string $password = '';

    public string $username = '';

    public string $pin = '';

    public string $badge = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (! LoginMode::tryFrom($this->mode)) {
            $this->mode = LoginMode::Password->value;
        }
    }

    public function setMode(string $mode): void
    {
        $this->mode = (LoginMode::tryFrom($mode) ?? LoginMode::Password)->value;
        $this->resetErrorBag();
        $this->reset('password', 'pin', 'badge');
    }

    /**
     * Pré-remplit le formulaire avec un compte de démonstration (environnement local uniquement).
     */
    public function useDemoAccount(string $username): void
    {
        abort_unless(DemoAccounts::enabled(), 404);

        $account = collect(DemoAccounts::all())->firstWhere('username', $username);
        abort_unless($account, 404);

        $this->setMode($account['mode']->value);

        match ($account['mode']) {
            LoginMode::Password => [$this->identifier, $this->password] = [$account['username'], DemoAccounts::PASSWORD],
            LoginMode::Pin => [$this->username, $this->pin] = [$account['username'], DemoAccounts::PIN],
            LoginMode::Badge => [$this->badge, $this->pin] = [$account['badge'], DemoAccounts::PIN],
        };
    }

    public function login(): void
    {
        $mode = LoginMode::tryFrom($this->mode) ?? LoginMode::Password;

        $this->validate(match ($mode) {
            LoginMode::Password => ['identifier' => ['required', 'string', 'max:255'], 'password' => ['required', 'string']],
            LoginMode::Pin => ['username' => ['required', 'string', 'max:255'], 'pin' => ['required', 'digits_between:4,6']],
            LoginMode::Badge => ['badge' => ['required', 'string', 'max:64'], 'pin' => ['required', 'digits_between:4,6']],
        }, [], ['identifier' => 'identifiant', 'username' => 'nom d’utilisateur', 'pin' => 'PIN', 'badge' => 'badge']);

        $identifier = match ($mode) {
            LoginMode::Password => $this->identifier,
            LoginMode::Pin => $this->username,
            LoginMode::Badge => $this->badge,
        };
        $errorField = match ($mode) {
            LoginMode::Password => 'identifier',
            LoginMode::Pin => 'username',
            LoginMode::Badge => 'badge',
        };

        $throttleKey = Str::transliterate($mode->value.'|'.Str::lower($identifier).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout(request()));

            throw ValidationException::withMessages([$errorField => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($throttleKey).' secondes.']);
        }

        $user = $this->findUser($mode, $identifier);
        $valid = $user && match ($mode) {
            LoginMode::Password => Hash::check($this->password, $user->password),
            default => $user->checkPin($this->pin),
        };

        if (! $valid) {
            RateLimiter::hit($throttleKey);
            event(new Failed('web', $user, ['username' => $identifier, 'method' => $mode->value]));
            $this->reset('password', 'pin');

            throw ValidationException::withMessages([$errorField => $mode === LoginMode::Password ? 'Identifiants invalides.' : 'Identifiant, badge ou PIN invalide.']);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([$errorField => 'Ce compte est désactivé.']);
        }

        $expected = $user->login_mode ?? LoginMode::Password;
        if ($expected !== $mode) {
            $this->reset('password', 'pin');

            throw ValidationException::withMessages([$errorField => "Ce compte se connecte via l’espace « {$expected->shortLabel()} »."]);
        }

        RateLimiter::clear($throttleKey);

        session()->put('login_method', $mode->value);
        Auth::login($user, $mode === LoginMode::Password && $this->remember);
        session()->regenerate();
        session()->put('last_activity_at', now()->timestamp);

        $this->redirectIntended(HomeRoute::url($user), navigate: false);
    }

    private function findUser(LoginMode $mode, string $identifier): ?User
    {
        return match ($mode) {
            LoginMode::Password => User::query()->where('email', $identifier)->orWhere('username', $identifier)->first(),
            LoginMode::Pin => User::query()->where('username', $identifier)->first(),
            LoginMode::Badge => User::query()->where('badge_code', $identifier)->first()
                ?? Employee::query()->where('badge_code', $identifier)->whereNotNull('user_id')->first()?->user,
        };
    }

    public function render()
    {
        return view('livewire.auth.login', [
            'modes' => LoginMode::cases(),
            'demoAccounts' => DemoAccounts::enabled() ? collect(DemoAccounts::all())->groupBy(fn (array $account): string => $account['mode']->value) : collect(),
        ]);
    }
}
