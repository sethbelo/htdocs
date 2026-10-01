<?php

namespace App\Livewire\System;

use App\Http\Controllers\Webhooks\CommunicationWebhookController;
use App\Livewire\Concerns\InteractsWithUi;
use App\Services\Communication\CommunicationService;
use App\Services\Communication\IntegrationSettings;
use App\Services\Communication\WhatsAppNumberPool;
use Livewire\Component;

/**
 * Master système › Intégrations & API : e-mail (SMTP), WhatsApp (3 numéros),
 * SMS et mode simulation / production. Les secrets sont chiffrés et ne
 * reviennent jamais au navigateur (champ vide = valeur conservée).
 */
class Integrations extends Component
{
    use InteractsWithUi;

    public string $mode = 'simulation';

    public array $email = [];

    public array $whatsapp = [];

    public array $sms = [];

    /** Adresses de test saisies par canal (et par numéro WhatsApp). */
    public array $testTo = ['email' => '', 'whatsapp' => '', 'sms' => ''];

    public function mount(): void
    {
        abort_unless($this->canView(), 403);
        $this->load();
    }

    private function settings(): IntegrationSettings
    {
        return app(IntegrationSettings::class);
    }

    private function load(): void
    {
        $settings = $this->settings();
        $this->mode = $settings->mode();
        foreach (IntegrationSettings::CHANNELS as $channel) {
            $this->{$channel} = $settings->masked($channel);
        }
        $this->testTo['email'] = $this->testTo['email'] ?: (string) auth()->user()->email;
    }

    private function canView(): bool
    {
        return auth()->user()->isMaster() || auth()->user()->can('system.view');
    }

    private function canEdit(): bool
    {
        return auth()->user()->isMaster() || auth()->user()->can('system.manage');
    }

    private function guard(): void
    {
        abort_unless($this->canEdit(), 403, 'Seul le Master système peut modifier les intégrations.');
    }

    public function saveMode(): void
    {
        $this->guard();
        $this->settings()->setMode($this->mode);
        $this->toast($this->mode === 'live' ? 'Mode production activé : les messages partent réellement.' : 'Mode simulation activé : aucun message ne quitte le serveur.', $this->mode === 'live' ? 'warning' : 'success');
    }

    public function save(string $channel): void
    {
        $this->guard();
        abort_unless(in_array($channel, IntegrationSettings::CHANNELS, true), 404);
        $rules = match ($channel) {
            'email' => ['email.driver' => 'required|in:smtp,log', 'email.host' => 'nullable|string|max:190', 'email.port' => 'nullable|integer|between:1,65535',
                'email.encryption' => 'nullable|in:tls,ssl,none', 'email.from_address' => 'nullable|email', 'email.reply_to' => 'nullable|email', 'email.from_name' => 'nullable|string|max:120'],
            'whatsapp' => ['whatsapp.provider' => 'required|in:'.implode(',', array_keys(IntegrationSettings::PROVIDERS['whatsapp'])),
                'whatsapp.numbers' => 'array|size:'.IntegrationSettings::NUMBER_SLOTS, 'whatsapp.numbers.*.label' => 'nullable|string|max:60',
                'whatsapp.numbers.*.phone' => ['nullable', 'regex:/^\+?[0-9 ().-]{8,20}$/'], 'whatsapp.numbers.*.phone_number_id' => 'nullable|string|max:60'],
            'sms' => ['sms.provider' => 'required|in:'.implode(',', array_keys(IntegrationSettings::PROVIDERS['sms'])), 'sms.base_url' => 'nullable|url', 'sms.sender_id' => 'nullable|string|max:20'],
        };
        $this->validate($rules, [], ['whatsapp.numbers.*.phone' => 'numéro WhatsApp']);
        if ($channel === 'whatsapp') {
            $phones = collect($this->whatsapp['numbers'])->pluck('phone')->filter()->map(fn ($p) => CommunicationService::normalizePhoneNumber($p));
            if ($phones->duplicates()->isNotEmpty()) {
                $this->addError('whatsapp.numbers', 'Les trois numéros WhatsApp doivent être différents.');

                return;
            }
        }
        $this->settings()->put($channel, $this->{$channel});
        $this->{$channel} = $this->settings()->masked($channel);
        $this->toast(['email' => 'Paramètres e-mail enregistrés.', 'whatsapp' => 'Paramètres WhatsApp enregistrés.', 'sms' => 'Paramètres SMS enregistrés.'][$channel]);
    }

    public function forget(string $channel, string $field): void
    {
        $this->guard();
        $this->settings()->forgetSecret($channel, $field);
        $this->{$channel} = $this->settings()->masked($channel);
        $this->toast('Secret effacé.');
    }

    public function test(string $channel, ?int $number = null): void
    {
        $this->guard();
        $address = trim((string) ($this->testTo[$channel] ?? ''));
        if ($address === '') {
            $this->toast('Saisissez une adresse de test.', 'warning');

            return;
        }
        [$ok, $message] = app(CommunicationService::class)->testChannel($channel, $address, $number);
        $this->toast($message, $ok ? 'success' : 'error');
    }

    /** Génère un jeton de vérification Meta aléatoire (à copier dans la console Meta). */
    public function generateVerifyToken(): void
    {
        $this->guard();
        $this->settings()->put('whatsapp', ['verify_token' => bin2hex(random_bytes(16))] + $this->settings()->get('whatsapp'));
        $this->whatsapp = $this->settings()->masked('whatsapp');
        $this->toast('Nouveau jeton de vérification généré.');
    }

    public function render()
    {
        $settings = $this->settings();

        return view('livewire.system.integrations', [
            'canEdit' => $this->canEdit(),
            'providers' => IntegrationSettings::PROVIDERS,
            'configured' => collect(IntegrationSettings::CHANNELS)->mapWithKeys(fn ($c) => [$c => $settings->configured($c)])->all(),
            'numbers' => WhatsAppNumberPool::for($settings->company())->numbers(),
            'verifyToken' => $settings->get('whatsapp')['verify_token'],
            'webhooks' => [
                'Meta WhatsApp (vérification + messages)' => route('webhooks.whatsapp.meta'),
                'Twilio WhatsApp' => route('webhooks.whatsapp.twilio'),
                'SMS Twilio' => route('webhooks.sms', ['provider' => 'twilio']),
                'SMS Africa’s Talking' => route('webhooks.sms', ['provider' => 'africastalking', 'cle' => CommunicationWebhookController::smsSecret()]),
                'SMS Infobip' => route('webhooks.sms', ['provider' => 'infobip', 'cle' => CommunicationWebhookController::smsSecret()]),
                'SMS passerelle HTTP' => route('webhooks.sms', ['provider' => 'http', 'cle' => CommunicationWebhookController::smsSecret()]),
            ],
        ]);
    }
}
