<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Journal;
use Illuminate\Database\Seeder;

/**
 * Plan comptable SYSCOHADA révisé (extrait opérationnel) et journaux, configurables ensuite.
 */
class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['101', 'Capital social', 1, AccountType::Equity],
            ['121', 'Report à nouveau créditeur', 1, AccountType::Equity],
            ['131', 'Résultat net : bénéfice', 1, AccountType::Equity],
            ['162', 'Emprunts auprès des établissements de crédit', 1, AccountType::Liability],
            ['215', 'Fonds commercial', 2, AccountType::Asset],
            ['241', 'Matériel et outillage industriel', 2, AccountType::Asset],
            ['244', 'Matériel et mobilier de bureau', 2, AccountType::Asset],
            ['245', 'Matériel de transport', 2, AccountType::Asset],
            ['311', 'Marchandises', 3, AccountType::Asset],
            ['321', 'Matières premières (papier, supports)', 3, AccountType::Asset],
            ['331', 'Consommables (encres, toners)', 3, AccountType::Asset],
            ['361', 'Produits finis', 3, AccountType::Asset],
            ['401', 'Fournisseurs', 4, AccountType::Liability],
            ['411', 'Clients', 4, AccountType::Asset],
            ['421', 'Personnel, avances et acomptes', 4, AccountType::Asset],
            ['422', 'Personnel, rémunérations dues', 4, AccountType::Liability],
            ['431', 'Sécurité sociale', 4, AccountType::Liability],
            ['4431', 'TVA facturée sur ventes', 4, AccountType::Liability],
            ['4452', 'TVA récupérable sur achats', 4, AccountType::Asset],
            ['447', 'État, impôts retenus à la source', 4, AccountType::Liability],
            ['521', 'Banques locales', 5, AccountType::Asset],
            ['5211', 'Mobile Money', 5, AccountType::Asset],
            ['571', 'Caisse siège', 5, AccountType::Asset],
            ['5711', 'Caisse boutique', 5, AccountType::Asset],
            ['585', 'Virements de fonds', 5, AccountType::Asset],
            ['601', 'Achats de marchandises', 6, AccountType::Expense],
            ['602', 'Achats de matières premières', 6, AccountType::Expense],
            ['604', 'Achats stockés de matières et fournitures consommables', 6, AccountType::Expense],
            ['605', 'Autres achats', 6, AccountType::Expense],
            ['618', 'Autres frais de transport', 6, AccountType::Expense],
            ['624', 'Entretien, réparations et maintenance', 6, AccountType::Expense],
            ['638', 'Autres charges externes (frais de mission)', 6, AccountType::Expense],
            ['658', 'Charges diverses (écarts de caisse)', 6, AccountType::Expense],
            ['661', 'Rémunérations directes versées au personnel', 6, AccountType::Expense],
            ['664', 'Charges sociales', 6, AccountType::Expense],
            ['673', 'Escomptes accordés', 6, AccountType::Expense],
            ['701', 'Ventes de marchandises', 7, AccountType::Income],
            ['702', 'Ventes de produits finis', 7, AccountType::Income],
            ['706', 'Services vendus (travaux d’impression)', 7, AccountType::Income],
            ['707', 'Produits accessoires (livraison)', 7, AccountType::Income],
            ['758', 'Produits divers (écarts de caisse)', 7, AccountType::Income],
        ];

        foreach ($accounts as [$code, $name, $class, $type]) {
            Account::query()->updateOrCreate(['code' => $code], ['name' => $name, 'class' => $class, 'type' => $type]);
        }

        $journals = [
            ['VEN', 'Journal des ventes', 'sales', null],
            ['ACH', 'Journal des achats', 'purchases', null],
            ['CAI', 'Journal de caisse', 'cash', '571'],
            ['BQ', 'Journal de banque', 'bank', '521'],
            ['MM', 'Journal Mobile Money', 'mobile_money', '5211'],
            ['OD', 'Opérations diverses', 'general', null],
        ];

        foreach ($journals as [$code, $name, $type, $account]) {
            Journal::query()->updateOrCreate(['code' => $code], [
                'name' => $name,
                'type' => $type,
                'default_account_id' => $account ? Account::query()->where('code', $account)->value('id') : null,
            ]);
        }
    }
}
