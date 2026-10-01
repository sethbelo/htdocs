<?php

namespace Database\Seeders;

use App\Models\CommCampaign;
use App\Models\CommTemplate;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\Communication\CampaignService;
use App\Services\Communication\CommunicationService;
use App\Services\Communication\IntegrationSettings;
use Illuminate\Database\Seeder;

/**
 * Communication & marketing (démo) : mode simulation, 3 numéros WhatsApp,
 * expéditeur SMS, modèles, conversations et campagnes.
 */
class CommunicationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->findOrFail(company_id());
        $settings = IntegrationSettings::for($company);
        $settings->setMode('simulation');
        $settings->put('email', ['driver' => 'log', 'from_address' => 'contact@sogeref.cd', 'from_name' => $company->name, 'reply_to' => 'contact@sogeref.cd']);
        $settings->put('whatsapp', ['provider' => 'simulation', 'numbers' => [
            ['label' => 'Service client', 'phone' => '+243 81 555 0101', 'active' => true],
            ['label' => 'Commercial', 'phone' => '+243 97 555 0202', 'active' => true],
            ['label' => 'Après-vente', 'phone' => '+243 89 555 0303', 'active' => true],
        ]]);
        $settings->put('sms', ['provider' => 'simulation', 'sender_id' => 'SOGEREF']);

        foreach ([
            ['whatsapp', 'Relance facture', null, 'Bonjour {prenom}, sauf erreur de notre part, votre facture {entreprise} reste à régler. Nous restons à votre disposition pour toute question. Merci de votre confiance !'],
            ['whatsapp', 'Promotion du mois', null, 'Bonjour {prenom} 👋 Ce mois-ci chez {entreprise} : -10 % sur la menuiserie aluminium et le mobilier sur mesure. Répondez à ce message pour recevoir un devis.'],
            ['sms', 'Rappel de rendez-vous', null, '{entreprise} : bonjour {prenom}, nous vous rappelons votre rendez-vous de demain. Merci de confirmer par retour de SMS.'],
            ['email', 'Bienvenue', 'Bienvenue chez {entreprise}', "Bonjour {prenom},\n\nNous sommes ravis de vous compter parmi nos clients. Notre équipe reste joignable pour vos projets de construction, menuiserie et fourniture.\n\nCordialement,\nL’équipe {entreprise}"],
        ] as [$channel, $name, $subject, $body]) {
            CommTemplate::firstOrCreate(['company_id' => $company->id, 'channel' => $channel, 'name' => $name], ['subject' => $subject, 'body' => $body]);
        }

        $comm = app(CommunicationService::class)->forCompany($company);
        $master = User::query()->where('email', 'master@sogeref.cd')->first();
        $agent = User::query()->where('email', 'communication@sogeref.cd')->first() ?? $master;
        $clients = Contact::query()->where('company_id', $company->id)->where('type', 'client')->whereNotNull('phone')->orderBy('id')->limit(6)->get();

        foreach ($clients->take(4) as $i => $client) {
            $in = $comm->receive('whatsapp', (string) $client->phone, ['Bonjour, je voudrais un devis pour des portes en aluminium.', 'Ma commande est-elle prête ?', 'Merci pour la livraison, tout est parfait.', 'Pouvez-vous me rappeler demain matin ?'][$i], 'demo-in-'.$client->id, null);
            $in->conversation->update(['contact_id' => $client->id]);
            if ($i < 3) {
                $comm->reply($in->conversation, ['Bonjour '.($client->first_name ?: $client->display_name).', avec plaisir : pouvez-vous nous préciser les dimensions ?', 'Oui, elle est prête : vous pouvez passer à partir de 14 h.', 'Merci à vous, à très bientôt !'][$i], null, $agent);
            }
        }
        if ($sms = $clients->get(4)) {
            $comm->sendSms($sms, $comm->renderTemplate('{entreprise} : bonjour {prenom}, votre devis est disponible. Contactez-nous au +243 81 555 0101.', $sms), $agent);
        }
        if ($mail = Contact::query()->where('company_id', $company->id)->whereNotNull('email')->where('email', '!=', '')->first()) {
            $comm->sendEmail($mail, 'Votre devis SOGEREF', "Bonjour,\n\nVeuillez trouver ci-joint notre proposition. Nous restons à votre disposition.\n\nCordialement,\nSOGEREF Sarl", $agent);
        }

        $campaigns = app(CampaignService::class);
        $sent = CommCampaign::create([
            'company_id' => $company->id, 'name' => 'Promotion menuiserie — septembre', 'channel' => 'whatsapp', 'created_by' => $agent?->id,
            'body' => 'Bonjour {prenom}, profitez de -10 % sur la menuiserie aluminium chez {entreprise} jusqu’au 30 septembre !',
            'audience' => ['types' => ['client'], 'segments' => [], 'cities' => [], 'contact_ids' => [], 'only_opted_in' => true],
        ]);
        $campaigns->launch($sent);
        $sent->messages()->limit(3)->update(['status' => 'delivered']);
        $campaigns->refreshStats($sent);

        CommCampaign::create([
            'company_id' => $company->id, 'name' => 'Vœux de fin d’année', 'channel' => 'email', 'created_by' => $agent?->id,
            'subject' => 'Meilleurs vœux de {entreprise}', 'body' => "Chère cliente, cher client,\n\nToute l’équipe {entreprise} vous remercie pour votre confiance et vous présente ses meilleurs vœux.",
            'audience' => ['types' => ['client', 'partner'], 'segments' => [], 'cities' => [], 'contact_ids' => [], 'only_opted_in' => true],
        ]);
    }
}
