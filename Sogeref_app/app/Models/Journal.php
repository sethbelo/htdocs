<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}
