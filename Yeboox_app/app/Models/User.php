<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Laravolt\Avatar\Facade;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasRoles {
        HasRoles::teams as permissionTeams;
    }
    use HasTeams {
        HasTeams::teams insteadof HasRoles;
    }
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'email', 'password', 'username',
        'establishment_id', 'branch_id',
        'pin_code', 'rfid_badge', 'phone', 'avatar_path',
        'status', 'must_change_password', 'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'pin_code',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'must_change_password' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────

    public function establishment()
    {
        return $this->belongsTo(Establishment::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }

    // ── Scopes ──────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', UserStatus::Active);
    }

    public function scopeForEstablishment($query, ?string $establishmentId = null)
    {
        $establishmentId = $establishmentId ?? auth()->user()?->establishment_id;
        if ($establishmentId) {
            return $query->where('establishment_id', $establishmentId);
        }

        return $query;
    }

    public function scopeForBranch($query, ?string $branchId = null)
    {
        $branchId = $branchId ?? auth()->user()?->branch_id;
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }

        return $query;
    }

    // ── Accessors ──────────────────────────────

    /**
     * Get avatar URL — use uploaded photo, then Laravolt avatar, then Jetstream default.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path) {
            return asset('storage/'.$this->avatar_path);
        }

        // Generate avatar using Laravolt
        if (class_exists(Facade::class)) {
            return Facade::create($this->name)->toBase64();
        }

        return $this->profile_photo_url;
    }

    /**
     * Get initials for display.
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials;
    }

    // ── Helpers ──────────────────────────────

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isManager(): bool
    {
        return $this->hasRole('gerant');
    }

    public function isSeller(): bool
    {
        return $this->hasRole('vendeur');
    }

    /**
     * Verify PIN code.
     */
    public function verifyPin(string $pin): bool
    {
        if (! $this->pin_code) {
            return false;
        }

        return \Hash::check($pin, $this->pin_code);
    }

    /**
     * Set PIN code (hashed).
     */
    public function setPin(string $pin): void
    {
        $this->update(['pin_code' => \Hash::make($pin)]);
    }

    /**
     * Log a login attempt.
     */
    public function logLogin(string $method = 'password', bool $success = true, ?string $reason = null): void
    {
        $this->loginHistories()->create([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'login_method' => $method,
            'success' => $success,
            'failure_reason' => $reason,
            'created_at' => now(),
        ]);
    }

    /**
     * Check if user has too many recent failed login attempts.
     */
    public function hasTooManyLoginAttempts(int $maxAttempts = 5, int $minutes = 15): bool
    {
        return $this->loginHistories()
            ->failed()
            ->recent($minutes)
            ->count() >= $maxAttempts;
    }
}
