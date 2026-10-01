<?php

namespace Tests\Feature;

use App\Livewire\Communication\Campaigns;
use App\Livewire\Communication\Emails;
use App\Livewire\Communication\QuickSend;
use App\Livewire\Communication\Templates;
use App\Livewire\Communication\WhatsAppInbox;
use App\Livewire\System\Integrations;
use App\Models\CommCampaign;
use App\Models\CommConversation;
use App\Models\CommMessage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\Communication\CampaignService;
use App\Services\Communication\CommunicationService;
use App\Services\Communication\IntegrationSettings;
use App\Services\Communication\WhatsAppNumberPool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Communication & marketing : paramètres chiffrés, 3 numéros WhatsApp, envois,
 * campagnes, webhooks, écrans et droits.
 */
class CommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function as(string $login): User
    {
        $user = User::where('email', "{$login}@sogeref.cd")->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    public function test_every_screen_renders_for_master_and_communication_officer(): void
    {
        foreach (['master', 'communication'] as $login) {
            $this->as($login);
            foreach (['communication', 'communication.whatsapp', 'communication.sms', 'communication.emails', 'communication.campaigns', 'communication.templates', 'chat'] as $route) {
                $this->get(route($route))->assertOk();
            }
            $this->get(route('communication.campaigns.show', CommCampaign::first()))->assertOk();
        }
        $this->as('master');
        $this->get(route('system', ['onglet' => 'integrations']))->assertOk()->assertSee('Intégrations & API');
    }

    public function test_roles_without_the_module_are_refused(): void
    {
        $this->as('caisse');
        $this->get(route('communication'))->assertForbidden();
        $this->get(route('communication.campaigns'))->assertForbidden();
    }

    public function test_secrets_are_encrypted_at_rest_and_never_rendered(): void
    {
        $this->as('master');
        Livewire::test(Integrations::class)
            ->set('whatsapp.provider', 'meta_cloud')
            ->set('whatsapp.access_token', 'EAAG-super-secret-token')
            ->call('save', 'whatsapp')->assertHasNoErrors();

        $raw = json_encode(Company::first()->fresh()->settings);
        $this->assertStringNotContainsString('EAAG-super-secret-token', $raw);
        $this->assertSame('EAAG-super-secret-token', app(IntegrationSettings::class)->get('whatsapp')['access_token']);
        $this->get(route('system', ['onglet' => 'integrations']))->assertOk()->assertDontSee('EAAG-super-secret-token');
        Livewire::test(Integrations::class)->assertSet('whatsapp.access_token', '');
    }

    public function test_only_master_can_save_integrations(): void
    {
        $this->as('communication');
        $this->get(route('system', ['onglet' => 'integrations']))->assertForbidden();
        $this->as('direction');
        $this->get(route('system', ['onglet' => 'integrations']))->assertForbidden();
    }

    public function test_three_whatsapp_numbers_random_and_sticky(): void
    {
        $this->as('master');
        $pool = app(WhatsAppNumberPool::class);
        $phones = collect($pool->active())->pluck('phone');
        $this->assertCount(3, $phones);
        $this->assertCount(3, $phones->unique());

        $first = app(CommunicationService::class)->sendWhatsApp('+243 81 000 1111', 'Bonjour');
        $this->assertContains($first->conversation->sender, $phones->all());
        $again = app(CommunicationService::class)->sendWhatsApp('0810001111', 'Encore');
        $this->assertSame($first->conversation_id, $again->conversation_id);
        $this->assertSame($first->conversation->sender, $again->conversation->fresh()->sender);

        $picked = collect(range(1, 40))->map(fn ($i) => $pool->pick('+24399'.str_pad((string) $i, 7, '0', STR_PAD_LEFT))['phone'])->unique();
        $this->assertGreaterThan(1, $picked->count(), 'Les nouveaux correspondants sont répartis entre les numéros.');
    }

    public function test_simulation_send_and_template_rendering(): void
    {
        $this->as('master');
        $contact = Contact::where('type', 'client')->whereNotNull('phone')->first();
        $comm = app(CommunicationService::class);
        $message = $comm->sendSms($contact, $comm->renderTemplate('Bonjour {nom_complet} — {entreprise}', $contact));
        $this->assertSame('sent', $message->status);
        $this->assertSame('simulation', $message->provider);
        $this->assertStringContainsString($contact->display_name, $message->body);
        $this->assertStringContainsString(Company::first()->name, $message->body);
    }

    public function test_campaign_audience_respects_opt_in_and_launch_completes(): void
    {
        $user = $this->as('master');
        $optedOut = Contact::where('type', 'client')->whereNotNull('phone')->first();
        $optedOut->update(['marketing_opt_in' => false]);
        $campaign = CommCampaign::create(['company_id' => $user->current_company_id, 'name' => 'Test', 'channel' => 'sms', 'body' => 'Promo {prenom}', 'created_by' => $user->id,
            'audience' => ['types' => ['client'], 'only_opted_in' => true]]);

        $service = app(CampaignService::class);
        $this->assertFalse($service->audience($campaign)->pluck('contact.id')->contains($optedOut->id));
        $service->launch($campaign);
        $campaign->refresh();
        $this->assertSame('sent', $campaign->status);
        $this->assertGreaterThan(0, $campaign->stat('sent'));
        $this->assertSame($campaign->stat('total'), $campaign->stat('sent') + $campaign->stat('failed'));
    }

    public function test_campaign_wizard_launches_from_the_screen(): void
    {
        $this->as('communication');
        Livewire::test(Campaigns::class)
            ->call('create')
            ->set('form.name', 'Relance clients')->set('form.channel', 'whatsapp')
            ->call('next')->call('next')
            ->set('form.body', 'Bonjour {prenom}')
            ->call('next')
            ->call('launch')->assertHasNoErrors()->assertRedirect();
        $this->assertSame('sent', CommCampaign::where('name', 'Relance clients')->value('status'));
    }

    public function test_inbox_reply_email_and_quick_send(): void
    {
        $this->as('communication');
        $conversation = CommConversation::where('channel', 'whatsapp')->first();
        Livewire::test(WhatsAppInbox::class)->call('select', $conversation->id)->set('body', 'Réponse test')->call('send')->assertHasNoErrors();
        $this->assertDatabaseHas('comm_messages', ['conversation_id' => $conversation->id, 'direction' => 'out', 'body' => 'Réponse test']);

        Livewire::test(Emails::class)->call('openNew')
            ->set('newAddress', 'a@exemple.cd, b@exemple.cd')->set('newSubject', 'Offre')->set('newBody', 'Bonjour')
            ->call('startConversation')->assertHasNoErrors();
        $this->assertSame(2, CommMessage::where('channel', 'email')->where('subject', 'Offre')->count());

        $contact = Contact::whereNotNull('phone')->first();
        Livewire::test(QuickSend::class, ['contact' => $contact])->call('start', 'sms')->set('body', 'Rapide')->call('send')->assertHasNoErrors();
        $this->assertDatabaseHas('comm_messages', ['channel' => 'sms', 'body' => 'Rapide']);
        $this->get(route('contacts.show', $contact))->assertOk()->assertSee('Écrire au contact');

        Livewire::test(Templates::class)->call('create', 'sms')->set('form.name', 'Merci')->set('form.body', 'Merci {prenom}')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('comm_templates', ['name' => 'Merci', 'channel' => 'sms']);
    }

    public function test_sending_requires_permission(): void
    {
        $this->as('rh');
        $this->get(route('communication.whatsapp'))->assertForbidden();
    }

    public function test_meta_webhook_verify_and_inbound_message(): void
    {
        $this->as('master');
        app(IntegrationSettings::class)->put('whatsapp', ['verify_token' => 'jeton-123'] + app(IntegrationSettings::class)->get('whatsapp'));
        auth()->logout();

        $this->get('/webhooks/whatsapp/meta?hub.mode=subscribe&hub.verify_token=jeton-123&hub.challenge=42')->assertOk()->assertSee('42');
        $this->get('/webhooks/whatsapp/meta?hub.mode=subscribe&hub.verify_token=faux&hub.challenge=42')->assertForbidden();

        $this->postJson('/webhooks/whatsapp/meta', ['entry' => [['changes' => [['value' => [
            'metadata' => ['display_phone_number' => '243815550101'],
            'messages' => [['from' => '243990001122', 'id' => 'wamid.TEST', 'type' => 'text', 'text' => ['body' => 'Bonjour SOGEREF']]],
        ]]]]]])->assertOk();
        $message = CommMessage::where('provider_message_id', 'wamid.TEST')->firstOrFail();
        $this->assertSame('in', $message->direction);
        $this->assertSame('+243815550101', $message->conversation->sender);
        $this->assertSame(1, $message->conversation->unread_count);
    }

    public function test_sms_webhook_requires_secret(): void
    {
        $this->post('/webhooks/sms/http', ['from' => '+243990000000', 'text' => 'x'])->assertForbidden();
    }

    public function test_links_carry_no_numeric_ids(): void
    {
        $this->as('master');
        foreach (['communication', 'communication.campaigns', 'communication.whatsapp'] as $route) {
            $html = $this->get(route($route))->getContent();
            $this->assertDoesNotMatchRegularExpression('#/communication/campagnes/\d+["?]|[?&]conversation=\d+["&]#', $html);
        }
    }
}
