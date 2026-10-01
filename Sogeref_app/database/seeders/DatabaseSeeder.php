<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\ExchangeRate;
use App\Models\FinancialAccount;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * SOGEREF ERP — Données de démarrage.
 *
 *   1. Rôles & permissions        4. Plan comptable SYSCOHADA
 *   2. Société SOGEREF Sarl        5. Comptes de trésorerie
 *   3. Utilisateurs par métier     6. Jeu de démonstration complet
 *
 * Mot de passe de tous les comptes de démonstration : Sogeref@2026
 */
class DatabaseSeeder extends Seeder
{
    public const PASSWORD = 'Sogeref@2026';

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        foreach ([['USD', 'Dollar américain', '$'], ['CDF', 'Franc congolais', 'FC'], ['EUR', 'Euro', '€']] as [$code, $name, $symbol]) {
            DB::table('currencies')->updateOrInsert(['code' => $code], ['name' => $name, 'symbol' => $symbol, 'precision' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ── 2. Société (coordonnées issues des documents officiels SOGEREF) ──
        $company = Company::create([
            'name' => 'SOGEREF Sarl',
            'legal_name' => 'Société Générale de Réalisation et de Fabrication — SOGEREF Sarl',
            'registration_number' => 'CD/KNM/RCCM/13-B-8079',
            'tax_number' => 'A1913678P',
            'email' => 'contact@sogeref.cd',
            'phone' => '+243 840 500 400',
            'country_code' => 'CD',
            'base_currency' => 'USD',
            'accounting_plan' => 'SYSCOHADA',
            'accent_color' => '#104A63',
            'address' => ['line1' => 'Av. du Port, Concession SCTP / ex ONATRA', 'line2' => 'Commune de la Gombe', 'city' => 'Kinshasa', 'country' => 'République démocratique du Congo'],
            'document_footer' => 'SOGEREF Sarl · RCCM CD-KNM-13-B-8079 · ID.NAT 01-83-N-92036T · N° Impôt A1913678P · Av. du Port, Concession SCTP/ex ONATRA, Gombe — Kinshasa, RDC · +243 840 500 400',
            'settings' => [
                'id_nat' => '01-83-N-92036T',
                'website' => 'www.sogeref.cd',
                'slogan' => 'Sculptons vos rêves, bâtissons la réalité',
                'bank_details' => 'Rawbank — USD 05100-01234567890-12 · Equity BCDC — USD 00018-00123456789-45',
                'templates' => ['quote' => 'moderne', 'invoice' => 'moderne', 'purchase_order' => 'classique', 'report' => 'moderne'],
                'orders' => ['deposit_rate' => 50],
                'payroll' => ['exchange_rate' => 2847],
                'modules_disabled' => [],
                'quote_terms' => "Validité de l’offre : 30 jours.\nAcompte de 50 % à la commande, solde à la livraison.\nDélais de fabrication communiqués à la validation du devis.",
                'invoice_terms' => 'Paiement à réception de facture. Toute facture non réglée à échéance pourra suspendre la livraison.',
            ],
        ]);
        $siege = Branch::create(['company_id' => $company->id, 'code' => 'GOM', 'name' => 'Siège — Gombe', 'phone' => '+243 840 500 400', 'address' => ['city' => 'Kinshasa']]);
        Branch::create(['company_id' => $company->id, 'code' => 'LIM', 'name' => 'Atelier — Limete', 'phone' => '+243 840 500 401', 'address' => ['city' => 'Kinshasa']]);

        // ── 3. Utilisateurs (un compte par métier) ──────────────────────────
        $users = [];
        foreach ([
            ['master', 'Seth Belo', 'Super Admin', 'Administrateur système'],
            ['direction', 'Jean-Pierre Mukendi', 'Direction Générale', 'Directeur Général'],
            ['admin', 'Christelle Ilunga', 'Administrateur', 'Responsable administratif'],
            ['compta', 'Patrick Tshibanda', 'Comptable', 'Chef comptable'],
            ['caisse', 'Grâce Mbuyi', 'Caissier', 'Caissière principale'],
            ['commercial', 'Dorcas Malala', 'Commercial', 'Chargée d’affaires'],
            ['accueil', 'Nadine Kabeya', 'Réceptionniste', 'Réceptionniste'],
            ['atelier', 'Joseph Kalala', "Chef d'atelier", 'Chef d’atelier menuiserie'],
            ['magasin', 'Rémy Lukusa', 'Magasinier', 'Magasinier'],
            ['achats', 'Serge Mutombo', 'Acheteur', 'Responsable achats'],
            ['rh', 'Esther Kanku', 'Responsable RH', 'Responsable RH'],
            ['chantier', 'Didier Ngoy', 'Chef de chantier', 'Conducteur de travaux'],
        ] as [$login, $name, $role, $title]) {
            $user = User::create([
                'name' => $name, 'email' => "{$login}@sogeref.cd", 'password' => self::PASSWORD, 'job_title' => $title,
                'current_company_id' => $company->id, 'current_branch_id' => $siege->id, 'locale' => 'fr', 'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $user->companies()->attach($company->id, ['branch_id' => $siege->id, 'is_active' => true]);
            $user->assignRole($role);
            $users[$login] = $user;
        }

        // Toutes les opérations de démonstration sont attribuées au Master.
        Auth::setUser($users['master']);

        // ── 4. Comptabilité & change ────────────────────────────────────────
        $this->call(OhadaChartSeeder::class);
        foreach ([['CDF', 2847], ['EUR', 0.92]] as [$currency, $rate]) {
            ExchangeRate::create(['company_id' => $company->id, 'base_currency' => 'USD', 'quote_currency' => $currency, 'rate' => $rate, 'effective_date' => now()->startOfYear(), 'created_by' => $users['master']->id]);
        }

        // ── 5. Trésorerie ───────────────────────────────────────────────────
        foreach ([
            ['cash', 'Caisse principale — Siège', 'Siège Gombe', 'CAISSE-01', 4800],
            ['cash', 'Caisse showroom', 'Showroom Gombe', 'CAISSE-02', 650],
            ['bank', 'Rawbank — Compte courant USD', 'Rawbank', '05100-01234567890-12', 86420],
            ['bank', 'Equity BCDC — Compte USD', 'Equity BCDC', '00018-00123456789-45', 41250],
            ['mobile_money', 'M-Pesa Business', 'Vodacom M-Pesa', '+243 840 500 400', 3150],
        ] as [$type, $name, $provider, $number, $opening]) {
            FinancialAccount::create(['company_id' => $company->id, 'branch_id' => $siege->id, 'type' => $type, 'name' => $name, 'provider' => $provider, 'account_number' => $number, 'currency_code' => 'USD', 'opening_balance' => $opening]);
        }

        // ── 6. Jeu de démonstration ─────────────────────────────────────────
        $this->callWith(DemoSeeder::class, ['users' => $users]);

        // L'utilisateur « courant » ne sert qu'à horodater le jeu de démonstration : on l'oublie.
        Auth::forgetUser();
    }
}
