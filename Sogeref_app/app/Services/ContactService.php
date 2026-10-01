<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Création des fiches contacts (clients, prospects, fournisseurs, partenaires)
 * partagée par Contacts, l'Annuaire, le Pipeline et la Réception :
 * référence normalisée et détection des doublons (téléphone, e-mail, nom).
 */
class ContactService
{
    public const PREFIXES = ['client' => 'CU', 'supplier' => 'FRN', 'prospect' => 'PRS', 'partner' => 'PRT'];

    private static array $columns = [];

    /** Colonne optionnelle (ex. whatsapp, marketing_opt_in ajoutées par le module Communication). */
    public static function hasColumn(string $column): bool
    {
        return self::$columns[$column] ??= Schema::hasColumn('contacts', $column);
    }

    public function nextReference(string $type, ?int $companyId = null): string
    {
        $companyId ??= company_id();
        $prefix = self::PREFIXES[$type] ?? 'CT';
        $count = Contact::withTrashed()->where('company_id', $companyId)->where('type', $type)->count() + 1;
        do {
            $reference = $prefix.now()->format('ym').'-'.str_pad((string) $count++, 5, '0', STR_PAD_LEFT);
        } while (Contact::withTrashed()->where('company_id', $companyId)->where('reference', $reference)->exists());

        return $reference;
    }

    /** Crée une fiche ; les colonnes optionnelles absentes du schéma sont ignorées. */
    public function create(array $data, ?int $companyId = null): Contact
    {
        $companyId ??= company_id();
        foreach (['whatsapp', 'marketing_opt_in'] as $optional) {
            if (array_key_exists($optional, $data) && ! self::hasColumn($optional)) {
                unset($data[$optional]);
            }
        }

        return Contact::create($data + [
            'company_id' => $companyId,
            'reference' => $this->nextReference($data['type'] ?? 'client', $companyId),
            'currency_code' => 'USD',
            'owner_id' => auth()->id(),
        ]);
    }

    /**
     * Fiches existantes partageant le téléphone, l'e-mail ou le nom.
     *
     * @return Collection<int, Contact>
     */
    public function duplicates(?string $phone, ?string $email, ?string $name, ?int $exceptId = null, ?int $companyId = null): Collection
    {
        $companyId ??= company_id();
        $digits = $phone ? substr(preg_replace('/\D+/', '', $phone), -9) : '';
        $email = $email ? mb_strtolower(trim($email)) : '';
        $name = $name ? trim($name) : '';
        if (strlen($digits) < 6 && $email === '' && mb_strlen($name) < 3) {
            return collect();
        }

        return Contact::where('company_id', $companyId)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->where(function ($q) use ($digits, $email, $name) {
                if (strlen($digits) >= 6) {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '+', ''), '-', ''), '.', '') LIKE ?", ['%'.$digits]);
                    if (self::hasColumn('whatsapp')) {
                        $q->orWhereRaw("REPLACE(REPLACE(REPLACE(COALESCE(whatsapp, ''), ' ', ''), '+', ''), '-', '') LIKE ?", ['%'.$digits]);
                    }
                }
                if ($email !== '') {
                    $q->orWhereRaw('LOWER(email) = ?', [$email]);
                }
                if (mb_strlen($name) >= 3) {
                    $q->orWhere('company_name', $name);
                    [$a, $b] = array_pad(preg_split('/\s+/', $name, 2), 2, null);
                    if ($b) {
                        $q->orWhere(fn ($q) => $q->where('first_name', $a)->where('last_name', $b))
                            ->orWhere(fn ($q) => $q->where('last_name', $a)->where('first_name', $b));
                    }
                }
            })
            ->limit(5)->get();
    }
}
