<?php

namespace App\Models;

use App\Models\Concerns\HasPrintSpecifications;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    use HasPrintSpecifications;

    protected $guarded = ['id'];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
