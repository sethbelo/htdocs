<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialRequest extends Model
{
    protected $fillable = [
        'uuid', 'request_type', 'subscription_plan_id', 'company_name',
        'contact_name', 'email', 'phone', 'country', 'city', 'business_type',
        'locations_count', 'preferred_contact', 'message', 'status', 'consent',
        'source', 'appointment_date', 'appointment_time', 'timezone', 'metadata',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'consent' => 'boolean',
        'metadata' => 'array',
    ];
}
