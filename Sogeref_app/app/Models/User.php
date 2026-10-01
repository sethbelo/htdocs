<?php

namespace App\Models;

use App\Support\Modules;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'locale', 'theme', 'current_company_id', 'current_branch_id', 'is_active', 'job_title', 'avatar_path', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'last_login_at' => 'datetime', 'is_active' => 'boolean', 'password' => 'hashed'];
    }

    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    public function currentBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'current_branch_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withPivot(['branch_id', 'is_active'])->withTimestamps();
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class)->withPivot('last_read_at')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    protected function initials(): Attribute
    {
        return Attribute::get(fn () => collect(preg_split('/\s+/', (string) $this->name))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''));
    }

    public function isMaster(): bool
    {
        return $this->hasRole('Super Admin');
    }

    /** Le module est-il activé pour la société ET autorisé pour cet utilisateur ? */
    public function canAccessModule(string $key): bool
    {
        return Modules::enabled($key) && ($this->isMaster() || self::openToEveryone($key) || $this->can("{$key}.view"));
    }

    public function canManage(string $key): bool
    {
        return Modules::enabled($key) && ($this->isMaster() || self::openToEveryone($key) || $this->can("{$key}.manage"));
    }

    /** Modules ouverts à tous les employés (ex. Espace personnel : 'everyone' => true). */
    private static function openToEveryone(string $key): bool
    {
        return (bool) (Modules::get($key)['everyone'] ?? false);
    }

    public function roleLabel(): string
    {
        return $this->getRoleNames()->first() ?? 'Utilisateur';
    }
}
