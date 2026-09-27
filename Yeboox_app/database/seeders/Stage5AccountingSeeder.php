<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Expense;
use App\Models\FiscalYear;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Database\Seeder;

class Stage5AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@yeboox.local')->first();
        if (! $admin) {
            return;
        }

        $eid = $admin->establishment_id;

        // ── Exercice Fiscal ──────────────────────────────
        $fy = FiscalYear::create([
            'establishment_id' => $eid,
            'name' => 'Exercice 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);

        // ── Journaux Comptables ──────────────────────────────
        $journals = [
            ['code' => 'VT', 'name' => 'Journal des Ventes', 'type' => 'sales'],
            ['code' => 'AC', 'name' => 'Journal des Achats', 'type' => 'purchases'],
            ['code' => 'BQ', 'name' => 'Journal de Banque', 'type' => 'bank'],
            ['code' => 'CA', 'name' => 'Journal de Caisse', 'type' => 'cash'],
            ['code' => 'OD', 'name' => 'Journal des Opérations Diverses', 'type' => 'operations'],
            ['code' => 'AN', 'name' => 'Journal d\'À-Nouveau', 'type' => 'opening'],
        ];
        foreach ($journals as $j) {
            Journal::create(array_merge($j, ['establishment_id' => $eid]));
        }

        // ── Plan Comptable SYSCOHADA ──────────────────────────────
        $accounts = [
            // Classe 1 — Comptes de ressources durables
            ['code' => '10', 'name' => 'Capital', 'class' => 1, 'type' => 'equity', 'is_group' => true],
            ['code' => '101', 'name' => 'Capital social', 'class' => 1, 'type' => 'equity', 'parent' => '10'],
            ['code' => '12', 'name' => 'Report à nouveau', 'class' => 1, 'type' => 'equity', 'is_group' => true],
            ['code' => '121', 'name' => 'Report à nouveau créditeur', 'class' => 1, 'type' => 'equity', 'parent' => '12'],
            ['code' => '129', 'name' => 'Report à nouveau débiteur', 'class' => 1, 'type' => 'equity', 'parent' => '12'],
            ['code' => '13', 'name' => 'Résultat net de l\'exercice', 'class' => 1, 'type' => 'equity', 'is_group' => true],
            ['code' => '131', 'name' => 'Résultat net : bénéfice', 'class' => 1, 'type' => 'equity', 'parent' => '13'],
            ['code' => '139', 'name' => 'Résultat net : perte', 'class' => 1, 'type' => 'equity', 'parent' => '13'],
            ['code' => '16', 'name' => 'Emprunts et dettes', 'class' => 1, 'type' => 'liability', 'is_group' => true],
            ['code' => '162', 'name' => 'Emprunts auprès des établissements de crédit', 'class' => 1, 'type' => 'liability', 'parent' => '16'],

            // Classe 2 — Comptes d'actif immobilisé
            ['code' => '21', 'name' => 'Immobilisations incorporelles', 'class' => 2, 'type' => 'asset', 'is_group' => true],
            ['code' => '213', 'name' => 'Logiciels', 'class' => 2, 'type' => 'asset', 'parent' => '21'],
            ['code' => '22', 'name' => 'Terrains', 'class' => 2, 'type' => 'asset', 'is_group' => true],
            ['code' => '23', 'name' => 'Bâtiments, installations', 'class' => 2, 'type' => 'asset', 'is_group' => true],
            ['code' => '231', 'name' => 'Bâtiments', 'class' => 2, 'type' => 'asset', 'parent' => '23'],
            ['code' => '24', 'name' => 'Matériel', 'class' => 2, 'type' => 'asset', 'is_group' => true],
            ['code' => '241', 'name' => 'Matériel et outillage industriel', 'class' => 2, 'type' => 'asset', 'parent' => '24'],
            ['code' => '244', 'name' => 'Matériel et mobilier', 'class' => 2, 'type' => 'asset', 'parent' => '24'],
            ['code' => '245', 'name' => 'Matériel de transport', 'class' => 2, 'type' => 'asset', 'parent' => '24'],
            ['code' => '28', 'name' => 'Amortissements', 'class' => 2, 'type' => 'asset', 'is_group' => true],
            ['code' => '281', 'name' => 'Amortissements des immobilisations', 'class' => 2, 'type' => 'asset', 'parent' => '28'],

            // Classe 3 — Comptes de stocks
            ['code' => '31', 'name' => 'Marchandises', 'class' => 3, 'type' => 'asset', 'is_group' => true],
            ['code' => '311', 'name' => 'Marchandises en stock', 'class' => 3, 'type' => 'asset', 'parent' => '31'],
            ['code' => '32', 'name' => 'Matières premières', 'class' => 3, 'type' => 'asset', 'is_group' => true],
            ['code' => '321', 'name' => 'Matières premières', 'class' => 3, 'type' => 'asset', 'parent' => '32'],
            ['code' => '33', 'name' => 'Autres approvisionnements', 'class' => 3, 'type' => 'asset', 'is_group' => true],
            ['code' => '335', 'name' => 'Emballages', 'class' => 3, 'type' => 'asset', 'parent' => '33'],

            // Classe 4 — Comptes de tiers
            ['code' => '40', 'name' => 'Fournisseurs', 'class' => 4, 'type' => 'liability', 'is_group' => true],
            ['code' => '401', 'name' => 'Fournisseurs, dettes en compte', 'class' => 4, 'type' => 'liability', 'parent' => '40'],
            ['code' => '41', 'name' => 'Clients', 'class' => 4, 'type' => 'asset', 'is_group' => true],
            ['code' => '411', 'name' => 'Clients, créances en compte', 'class' => 4, 'type' => 'asset', 'parent' => '41'],
            ['code' => '42', 'name' => 'Personnel', 'class' => 4, 'type' => 'liability', 'is_group' => true],
            ['code' => '421', 'name' => 'Personnel, rémunérations dues', 'class' => 4, 'type' => 'liability', 'parent' => '42'],
            ['code' => '43', 'name' => 'Organismes sociaux', 'class' => 4, 'type' => 'liability', 'is_group' => true],
            ['code' => '431', 'name' => 'Sécurité sociale', 'class' => 4, 'type' => 'liability', 'parent' => '43'],
            ['code' => '44', 'name' => 'État et collectivités', 'class' => 4, 'type' => 'liability', 'is_group' => true],
            ['code' => '441', 'name' => 'État, impôt sur les bénéfices', 'class' => 4, 'type' => 'liability', 'parent' => '44'],
            ['code' => '443', 'name' => 'État, TVA facturée', 'class' => 4, 'type' => 'liability', 'parent' => '44'],
            ['code' => '445', 'name' => 'État, TVA récupérable', 'class' => 4, 'type' => 'asset', 'parent' => '44'],

            // Classe 5 — Comptes de trésorerie
            ['code' => '52', 'name' => 'Banques', 'class' => 5, 'type' => 'asset', 'is_group' => true],
            ['code' => '521', 'name' => 'Banque compte courant', 'class' => 5, 'type' => 'asset', 'parent' => '52'],
            ['code' => '57', 'name' => 'Caisse', 'class' => 5, 'type' => 'asset', 'is_group' => true],
            ['code' => '571', 'name' => 'Caisse en monnaie locale', 'class' => 5, 'type' => 'asset', 'parent' => '57'],
            ['code' => '58', 'name' => 'Virements internes', 'class' => 5, 'type' => 'asset', 'is_group' => true],
            ['code' => '585', 'name' => 'Mobile Money', 'class' => 5, 'type' => 'asset', 'parent' => '58'],

            // Classe 6 — Comptes de charges
            ['code' => '60', 'name' => 'Achats et variations de stocks', 'class' => 6, 'type' => 'expense', 'is_group' => true],
            ['code' => '601', 'name' => 'Achats de marchandises', 'class' => 6, 'type' => 'expense', 'parent' => '60'],
            ['code' => '602', 'name' => 'Achats de matières premières', 'class' => 6, 'type' => 'expense', 'parent' => '60'],
            ['code' => '604', 'name' => 'Achats de fournitures', 'class' => 6, 'type' => 'expense', 'parent' => '60'],
            ['code' => '61', 'name' => 'Transports', 'class' => 6, 'type' => 'expense', 'is_group' => true],
            ['code' => '611', 'name' => 'Transports sur achats', 'class' => 6, 'type' => 'expense', 'parent' => '61'],
            ['code' => '62', 'name' => 'Services extérieurs A', 'class' => 6, 'type' => 'expense', 'is_group' => true],
            ['code' => '622', 'name' => 'Locations et charges locatives', 'class' => 6, 'type' => 'expense', 'parent' => '62'],
            ['code' => '624', 'name' => 'Entretien, réparations', 'class' => 6, 'type' => 'expense', 'parent' => '62'],
            ['code' => '625', 'name' => 'Primes d\'assurance', 'class' => 6, 'type' => 'expense', 'parent' => '62'],
            ['code' => '63', 'name' => 'Services extérieurs B', 'class' => 6, 'type' => 'expense', 'is_group' => true],
            ['code' => '631', 'name' => 'Frais bancaires', 'class' => 6, 'type' => 'expense', 'parent' => '63'],
            ['code' => '632', 'name' => 'Rémunérations d\'intermédiaires', 'class' => 6, 'type' => 'expense', 'parent' => '63'],
            ['code' => '64', 'name' => 'Impôts et taxes', 'class' => 6, 'type' => 'expense', 'is_group' => true],
            ['code' => '641', 'name' => 'Impôts et taxes directes', 'class' => 6, 'type' => 'expense', 'parent' => '64'],
            ['code' => '66', 'name' => 'Charges de personnel', 'class' => 6, 'type' => 'expense', 'is_group' => true],
            ['code' => '661', 'name' => 'Rémunérations directes', 'class' => 6, 'type' => 'expense', 'parent' => '66'],
            ['code' => '664', 'name' => 'Charges sociales', 'class' => 6, 'type' => 'expense', 'parent' => '66'],
            ['code' => '67', 'name' => 'Frais financiers', 'class' => 6, 'type' => 'expense', 'is_group' => true],
            ['code' => '671', 'name' => 'Intérêts des emprunts', 'class' => 6, 'type' => 'expense', 'parent' => '67'],
            ['code' => '68', 'name' => 'Dotations aux amortissements', 'class' => 6, 'type' => 'expense', 'is_group' => true],
            ['code' => '681', 'name' => 'Dotations aux amortissements', 'class' => 6, 'type' => 'expense', 'parent' => '68'],

            // Classe 7 — Comptes de produits
            ['code' => '70', 'name' => 'Ventes', 'class' => 7, 'type' => 'revenue', 'is_group' => true],
            ['code' => '701', 'name' => 'Ventes de marchandises', 'class' => 7, 'type' => 'revenue', 'parent' => '70'],
            ['code' => '706', 'name' => 'Services vendus', 'class' => 7, 'type' => 'revenue', 'parent' => '70'],
            ['code' => '707', 'name' => 'Produits accessoires', 'class' => 7, 'type' => 'revenue', 'parent' => '70'],
            ['code' => '71', 'name' => 'Subventions d\'exploitation', 'class' => 7, 'type' => 'revenue', 'is_group' => true],
            ['code' => '75', 'name' => 'Autres produits', 'class' => 7, 'type' => 'revenue', 'is_group' => true],
            ['code' => '77', 'name' => 'Revenus financiers', 'class' => 7, 'type' => 'revenue', 'is_group' => true],
            ['code' => '771', 'name' => 'Intérêts de prêts', 'class' => 7, 'type' => 'revenue', 'parent' => '77'],
        ];

        // Create accounts with parent linkage
        $accountMap = [];
        foreach ($accounts as $a) {
            $parentId = null;
            if (isset($a['parent']) && isset($accountMap[$a['parent']])) {
                $parentId = $accountMap[$a['parent']];
            }
            $account = Account::create([
                'establishment_id' => $eid,
                'code' => $a['code'],
                'name' => $a['name'],
                'class' => $a['class'],
                'type' => $a['type'],
                'parent_id' => $parentId,
                'is_group' => $a['is_group'] ?? false,
            ]);
            $accountMap[$a['code']] = $account->id;
        }

        // ── Demo Journal Entries ──────────────────────────────
        $vtJournal = Journal::where('establishment_id', $eid)->where('code', 'VT')->first();
        $caJournal = Journal::where('establishment_id', $eid)->where('code', 'CA')->first();
        $acJournal = Journal::where('establishment_id', $eid)->where('code', 'AC')->first();

        // Entry 1: Vente au comptant
        $e1 = JournalEntry::create([
            'establishment_id' => $eid,
            'journal_id' => $vtJournal->id,
            'fiscal_year_id' => $fy->id,
            'entry_number' => 'VT-2026-00001',
            'entry_date' => now()->subDays(5),
            'reference' => 'CMD-260721-0001',
            'description' => 'Vente au comptant — Commande restaurant',
            'status' => 'posted',
            'created_by' => $admin->id,
            'validated_by' => $admin->id,
            'validated_at' => now()->subDays(5),
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $e1->id,
            'account_id' => $accountMap['571'], // Caisse
            'debit' => 125000,
            'credit' => 0,
            'label' => 'Encaissement vente CMD-260721-0001',
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $e1->id,
            'account_id' => $accountMap['701'], // Ventes
            'debit' => 0,
            'credit' => 125000,
            'label' => 'Vente marchandises CMD-260721-0001',
        ]);

        // Entry 2: Achat de fournitures
        $e2 = JournalEntry::create([
            'establishment_id' => $eid,
            'journal_id' => $acJournal->id,
            'fiscal_year_id' => $fy->id,
            'entry_number' => 'AC-2026-00001',
            'entry_date' => now()->subDays(3),
            'reference' => 'FACT-FOURN-0042',
            'description' => 'Achat matières premières — Marché central',
            'status' => 'posted',
            'created_by' => $admin->id,
            'validated_by' => $admin->id,
            'validated_at' => now()->subDays(3),
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $e2->id,
            'account_id' => $accountMap['602'], // Achats matières premières
            'debit' => 85000,
            'credit' => 0,
            'label' => 'Achat matières premières',
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $e2->id,
            'account_id' => $accountMap['401'], // Fournisseurs
            'debit' => 0,
            'credit' => 85000,
            'label' => 'Dette fournisseur — Marché central',
        ]);

        // Entry 3: Paiement loyer
        $e3 = JournalEntry::create([
            'establishment_id' => $eid,
            'journal_id' => $caJournal->id,
            'fiscal_year_id' => $fy->id,
            'entry_number' => 'CA-2026-00001',
            'entry_date' => now()->subDays(1),
            'description' => 'Paiement loyer mensuel',
            'status' => 'posted',
            'created_by' => $admin->id,
            'validated_by' => $admin->id,
            'validated_at' => now()->subDays(1),
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $e3->id,
            'account_id' => $accountMap['622'], // Loyer
            'debit' => 350000,
            'credit' => 0,
            'label' => 'Loyer juillet 2026',
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $e3->id,
            'account_id' => $accountMap['571'], // Caisse
            'debit' => 0,
            'credit' => 350000,
            'label' => 'Décaissement loyer',
        ]);

        // Entry 4: Vente brouillon (non validée)
        $e4 = JournalEntry::create([
            'establishment_id' => $eid,
            'journal_id' => $vtJournal->id,
            'fiscal_year_id' => $fy->id,
            'entry_number' => 'VT-2026-00002',
            'entry_date' => now(),
            'reference' => 'CMD-260726-0001',
            'description' => 'Vente — en attente validation',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $e4->id,
            'account_id' => $accountMap['585'], // Mobile Money
            'debit' => 45000,
            'credit' => 0,
            'label' => 'Encaissement Mobile Money',
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $e4->id,
            'account_id' => $accountMap['701'], // Ventes
            'debit' => 0,
            'credit' => 45000,
            'label' => 'Vente marchandises',
        ]);

        // ── Demo Expenses ──────────────────────────────
        Expense::create([
            'establishment_id' => $eid,
            'account_id' => $accountMap['604'],
            'category' => 'fournitures',
            'description' => 'Achat serviettes, torchons et produits nettoyage',
            'amount' => 25000,
            'expense_date' => now()->subDays(2),
            'status' => 'approved',
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now()->subDays(2),
        ]);

        Expense::create([
            'establishment_id' => $eid,
            'account_id' => $accountMap['622'],
            'category' => 'loyer',
            'description' => 'Loyer local commercial — Juillet 2026',
            'amount' => 350000,
            'expense_date' => now()->subDays(1),
            'status' => 'approved',
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now()->subDays(1),
        ]);

        Expense::create([
            'establishment_id' => $eid,
            'category' => 'transport',
            'description' => 'Course taxi livraison urgente',
            'amount' => 8500,
            'expense_date' => now(),
            'status' => 'pending',
            'created_by' => $admin->id,
        ]);
    }
}
