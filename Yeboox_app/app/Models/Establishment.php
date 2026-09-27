<?php

namespace App\Models;

use App\Enums\EstablishmentType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Establishment extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'name', 'slogan', 'type', 'logo_path', 'rccm', 'id_nat', 'import_id', 'nif', 'vat_number',
        'address', 'city', 'country', 'phone', 'email', 'website',
        'document_footer',
        'default_currency_id', 'timezone', 'fiscal_year_start',
        'settings', 'is_active',
    ];

    protected $casts = [
        'type' => EstablishmentType::class,
        'settings' => 'array',
        'is_active' => 'boolean',
        'fiscal_year_start' => 'date',
    ];

    // ── Relationships ──────────────────────────────

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function mainBranch()
    {
        return $this->hasOne(Branch::class)->where('is_main', true);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function currencies()
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    public function defaultCurrency()
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    public function taxes()
    {
        return $this->hasMany(Tax::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function exchangeRates()
    {
        return $this->hasMany(ExchangeRate::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    // ── Accessors ──────────────────────────────

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path
            ? asset('storage/'.$this->logo_path)
            : null;
    }

    // ── Scopes ──────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ──────────────────────────────

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings()->where('key', $key)->first();

        return $setting ? $setting->typed_value : $default;
    }

    public function supportsKitchenModule(): bool
    {
        return in_array($this->type, [
            EstablishmentType::Restaurant,
            EstablishmentType::Bar,
        ], true);
    }

    public function kitchenModuleEnabled(): bool
    {
        return $this->supportsKitchenModule()
            && (bool) $this->getSetting('kitchen_enabled', false);
    }

    public function provisionalReceiptEnabled(): bool
    {
        return $this->supportsKitchenModule()
            && (bool) $this->getSetting('pos_provisional_receipt_enabled', false);
    }

    public function reservationsModuleEnabled(): bool
    {
        return (bool) $this->getSetting('reservations_enabled', false);
    }

    public function appointmentsModuleEnabled(): bool
    {
        return (bool) $this->getSetting('appointments_enabled', false);
    }
}
