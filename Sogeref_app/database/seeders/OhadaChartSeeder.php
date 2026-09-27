<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Plan comptable SYSCOHADA révisé (comptes utilisés par SOGEREF ERP)
 * et journaux standards.
 */
class OhadaChartSeeder extends Seeder
{
    public const ACCOUNTS = [
        // Classe 1 — Ressources durables
        ['101', 'Capital social', '1', 'equity'], ['111', 'Réserve légale', '1', 'equity'], ['121', 'Report à nouveau créditeur', '1', 'equity'],
        ['131', 'Résultat net : bénéfice', '1', 'equity'], ['162', 'Emprunts auprès des établissements de crédit', '1', 'liability'],
        // Classe 2 — Actif immobilisé
        ['231', 'Bâtiments industriels', '2', 'asset'], ['241', 'Matériel et outillage industriel', '2', 'asset'], ['244', 'Matériel et mobilier de bureau', '2', 'asset'],
        ['245', 'Matériel de transport', '2', 'asset'], ['284', 'Amortissements du matériel', '2', 'asset'],
        // Classe 3 — Stocks
        ['311', 'Marchandises', '3', 'asset'], ['321', 'Matières premières', '3', 'asset'], ['335', 'Emballages', '3', 'asset'],
        ['361', 'Produits finis', '3', 'asset'], ['371', 'Produits intermédiaires', '3', 'asset'],
        // Classe 4 — Tiers
        ['401', 'Fournisseurs', '4', 'liability'], ['408', 'Fournisseurs, factures non parvenues', '4', 'liability'], ['411', 'Clients', '4', 'asset'],
        ['419', 'Clients, avances et acomptes reçus', '4', 'liability'], ['421', 'Personnel, rémunérations dues', '4', 'liability'],
        ['422', 'Personnel, avances et acomptes', '4', 'asset'], ['431', 'Sécurité sociale (CNSS)', '4', 'liability'],
        ['4431', 'TVA facturée', '4', 'liability'], ['4452', 'TVA récupérable', '4', 'asset'], ['447', 'État, impôts retenus à la source (IPR)', '4', 'liability'],
        ['471', 'Compte d’attente', '4', 'liability'],
        // Classe 5 — Trésorerie
        ['521', 'Banques', '5', 'asset'], ['571', 'Caisse', '5', 'asset'], ['581', 'Mobile Money', '5', 'asset'], ['585', 'Virements de fonds', '5', 'asset'],
        // Classe 6 — Charges
        ['601', 'Achats de marchandises', '6', 'expense'], ['602', 'Achats de matières premières', '6', 'expense'], ['604', 'Achats stockés de matières et fournitures', '6', 'expense'],
        ['605', 'Autres achats', '6', 'expense'], ['6053', 'Fournitures d’atelier', '6', 'expense'], ['6055', 'Fournitures de bureau', '6', 'expense'],
        ['611', 'Transports sur achats', '6', 'expense'], ['618', 'Autres frais de transport', '6', 'expense'], ['622', 'Locations et charges locatives', '6', 'expense'],
        ['624', 'Entretien, réparations et maintenance', '6', 'expense'], ['625', 'Primes d’assurance', '6', 'expense'], ['628', 'Frais de télécommunications', '6', 'expense'],
        ['631', 'Frais bancaires', '6', 'expense'], ['632', 'Rémunérations d’intermédiaires et de conseils', '6', 'expense'], ['638', 'Autres charges externes', '6', 'expense'],
        ['641', 'Impôts et taxes directs', '6', 'expense'], ['661', 'Rémunérations directes versées au personnel', '6', 'expense'],
        ['663', 'Indemnités forfaitaires versées au personnel', '6', 'expense'], ['664', 'Charges sociales', '6', 'expense'],
        ['671', 'Intérêts des emprunts', '6', 'expense'], ['681', 'Dotations aux amortissements', '6', 'expense'],
        // Classe 7 — Produits
        ['701', 'Ventes de marchandises', '7', 'revenue'], ['702', 'Ventes de produits finis', '7', 'revenue'], ['704', 'Travaux facturés', '7', 'revenue'],
        ['706', 'Services vendus', '7', 'revenue'], ['707', 'Produits accessoires (locations)', '7', 'revenue'], ['758', 'Produits divers', '7', 'revenue'],
    ];

    public const JOURNALS = [
        ['VE', 'Journal des ventes', 'sales'], ['AC', 'Journal des achats', 'purchases'], ['BQ', 'Journal de banque', 'bank'],
        ['CA', 'Journal de caisse', 'cash'], ['OD', 'Opérations diverses', 'general'],
    ];

    public function run(): void
    {
        foreach (Company::all() as $company) {
            foreach (self::ACCOUNTS as [$code, $name, $class, $type]) {
                DB::table('chart_of_accounts')->updateOrInsert(
                    ['company_id' => $company->id, 'code' => $code],
                    ['name' => $name, 'class' => $class, 'type' => $type, 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
                );
            }
            foreach (self::JOURNALS as [$code, $name, $type]) {
                DB::table('journals')->updateOrInsert(['company_id' => $company->id, 'code' => $code], ['name' => $name, 'type' => $type, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
