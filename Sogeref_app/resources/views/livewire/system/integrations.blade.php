{{-- Master système › Intégrations & API (e-mail, WhatsApp × 3 numéros, SMS). Les secrets ne sont jamais réaffichés. --}}
<div class="space-y-6">
    {{-- ─── MODE ───────────────────────────────────────────────────────── --}}
    <x-ui.card title="Mode d’envoi" subtitle="En simulation, les messages sont journalisés dans l’ERP sans jamais quitter le serveur" icon="shield-check">
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach (['simulation' => ['Simulation', 'Formation, essais et démonstrations : aucun message réel, aucun coût.', 'beaker'], 'live' => ['Production', 'Les e-mails, WhatsApp et SMS partent réellement vers les clients via les API configurées.', 'bolt']] as $value => [$label, $text, $icon])
                <label @class(['flex cursor-pointer gap-3 rounded-xl border p-4 transition', 'border-brand-500 bg-brand-50/60 ring-2 ring-brand-500/15 dark:bg-white/6' => $mode === $value, 'border-line hover:border-line-strong' => $mode !== $value])>
                    <input type="radio" wire:model.live="mode" value="{{ $value }}" class="mt-1" @disabled(! $canEdit)>
                    <span>
                        <span class="flex items-center gap-2 font-semibold"><x-ui.icon :name="$icon" class="size-4" />{{ $label }}</span>
                        <span class="mt-1 block text-[12.5px] text-fg-subtle">{{ $text }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @if ($mode === 'live')
            <p class="mt-4 flex items-start gap-2 rounded-lg bg-flame-50 p-3 text-[12.5px] text-flame-700 dark:bg-flame-400/10 dark:text-flame-300"><x-ui.icon name="exclamation-triangle" class="size-4 shrink-0" /> En production, chaque envoi est réel et peut être facturé par le fournisseur. Testez chaque canal avant d’activer ce mode.</p>
        @endif
        @if ($canEdit)
            <div class="mt-4 flex justify-end"><x-ui.button icon="check" wire:click="saveMode">Appliquer le mode</x-ui.button></div>
        @endif
    </x-ui.card>

    {{-- ─── E-MAIL ─────────────────────────────────────────────────────── --}}
    <x-ui.card title="E-mail aux clients (SMTP)" subtitle="Serveur d’envoi des e-mails, relances et campagnes" icon="envelope">
        <x-slot:actions><x-ui.badge :tone="$configured['email'] ? 'success' : 'warning'" dot>{{ $configured['email'] ? 'Configuré' : 'À configurer' }}</x-ui.badge></x-slot:actions>
        <div class="grid gap-4 md:grid-cols-3">
            <x-ui.select label="Mode d’envoi" wire:model.live="email.driver" :options="$providers['email']" :disabled="! $canEdit" />
            <x-ui.input label="Serveur SMTP" wire:model="email.host" placeholder="smtp.sogeref.cd" :disabled="! $canEdit" />
            <div class="grid grid-cols-2 gap-3">
                <x-ui.input label="Port" type="number" wire:model="email.port" :disabled="! $canEdit" />
                <x-ui.select label="Chiffrement" wire:model="email.encryption" :options="['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'Aucun']" :disabled="! $canEdit" />
            </div>
            <x-ui.input label="Identifiant" wire:model="email.username" autocomplete="off" :disabled="! $canEdit" />
            @include('livewire.system.partials.secret', ['channel' => 'email', 'field' => 'password', 'label' => 'Mot de passe', 'set' => $email['password_set'] ?? false])
            <x-ui.input label="Adresse d’expédition" type="email" wire:model="email.from_address" placeholder="contact@sogeref.cd" :disabled="! $canEdit" />
            <x-ui.input label="Nom d’expéditeur" wire:model="email.from_name" placeholder="SOGEREF Sarl" :disabled="! $canEdit" />
            <x-ui.input label="Adresse de réponse" type="email" wire:model="email.reply_to" :disabled="! $canEdit" />
        </div>
        @include('livewire.system.partials.integration-actions', ['channel' => 'email', 'placeholder' => 'adresse@exemple.cd'])
    </x-ui.card>

    {{-- ─── WHATSAPP ───────────────────────────────────────────────────── --}}
    <x-ui.card title="WhatsApp Business" subtitle="Trois numéros pour la relation client : chaque nouveau correspondant est attribué au hasard à un numéro actif, puis reste sur ce numéro" icon="chat-bubble-oval-left-ellipsis">
        <x-slot:actions><x-ui.badge :tone="$configured['whatsapp'] ? 'success' : 'warning'" dot>{{ $configured['whatsapp'] ? 'Configuré' : 'À configurer' }}</x-ui.badge></x-slot:actions>
        <div class="grid gap-4 md:grid-cols-3">
            <x-ui.select label="Fournisseur" wire:model.live="whatsapp.provider" :options="$providers['whatsapp']" :disabled="! $canEdit" />
            @if ($whatsapp['provider'] === 'meta_cloud')
                @include('livewire.system.partials.secret', ['channel' => 'whatsapp', 'field' => 'access_token', 'label' => 'Jeton d’accès permanent', 'set' => $whatsapp['access_token_set'] ?? false])
                <x-ui.input label="ID du compte WhatsApp Business" wire:model="whatsapp.business_account_id" :disabled="! $canEdit" />
                @include('livewire.system.partials.secret', ['channel' => 'whatsapp', 'field' => 'app_secret', 'label' => 'Clé secrète de l’application (signature)', 'set' => $whatsapp['app_secret_set'] ?? false])
            @elseif ($whatsapp['provider'] === 'twilio')
                <x-ui.input label="Account SID" wire:model="whatsapp.account_sid" :disabled="! $canEdit" />
                @include('livewire.system.partials.secret', ['channel' => 'whatsapp', 'field' => 'auth_token', 'label' => 'Auth token', 'set' => $whatsapp['auth_token_set'] ?? false])
            @endif
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-3">
            @foreach ($whatsapp['numbers'] as $i => $number)
                <div wire:key="wa-number-{{ $i }}" @class(['rounded-2xl border p-4', 'border-emerald-300 bg-emerald-50/40 dark:border-emerald-400/30 dark:bg-emerald-400/5' => $number['active'], 'border-line bg-surface-2' => ! $number['active']])>
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <span class="flex items-center gap-2 font-display font-semibold"><span class="grid size-7 place-items-center rounded-full bg-brand-600 text-[12px] font-bold text-white">{{ $i + 1 }}</span> Numéro {{ $i + 1 }}</span>
                        <x-ui.toggle wire:model="whatsapp.numbers.{{ $i }}.active" :disabled="! $canEdit" />
                    </div>
                    <div class="space-y-3">
                        <x-ui.input label="Libellé" wire:model="whatsapp.numbers.{{ $i }}.label" placeholder="Service client" :disabled="! $canEdit" />
                        <x-ui.input label="Numéro WhatsApp" wire:model="whatsapp.numbers.{{ $i }}.phone" placeholder="+243 81 000 0000" :disabled="! $canEdit" />
                        @if ($whatsapp['provider'] === 'meta_cloud')
                            <x-ui.input label="Phone number ID (Meta)" wire:model="whatsapp.numbers.{{ $i }}.phone_number_id" :disabled="! $canEdit" />
                        @endif
                    </div>
                    @php $stats = $numbers[$i] ?? []; @endphp
                    <p class="mt-3 text-[12px] text-fg-subtle">{{ $stats['conversations'] ?? 0 }} conversation(s) · {{ $stats['messages_30d'] ?? 0 }} message(s) sur 30 jours</p>
                    @if ($canEdit)
                        <x-ui.button variant="secondary" size="sm" icon="paper-airplane" class="mt-3 w-full" wire:click="test('whatsapp', {{ $i }})">Tester ce numéro</x-ui.button>
                    @endif
                </div>
            @endforeach
        </div>
        @error('whatsapp.numbers')<p class="mt-2 text-[12.5px] text-flame-600">{{ $message }}</p>@enderror
        @include('livewire.system.partials.integration-actions', ['channel' => 'whatsapp', 'placeholder' => '+243 8x xxx xxxx (pour les tests)'])
    </x-ui.card>

    {{-- ─── SMS ────────────────────────────────────────────────────────── --}}
    <x-ui.card title="SMS" subtitle="Rappels, relances et campagnes par SMS" icon="device-phone-mobile">
        <x-slot:actions><x-ui.badge :tone="$configured['sms'] ? 'success' : 'warning'" dot>{{ $configured['sms'] ? 'Configuré' : 'À configurer' }}</x-ui.badge></x-slot:actions>
        <div class="grid gap-4 md:grid-cols-3">
            <x-ui.select label="Fournisseur" wire:model.live="sms.provider" :options="$providers['sms']" :disabled="! $canEdit" />
            <x-ui.input label="Nom d’expéditeur (Sender ID)" wire:model="sms.sender_id" placeholder="SOGEREF" maxlength="11" :disabled="! $canEdit" />
            @if ($sms['provider'] === 'twilio')
                <x-ui.input label="Account SID" wire:model="sms.account_sid" :disabled="! $canEdit" />
                @include('livewire.system.partials.secret', ['channel' => 'sms', 'field' => 'auth_token', 'label' => 'Auth token', 'set' => $sms['auth_token_set'] ?? false])
            @elseif ($sms['provider'] === 'africastalking')
                <x-ui.input label="Nom d’utilisateur" wire:model="sms.username" :disabled="! $canEdit" />
                @include('livewire.system.partials.secret', ['channel' => 'sms', 'field' => 'api_key', 'label' => 'Clé API', 'set' => $sms['api_key_set'] ?? false])
            @elseif (in_array($sms['provider'], ['infobip', 'http'], true))
                <x-ui.input label="URL de base de l’API" wire:model="sms.base_url" placeholder="https://…" :disabled="! $canEdit" />
                @include('livewire.system.partials.secret', ['channel' => 'sms', 'field' => 'api_key', 'label' => 'Clé API', 'set' => $sms['api_key_set'] ?? false])
            @endif
        </div>
        @include('livewire.system.partials.integration-actions', ['channel' => 'sms', 'placeholder' => '+243 8x xxx xxxx'])
    </x-ui.card>

    {{-- ─── WEBHOOKS ───────────────────────────────────────────────────── --}}
    <x-ui.card title="Webhooks (messages entrants)" subtitle="URL à déclarer chez chaque fournisseur pour recevoir les réponses des clients et les accusés de remise — HTTPS obligatoire en production" icon="link">
        <div class="space-y-2">
            @foreach ($webhooks as $label => $url)
                <div class="flex flex-wrap items-center gap-3 rounded-lg border border-line px-3 py-2" x-data="{ copied: false }">
                    <span class="w-56 shrink-0 text-[12.5px] font-semibold text-fg-muted">{{ $label }}</span>
                    <code class="min-w-0 flex-1 truncate text-[12px]">{{ $url }}</code>
                    <button type="button" class="btn btn-ghost btn-sm" x-on:click="navigator.clipboard.writeText(@js($url)); copied = true; setTimeout(() => copied = false, 1500)"><x-ui.icon name="clipboard-document" class="size-4" /><span x-text="copied ? 'Copié' : 'Copier'"></span></button>
                </div>
            @endforeach
            <div class="flex flex-wrap items-center gap-3 rounded-lg border border-dashed border-line-strong px-3 py-2">
                <span class="w-56 shrink-0 text-[12.5px] font-semibold text-fg-muted">Jeton de vérification Meta</span>
                <code class="min-w-0 flex-1 truncate text-[12px]">{{ $canEdit ? ($verifyToken ?: '— non défini —') : '••••••••' }}</code>
                @if ($canEdit)<x-ui.button variant="ghost" size="sm" icon="arrow-path" wire:click="generateVerifyToken" wire:confirm="Générer un nouveau jeton ? Il faudra le reporter dans la console Meta.">Générer</x-ui.button>@endif
            </div>
        </div>
    </x-ui.card>
</div>
