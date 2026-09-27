<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailRecord extends Model
{
    use BelongsToCompany;

    protected $attributes = ['status' => 'registered', 'channel' => 'physique'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['record_date' => 'date'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
