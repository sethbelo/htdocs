<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignVersion extends Model
{
    protected $guarded = ['id'];

    public function designJob(): BelongsTo
    {
        return $this->belongsTo(DesignJob::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
