<?php

namespace App\Services\Communication;

/** Résultat d'un envoi par un pilote (jamais d'exception vers l'interface). */
final class SendResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $provider,
        public readonly ?string $providerId = null,
        public readonly ?string $error = null,
    ) {}

    public static function sent(string $provider, ?string $providerId = null): self
    {
        return new self(true, $provider, $providerId);
    }

    public static function failed(string $provider, string $error): self
    {
        return new self(false, $provider, null, mb_substr($error, 0, 1000));
    }
}
