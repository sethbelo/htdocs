<?php

namespace App\Models;

use App\Enums\LoginMode;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'badge_code',
        'login_mode',
        'must_change_password',
        'session_timeout_minutes',
        'default_site_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'pin_hash',
        'remember_token',
    ];

    /** @var array<int, int>|null */
    protected ?array $cachedSiteIds = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
            'login_mode' => LoginMode::class,
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'username', 'email', 'is_active', 'default_site_id', 'must_change_password', 'login_mode'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('User');
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class)->withTimestamps();
    }

    public function defaultSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'default_site_id');
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    public function hasConsolidatedView(): bool
    {
        return $this->can('sites.view_all');
    }

    /**
     * @return array<int, int>
     */
    public function accessibleSiteIds(): array
    {
        if ($this->hasConsolidatedView()) {
            return $this->cachedSiteIds ??= Site::query()->pluck('id')->all();
        }

        return $this->cachedSiteIds ??= $this->sites()->pluck('sites.id')->all();
    }

    public function canAccessSite(Site|int|null $site): bool
    {
        if ($site === null) {
            return true;
        }

        $siteId = $site instanceof Site ? $site->id : $site;

        return $this->hasConsolidatedView() || in_array($siteId, $this->accessibleSiteIds(), true);
    }

    public function isDirectorGeneral(): bool
    {
        return $this->hasRole('directeur-general');
    }

    public function usesPassword(): bool
    {
        return ($this->login_mode ?? LoginMode::Password) === LoginMode::Password;
    }

    public function setPin(string $pin): void
    {
        $this->forceFill(['pin_hash' => Hash::make($pin)])->save();
    }

    public function checkPin(string $pin): bool
    {
        return $this->pin_hash !== null && Hash::check($pin, $this->pin_hash);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn (string $word): string => Str::substr($word, 0, 1))
            ->implode('');
    }
}
