<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Dossier de commande client : fil conducteur du devis jusqu'à la livraison.
 */
class SalesOrder extends Model
{
    use BelongsToCompany, SoftDeletes;

    /** Étapes ordonnées du parcours (hors « annule »). */
    public const PIPELINE = ['devis', 'valide', 'facture', 'paye', 'fabrication', 'execution', 'livre', 'cloture'];

    protected $attributes = ['stage' => 'devis', 'kind' => 'fabrication', 'priority' => 'normale', 'channel' => 'b2b', 'currency_code' => 'USD'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'amount_paid' => 'decimal:2', 'delivery_date' => 'date', 'validated_at' => 'datetime', 'delivered_at' => 'datetime'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'quote_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'invoice_id');
    }

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SalesOrderEvent::class)->latest('id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /** Étapes réellement applicables selon la nature de la commande. */
    public function steps(): array
    {
        return array_values(array_filter(self::PIPELINE, fn ($step) => match ($step) {
            'fabrication' => $this->kind === 'fabrication',
            'execution' => in_array($this->kind, ['construction', 'service'], true),
            default => true,
        }));
    }

    public function stepIndex(): int
    {
        $index = array_search($this->stage, $this->steps(), true);

        return $index === false ? -1 : $index;
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->total - (float) $this->amount_paid, 2));
    }

    public function paidRatio(): float
    {
        return (float) $this->total > 0 ? min(1, (float) $this->amount_paid / (float) $this->total) : 0;
    }
}
