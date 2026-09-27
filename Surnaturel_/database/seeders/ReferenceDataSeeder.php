<?php

namespace Database\Seeders;

use App\Enums\SiteType;
use App\Enums\TreasuryAccountType;
use App\Models\Account;
use App\Models\ApprovalWorkflow;
use App\Models\CashRegister;
use App\Models\Department;
use App\Models\Journal;
use App\Models\LeaveType;
use App\Models\LoyaltyTier;
use App\Models\PipelineStage;
use App\Models\PrintRoom;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\Site;
use App\Models\TreasuryAccount;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Structure de base : entreprise, sièges, entrepôts, caisses, trésorerie, référentiels métier.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'company.name' => 'Surnaturel Imprimerie',
            'company.legal_name' => 'Surnaturel Imprimerie SARL',
            'company.rccm' => 'CD/KIN/RCCM/00-B-00000',
            'company.id_nat' => '01-000-N00000X',
            'company.nif' => 'A0000000X',
            'company.address' => 'Avenue du Commerce, Gombe',
            'company.city' => 'Kinshasa',
            'company.country' => 'République Démocratique du Congo',
            'company.phone' => '+243 000 000 000',
            'company.email' => 'contact@example.com',
            'company.website' => null,
            'company.socials' => ['facebook' => null, 'instagram' => null, 'whatsapp' => null],
            'company.currency' => 'USD',
            'company.currency_decimals' => 2,
            'company.default_tax_rate' => 16,
            'company.ticket_footer' => 'Merci de votre confiance !',
            'loyalty.enabled' => true,
            'loyalty.amount_per_point' => 1,
            'loyalty.point_value' => 0.05,
            'hr.work_start' => '08:00',
            'hr.late_tolerance_minutes' => 10,
            'hr.daily_hours' => 8,
            'hr.break_minutes' => 60,
            'hr.working_days_per_month' => 26,
            'hr.overtime_rate' => 1.3,
            'inventory.allow_negative_stock' => false,
            'orders.default_deposit_rate' => 50,
        ] as $key => $value) {
            Setting::put($key, $value);
        }

        $headquarters = Site::query()->updateOrCreate(['code' => 'SIE-KIN'], [
            'name' => 'Siège Kinshasa', 'type' => SiteType::Headquarters, 'city' => 'Kinshasa',
            'address' => 'Avenue du Commerce, Gombe', 'latitude' => -4.3036, 'longitude' => 15.3050,
        ]);
        $shop = Site::query()->updateOrCreate(['code' => 'BTQ-LIM'], [
            'name' => 'Boutique Limete', 'type' => SiteType::Shop, 'parent_id' => $headquarters->id, 'city' => 'Kinshasa',
            'address' => 'Boulevard Lumumba, Limete', 'latitude' => -4.3700, 'longitude' => 15.3440,
        ]);
        $pointOfSale = Site::query()->updateOrCreate(['code' => 'PDV-NGA'], [
            'name' => 'Point de vente Ngaliema', 'type' => SiteType::PointOfSale, 'parent_id' => $headquarters->id, 'city' => 'Kinshasa',
            'address' => 'Avenue de la Libération, Ngaliema', 'latitude' => -4.3450, 'longitude' => 15.2530,
        ]);

        $mainWarehouse = Warehouse::query()->updateOrCreate(['code' => 'ENT-CEN'], [
            'name' => 'Entrepôt central', 'site_id' => $headquarters->id, 'city' => 'Kinshasa', 'latitude' => -4.3200, 'longitude' => 15.2900,
        ]);
        $shopWarehouse = Warehouse::query()->updateOrCreate(['code' => 'ENT-LIM'], [
            'name' => 'Réserve boutique Limete', 'site_id' => $shop->id, 'city' => 'Kinshasa', 'latitude' => -4.3705, 'longitude' => 15.3445,
        ]);

        $headquarters->update(['main_warehouse_id' => $mainWarehouse->id]);
        $shop->update(['main_warehouse_id' => $shopWarehouse->id]);
        $pointOfSale->update(['main_warehouse_id' => $mainWarehouse->id]);

        PrintRoom::query()->updateOrCreate(['code' => 'SAL-01'], ['name' => 'Salle d’impression principale', 'site_id' => $headquarters->id]);

        $journal = fn (string $code): ?int => Journal::query()->where('code', $code)->value('id');
        $account = fn (string $code): ?int => Account::query()->where('code', $code)->value('id');

        $cashHq = TreasuryAccount::query()->updateOrCreate(['code' => 'CAI-SIE'], ['name' => 'Caisse siège', 'type' => TreasuryAccountType::Cash, 'site_id' => $headquarters->id, 'account_id' => $account('571'), 'journal_id' => $journal('CAI'), 'currency' => 'USD']);
        $cashShop = TreasuryAccount::query()->updateOrCreate(['code' => 'CAI-LIM'], ['name' => 'Caisse boutique Limete', 'type' => TreasuryAccountType::Cash, 'site_id' => $shop->id, 'account_id' => $account('5711'), 'journal_id' => $journal('CAI'), 'currency' => 'USD']);
        TreasuryAccount::query()->updateOrCreate(['code' => 'BQ-01'], ['name' => 'Banque principale', 'type' => TreasuryAccountType::Bank, 'account_id' => $account('521'), 'journal_id' => $journal('BQ'), 'bank_name' => 'Banque partenaire', 'currency' => 'USD']);
        TreasuryAccount::query()->updateOrCreate(['code' => 'MM-01'], ['name' => 'Mobile Money', 'type' => TreasuryAccountType::MobileMoney, 'account_id' => $account('5211'), 'journal_id' => $journal('MM'), 'currency' => 'USD']);

        CashRegister::query()->updateOrCreate(['code' => 'CS-SIE-01'], ['name' => 'Caisse commandes siège', 'site_id' => $headquarters->id, 'warehouse_id' => $mainWarehouse->id, 'treasury_account_id' => $cashHq->id, 'max_disbursement_without_approval' => 50]);
        CashRegister::query()->updateOrCreate(['code' => 'CS-LIM-01'], ['name' => 'Caisse boutique 1', 'site_id' => $shop->id, 'warehouse_id' => $shopWarehouse->id, 'treasury_account_id' => $cashShop->id, 'max_disbursement_without_approval' => 20]);
        CashRegister::query()->updateOrCreate(['code' => 'CS-LIM-02'], ['name' => 'Caisse boutique 2', 'site_id' => $shop->id, 'warehouse_id' => $shopWarehouse->id, 'treasury_account_id' => $cashShop->id, 'max_disbursement_without_approval' => 20]);

        foreach ([['Prospect', 'zinc'], ['Contacté', 'sky'], ['Besoin identifié', 'indigo'], ['Devis', 'purple'], ['Négociation', 'amber'], ['Gagné', 'emerald', true], ['Perdu', 'red', false, true]] as $position => $stage) {
            PipelineStage::query()->updateOrCreate(['name' => $stage[0]], ['position' => $position, 'color' => $stage[1], 'is_won' => $stage[2] ?? false, 'is_lost' => $stage[3] ?? false]);
        }

        foreach ([['Bronze', 0, 0, 1, 'amber'], ['Argent', 500, 2, 1.25, 'zinc'], ['Or', 2000, 5, 1.5, 'yellow'], ['Platine', 5000, 8, 2, 'indigo']] as [$name, $min, $discount, $multiplier, $color]) {
            LoyaltyTier::query()->updateOrCreate(['name' => $name], ['min_points' => $min, 'discount_rate' => $discount, 'points_multiplier' => $multiplier, 'color' => $color]);
        }

        foreach ([['Pièce', 'pc'], ['Rame', 'rame'], ['Feuille', 'f'], ['Litre', 'L'], ['Cartouche', 'cart'], ['Mètre', 'm'], ['Mètre carré', 'm²'], ['Kilogramme', 'kg'], ['Boîte', 'bte']] as [$name, $symbol]) {
            Unit::query()->updateOrCreate(['symbol' => $symbol], ['name' => $name]);
        }

        foreach (['Impression numérique', 'Impression offset', 'Grand format', 'Papeterie', 'Papiers & supports', 'Encres & toners', 'Fournitures bureau', 'Accessoires', 'Finition'] as $position => $name) {
            ProductCategory::query()->updateOrCreate(['name' => $name], ['position' => $position]);
        }

        foreach ([['ADM', 'Administration'], ['COM', 'Commercial'], ['GRA', 'Graphisme'], ['IMP', 'Impression'], ['LOG', 'Logistique & entrepôt'], ['BTQ', 'Boutique'], ['FIN', 'Finance & comptabilité'], ['RH', 'Ressources humaines']] as [$code, $name]) {
            Department::query()->updateOrCreate(['code' => $code], ['name' => $name, 'site_id' => $headquarters->id]);
        }

        foreach ([['CA', 'Congé annuel', 26, true, false], ['MAL', 'Congé maladie', 30, true, true], ['MAT', 'Congé maternité', 98, true, true], ['CE', 'Circonstances exceptionnelles', 5, true, true], ['CSS', 'Congé sans solde', 0, false, false]] as [$code, $name, $quota, $paid, $document]) {
            LeaveType::query()->updateOrCreate(['code' => $code], ['name' => $name, 'annual_quota' => $quota, 'is_paid' => $paid, 'requires_document' => $document]);
        }

        $workflows = [
            'purchase_request' => ['Demandes d’achat', [
                ['Responsable de service', 'responsable-administratif', 0],
                ['Responsable financier', 'responsable-financier', 500],
                ['Directeur Général', 'directeur-general', 2000],
            ]],
            'expense_claim' => ['Notes de frais', [
                ['Responsable', 'responsable-administratif', 0],
                ['Finance', 'responsable-financier', 0],
            ]],
            'disbursement' => ['Bons de décaissement', [
                ['Responsable financier', 'responsable-financier', 0],
                ['Directeur Général', 'directeur-general', 1000],
            ]],
            'voucher' => ['Autres bons', [
                ['Responsable administratif', 'responsable-administratif', 0],
            ]],
        ];

        foreach ($workflows as $code => [$name, $steps]) {
            $workflow = ApprovalWorkflow::query()->updateOrCreate(['code' => $code], ['name' => $name]);
            foreach ($steps as $index => [$stepName, $role, $minAmount]) {
                $workflow->steps()->updateOrCreate(['sequence' => $index + 1], ['name' => $stepName, 'approver_role' => $role, 'min_amount' => $minAmount]);
            }
        }
    }
}
