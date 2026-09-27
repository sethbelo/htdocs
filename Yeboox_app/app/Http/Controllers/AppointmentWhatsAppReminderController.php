<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;

class AppointmentWhatsAppReminderController extends Controller
{
    public function __invoke(string $appointmentId): RedirectResponse
    {
        abort_unless(auth()->user()->can('appointments.remind'), 403);

        $appointment = Appointment::forEstablishment()->findOrFail($appointmentId);
        $appointment->update(['reminder_sent_at' => now()]);

        return redirect()->away($appointment->whatsapp_reminder_url);
    }
}
