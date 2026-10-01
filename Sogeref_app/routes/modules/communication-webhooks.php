<?php

/*
| Communication & marketing — webhooks publics des fournisseurs (hors authentification, hors CSRF).
*/

use App\Http\Controllers\Webhooks\CommunicationWebhookController;
use Illuminate\Support\Facades\Route;

Route::controller(CommunicationWebhookController::class)->prefix('webhooks')->name('webhooks.')->middleware('throttle:240,1')->group(function () {
    Route::get('/whatsapp/meta', 'metaVerify')->name('whatsapp.meta.verify');
    Route::post('/whatsapp/meta', 'meta')->name('whatsapp.meta');
    Route::post('/whatsapp/twilio', 'twilioWhatsApp')->name('whatsapp.twilio');
    Route::post('/sms/{provider}', 'sms')->whereIn('provider', ['twilio', 'africastalking', 'infobip', 'http'])->name('sms');
});
