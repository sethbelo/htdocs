<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['transaction_type', 'student_id', 'teacher_id', 'fee_type_id', 'amount', 'payment_method', 'currency', 'payment_date', 'reference', 'description', 'ohada_account_code', 'status', 'beneficiary_name', 'motif'])]
class Payment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }
}
