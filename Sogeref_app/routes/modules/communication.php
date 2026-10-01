<?php

/*
| Communication & marketing — écrans (inclus dans le groupe auth + active de web.php).
| Les webhooks entrants (WhatsApp / SMS) sont déclarés dans routes/modules/communication-webhooks.php.
| Chaque écran n'est déclaré que si son composant existe : une livraison partielle ne casse jamais l'application.
*/

use App\Livewire\Communication;
use Illuminate\Support\Facades\Route;

Route::middleware('module:communication')->prefix('communication')->name('communication')->group(function () {
    foreach ([
        ['/', Communication\Overview::class, ''],
        ['/whatsapp', Communication\WhatsAppInbox::class, '.whatsapp'],
        ['/sms', Communication\SmsInbox::class, '.sms'],
        ['/emails', Communication\Emails::class, '.emails'],
        ['/campagnes', Communication\Campaigns::class, '.campaigns'],
        ['/campagnes/{campaign}', Communication\CampaignShow::class, '.campaigns.show'],
        ['/modeles', Communication\Templates::class, '.templates'],
    ] as [$uri, $component, $name]) {
        if (class_exists($component)) {
            Route::get($uri, $component)->name($name);
        }
    }
});
