<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Signataires des bons et documents officiels, personnalisables dans Paramètres › Documents.
 */
class DocumentSignatures
{
    /**
     * @var array<string, array{label: string, signatories: list<string>}>
     */
    public const DEFAULTS = [
        'voucher' => ['label' => 'Bons (caisse, achat, sortie)', 'signatories' => ['Demandeur', 'Chef de service', 'Directeur financier', 'Directeur Général']],
        'stock_operation' => ['label' => 'Bons d’entrée / de sortie magasin', 'signatories' => ['Magasinier', 'Remis / livré par', 'Responsable entrepôt']],
        'stock_transfer' => ['label' => 'Bons de transfert inter-entrepôts', 'signatories' => ['Expédié par', 'Transporteur', 'Réceptionné par', 'Responsable entrepôt']],
        'warehouse_report' => ['label' => 'Rapports journaliers d’entrepôt', 'signatories' => ['Magasinier', 'Responsable entrepôt', 'Contrôle de gestion']],
        'delivery_note' => ['label' => 'Bons de livraison', 'signatories' => ['Livreur', 'Client — reçu conforme', 'Responsable logistique']],
        'proof' => ['label' => 'Bons à tirer (BAT)', 'signatories' => ['Graphiste', 'Client — bon pour accord', 'Responsable graphisme']],
        'print_order' => ['label' => 'Fiches techniques d’impression', 'signatories' => ['Graphiste', 'Responsable graphisme', 'Responsable impression']],
        'quote' => ['label' => 'Devis & pro forma', 'signatories' => ['Bon pour accord — Client', 'Pour Surnaturel Print']],
        'invoice' => ['label' => 'Factures', 'signatories' => ['Le client', 'Pour Surnaturel Print']],
        'cash_session' => ['label' => 'Clôtures de caisse', 'signatories' => ['Caissier', 'Superviseur']],
        'payslip' => ['label' => 'Fiches de paie', 'signatories' => ['L’agent', 'Ressources humaines', 'Directeur Général']],
        'affiliation' => ['label' => 'Attestations d’affiliation santé & assurance', 'signatories' => ['L’agent', 'Ressources humaines', 'Directeur Général']],
        'followup_report' => ['label' => 'Rapports de suivi clients', 'signatories' => ['Agent marketing', 'Responsable marketing']],
    ];

    /**
     * @return list<string>
     */
    public static function for(string $type): array
    {
        $configured = Setting::get('documents.signatures', [])[$type]['signatories'] ?? null;

        return array_values(array_filter($configured ?: self::DEFAULTS[$type]['signatories'] ?? []));
    }

    /**
     * @return array<string, array{label: string, signatories: list<string>}>
     */
    public static function all(): array
    {
        $configured = Setting::get('documents.signatures', []);

        return collect(self::DEFAULTS)->map(fn (array $default, string $type): array => [
            'label' => $default['label'],
            'signatories' => $configured[$type]['signatories'] ?? $default['signatories'],
        ])->all();
    }
}
