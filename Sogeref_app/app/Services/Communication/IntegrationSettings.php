<?php

namespace App\Services\Communication;

use App\Models\Company;
use App\Support\Tenant;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

/**
 * Paramètres des intégrations (e-mail SMTP, WhatsApp, SMS) d'une société,
 * stockés dans companies.settings['integrations'].
 *
 * Les secrets (jetons, mots de passe, clés d'API) sont chiffrés au repos
 * (Crypt::encryptString) et ne sont jamais renvoyés en clair au navigateur :
 * masked() remplace leur valeur par une chaîne vide + un indicateur « défini ».
 *
 * Mode global : « simulation » (défaut — rien ne quitte le serveur) ou « live ».
 */
class IntegrationSettings
{
    public const CHANNELS = ['email', 'whatsapp', 'sms'];

    public const MASK = '••••••••';

    /** Champs par canal et valeurs par défaut. */
    public const DEFAULTS = [
        'email' => [
            'driver' => 'log', 'host' => '', 'port' => 587, 'encryption' => 'tls', 'username' => '', 'password' => '',
            'from_address' => '', 'from_name' => '', 'reply_to' => '',
        ],
        'whatsapp' => [
            'provider' => 'simulation',
            'access_token' => '', 'business_account_id' => '', 'verify_token' => '', 'app_secret' => '',
            'account_sid' => '', 'auth_token' => '',
            'numbers' => [],
        ],
        'sms' => [
            'provider' => 'simulation', 'account_sid' => '', 'auth_token' => '', 'username' => '', 'api_key' => '',
            'base_url' => '', 'sender_id' => '',
        ],
    ];

    /** Champs secrets (chiffrés au repos, jamais affichés). */
    public const SECRETS = [
        'email' => ['password'],
        'whatsapp' => ['access_token', 'verify_token', 'app_secret', 'auth_token'],
        'sms' => ['auth_token', 'api_key'],
    ];

    public const PROVIDERS = [
        'email' => ['smtp' => 'Serveur SMTP', 'log' => 'Journal (aucun envoi réel)'],
        'whatsapp' => ['meta_cloud' => 'Meta WhatsApp Cloud API', 'twilio' => 'Twilio WhatsApp', 'simulation' => 'Simulation'],
        'sms' => ['twilio' => 'Twilio', 'africastalking' => 'Africa’s Talking', 'infobip' => 'Infobip', 'http' => 'Passerelle HTTP générique', 'simulation' => 'Simulation'],
    ];

    public const NUMBER_SLOTS = 3;

    public function __construct(private ?Company $company = null) {}

    public static function for(Company $company): self
    {
        return new self($company);
    }

    public function company(): Company
    {
        return $this->company ?? Tenant::company();
    }

    // ── Lecture ─────────────────────────────────────────────────────────────

    /** Valeurs complètes du canal, secrets DÉCHIFFRÉS (usage serveur uniquement). */
    public function get(string $channel): array
    {
        $this->assertChannel($channel);
        $stored = (array) data_get($this->company()->settings, "integrations.{$channel}", []);
        $values = array_replace(self::DEFAULTS[$channel], array_intersect_key($stored, self::DEFAULTS[$channel]));
        foreach (self::SECRETS[$channel] as $field) {
            $values[$field] = $this->decrypt($stored[$field] ?? '');
        }
        if ($channel === 'whatsapp') {
            $values['numbers'] = $this->normalizeNumbers($values['numbers']);
        }

        return $values;
    }

    /**
     * Valeurs destinées au formulaire : secrets vidés, remplacés par
     * « {champ}_set » (bool) pour afficher « •••• — laisser vide pour conserver ».
     */
    public function masked(string $channel): array
    {
        $values = $this->get($channel);
        foreach (self::SECRETS[$channel] as $field) {
            $values[$field.'_set'] = $values[$field] !== '';
            $values[$field] = '';
        }

        return $values;
    }

    /** Un secret est-il défini (sans le révéler) ? */
    public function hasSecret(string $channel, string $field): bool
    {
        return $this->decrypt((string) data_get($this->company()->settings, "integrations.{$channel}.{$field}", '')) !== '';
    }

    public function mode(): string
    {
        return data_get($this->company()->settings, 'integrations.mode') === 'live' ? 'live' : 'simulation';
    }

    public function isSimulation(): bool
    {
        return $this->mode() === 'simulation';
    }

