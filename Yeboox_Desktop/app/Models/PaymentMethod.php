<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['name', 'code', 'icon', 'is_active', 'syscohada_code'];
}
