<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasEstablishment, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'branch_id', 'customer_id',
        'client_name', 'client_phone', 'client_email',
        'service_name', 'staff_name', 'appointment_date', 'appointment_time',
        'duration_minutes', 'status', 'notes', 'reminder_sent_at', 'created_by',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'duration_minutes' => 'integer',
        'reminder_sent_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereDate('appointment_date', '>=', today())
            ->whereNotIn('status', ['completed', 'cancelled', 'no_show']);
    }

    public function getStartsAtAttribute()
    {
        return $this->appointment_date->copy()->setTimeFromTimeString($this->appointment_time);
    }

    public function getWhatsappReminderUrlAttribute(): string
    {
        $phone = preg_replace('/\D+/', '', $this->client_phone) ?: '';
        if (str_starts_with($phone, '0')) {
            $phone = '243'.substr($phone, 1);
        }

        $template = $this->establishment?->getSetting(
            'appointment_whatsapp_template',
            'Bonjour {client}, rappel de votre rendez-vous {service} le {date} à {heure} chez {entreprise}.'
        );
        $message = strtr((string) $template, [
            '{client}' => $this->client_name,
            '{service}' => $this->service_name,
            '{date}' => $this->appointment_date->format('d/m/Y'),
            '{heure}' => substr($this->appointment_time, 0, 5),
            '{entreprise}' => $this->establishment?->name ?? config('app.name'),
        ]);

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}