    /** Le canal dispose-t-il des paramètres requis par son fournisseur ? */
    public function configured(string $channel): bool
    {
        $v = $this->get($channel);

        return match ($channel) {
            'email' => $v['driver'] === 'log' || (filled($v['host']) && filled($v['port']) && filled($v['from_address'])),
            'whatsapp' => match ($v['provider']) {
                'meta_cloud' => filled($v['access_token']) && collect($v['numbers'])->contains(fn ($n) => $n['active'] && filled($n['phone_number_id'])),
                'twilio' => filled($v['account_sid']) && filled($v['auth_token']) && collect($v['numbers'])->contains(fn ($n) => $n['active'] && filled($n['phone'])),
                default => false,
            },
            'sms' => match ($v['provider']) {
                'twilio' => filled($v['account_sid']) && filled($v['auth_token']) && filled($v['sender_id']),
                'africastalking' => filled($v['username']) && filled($v['api_key']),
                'infobip' => filled($v['base_url']) && filled($v['api_key']),
                'http' => filled($v['base_url']),
                default => false,
            },
        };
    }

    /** Fournisseur effectif d'un canal (« simulation » si le mode global l'impose). */
    public function provider(string $channel): string
    {
        if ($this->isSimulation()) {
            return 'simulation';
        }
        $v = $this->get($channel);

        return $channel === 'email' ? ($v['driver'] === 'smtp' ? 'smtp' : 'log') : $v['provider'];
    }

    // ── Écriture ────────────────────────────────────────────────────────────

    /**
     * Enregistre les valeurs d'un canal. Un secret laissé vide conserve la
     * valeur existante ; les secrets sont chiffrés avant stockage.
     */
    public function put(string $channel, array $values): void
    {
        $this->assertChannel($channel);
        $settings = $this->company()->settings ?? [];
        $stored = (array) data_get($settings, "integrations.{$channel}", []);
        $clean = [];
        foreach (self::DEFAULTS[$channel] as $field => $default) {
            if (in_array($field, self::SECRETS[$channel], true)) {
                $new = trim((string) ($values[$field] ?? ''));
                $clean[$field] = $new !== '' && $new !== self::MASK ? Crypt::encryptString($new) : ($stored[$field] ?? '');

                continue;
            }
            if ($field === 'numbers') {
                $clean['numbers'] = $this->normalizeNumbers($values['numbers'] ?? ($stored['numbers'] ?? []));

                continue;
            }
            $value = array_key_exists($field, $values) ? $values[$field] : ($stored[$field] ?? $default);
            $clean[$field] = is_int($default) ? (int) $value : trim((string) $value);
        }
        data_set($settings, "integrations.{$channel}", $clean);
        $this->save($settings);
    }

    /** Efface explicitement un secret. */
    public function forgetSecret(string $channel, string $field): void
    {
        abort_unless(in_array($field, self::SECRETS[$channel] ?? [], true), 422);
        $settings = $this->company()->settings ?? [];
        data_set($settings, "integrations.{$channel}.{$field}", '');
        $this->save($settings);
    }

    public function setMode(string $mode): void
    {
        $settings = $this->company()->settings ?? [];
        data_set($settings, 'integrations.mode', $mode === 'live' ? 'live' : 'simulation');
        $this->save($settings);
    }

    // ── Outils ──────────────────────────────────────────────────────────────

    /** Exactement trois numéros WhatsApp {label, phone (E.164), phone_number_id, active}. */
    public function normalizeNumbers(array $numbers): array
    {
        $numbers = array_values($numbers);
        $out = [];
        for ($i = 0; $i < self::NUMBER_SLOTS; $i++) {
            $n = (array) ($numbers[$i] ?? []);
            $phone = trim((string) ($n['phone'] ?? ''));
            $out[] = [
                'label' => trim((string) ($n['label'] ?? '')) ?: 'Ligne '.($i + 1),
                'phone' => $phone === '' ? '' : CommunicationService::normalizePhoneNumber($phone),
                'phone_number_id' => trim((string) ($n['phone_number_id'] ?? '')),
                'active' => (bool) ($n['active'] ?? false),
            ];
        }

        return $out;
    }

    private function save(array $settings): void
    {
        $company = $this->company();
        $company->forceFill(['settings' => $settings])->save();
        Tenant::flush(); // la société mise en cache pour la requête doit relire les nouveaux paramètres
    }

    private function decrypt(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return '';
        }
    }

    private function assertChannel(string $channel): void
    {
        if (! in_array($channel, self::CHANNELS, true)) {
            throw new InvalidArgumentException("Canal inconnu : {$channel}");
        }
    }
}
