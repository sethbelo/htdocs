<?php

namespace App\Models;

use App\Jobs\WriteActivityLog;
use App\Models\Concerns\BelongsToTenant;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'company_id',
        'establishment_id',
        'point_of_sale_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an action quickly.
     */
    public static function log(string $action, string $description, ?int $userId = null): void
    {
        $tenant = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

        WriteActivityLog::dispatch(
            $action,
            $description,
            $userId ?? auth()->id(),
            app()->runningInConsole() ? null : request()->ip(),
            $tenant?->companyId(),
            $tenant?->establishmentId(),
            $tenant?->pointOfSaleId(),
        );
    }
}
