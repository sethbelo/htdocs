<?php

namespace App\Livewire\Concerns;

use App\Support\RouteKey;

/**
 * Filtres d'URL sans identifiant numérique visible.
 *
 * Le composant garde sa propriété entière (ex. $siteId, liée au <select>),
 * tandis qu'une propriété « jeton » porte l'attribut #[Url] (?chantier=Xb3…).
 * Le jeton est décodé au montage (decodeUrlIds() en tête de mount()) puis
 * resynchronisé à chaque réponse, de sorte que l'URL suit les changements
 * de filtre sans jamais exposer l'identifiant réel.
 */
trait EncryptsUrlIds
{
    /** @return array<string, array{0: string, 1: class-string}> propriété entière => [propriété jeton, modèle] */
    abstract protected function encryptedUrlIds(): array;

    protected function decodeUrlIds(): void
    {
        foreach ($this->encryptedUrlIds() as $property => [$tokenProperty, $model]) {
            $this->{$property} = RouteKey::decode($model, $this->{$tokenProperty});
        }
    }

    public function dehydrateEncryptsUrlIds(): void
    {
        foreach ($this->encryptedUrlIds() as $property => [$tokenProperty, $model]) {
            $id = (int) $this->{$property};
            $this->{$tokenProperty} = $id > 0 ? RouteKey::encode($model, $id) : null;
        }
    }
}
