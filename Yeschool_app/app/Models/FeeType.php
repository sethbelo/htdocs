<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'amount', 'currency', 'ohada_account_code', 'exchange_rate'])]
class FeeType extends Model
{
    use HasFactory;
}
