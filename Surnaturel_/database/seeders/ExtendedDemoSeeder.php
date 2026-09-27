<?php

namespace Database\Seeders;

use App\Enums\InsuranceType;
use App\Enums\PayrollPeriodStatus;
use App\Models\Client;
use App\Models\Employee;
use App\Models\InsurancePolicy;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Hr\AffiliationService;
use App\Services\Hr\PayrollService;
use App\Services\Marketing\FollowUpService;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration des modules marketing, santé & assurance et cartes (idempotent).
 */
class ExtendedDemoSeeder extends Seeder
{
    public function run(FollowUpService $followUps, AffiliationService $affiliations, PayrollService $payroll): void
    {
        $period = PayrollPeriod::query()->firstOrCreate(['code' => now()->format('Y-m')], ['starts_on' => now()->startOfMonth(), 'ends_on' => now()->endOfMonth(), 'status' => PayrollPeriodStatus::Draft]);
        if ($period->payslips()->doesntExist()) {
            $payroll->compute($period);
        }

        $employees = Employee::query()->orderBy('id')->get();
        $employees->each(fn (Employee $e, int $i) => $e->forceFill([
            'blood_group' => $e->blood_group ?? ['O+', 'A+', 'B+', 'O-', 'AB+'][$i % 5],
            'emergency_contact' => $e->emergency_contact ?? 'Conjoint(e) — +243 81'.random_int(1000000, 9999999),
            'email' => $e->email ?? strtolower(str_replace(' ', '', "{$e->first_name}.{$e->last_name}")).'@surnaturel.cd',
            'badge_code' => $e->badge_code ?? 'BDG-'.str_pad((string) $e->id, 5, '0', STR_PAD_LEFT),
        ])->save());

        $medical = InsurancePolicy::query()->firstOrCreate(['name' => 'Santé Famille Plus'], [
            'type' => InsuranceType::Medical, 'provider' => 'SONAS — Assurance maladie', 'policy_number' => 'SAN-2026-0042',
            'coverage' => "Consultations, hospitalisation, maternité, pharmacie et examens de laboratoire.\nRéseau de cliniques conventionnées de Kinshasa.",
            'coverage_limit' => 5000, 'employer_share' => 80, 'monthly_premium' => 18, 'contact_phone' => '+243 815 000 111', 'starts_on' => now()->startOfYear(), 'is_active' => true,
        ]);
        $life = InsurancePolicy::query()->firstOrCreate(['name' => 'Prévoyance Vie Collective'], [
            'type' => InsuranceType::Life, 'provider' => 'Rawsur Assurances', 'policy_number' => 'VIE-2026-0007',
            'coverage' => 'Capital décès et invalidité absolue : 24 mois de salaire versés au bénéficiaire désigné.',
            'coverage_limit' => 20000, 'employer_share' => 100, 'monthly_premium' => 6, 'contact_phone' => '+243 820 000 222', 'starts_on' => now()->startOfYear(), 'is_active' => true,
        ]);

        $hr = User::role('responsable-rh')->first() ?? User::query()->first();
        foreach ($employees->take(4) as $i => $employee) {
            if ($employee->dependents()->doesntExist()) {
                $employee->dependents()->create(['first_name' => fake()->firstName(), 'last_name' => $employee->last_name, 'relationship' => 'spouse', 'gender' => $i % 2 ? 'M' : 'F', 'birth_date' => now()->subYears(30 + $i)]);
                $employee->dependents()->create(['first_name' => fake()->firstName(), 'last_name' => $employee->last_name, 'relationship' => 'child', 'birth_date' => now()->subYears(4 + $i)]);
            }

            if ($employee->affiliations()->doesntExist()) {
                $affiliations->affiliate($medical, $employee, null, $hr);
                foreach ($employee->dependents as $dependent) {
                    $affiliations->affiliate($medical, $employee, $dependent, $hr);
                }
                $spouse = $employee->dependents->firstWhere('relationship.value', 'spouse');
                $affiliations->affiliate($life, $employee, null, $hr, ['beneficiary_name' => $spouse?->fullName(), 'beneficiary_relationship' => 'Conjoint(e)', 'beneficiary_share' => 100]);
            }
        }

        $firstMedical = $medical->affiliations()->with('policy')->first();
        if ($firstMedical && $firstMedical->careVouchers()->doesntExist()) {
            $affiliations->issueCareVoucher($firstMedical, $hr, ['care_provider' => 'Centre Hospitalier Monkole', 'care_type' => 'consultation', 'description' => 'Consultation de médecine générale', 'estimated_amount' => 35]);
        }

        $agent = User::role('agent-marketing')->first() ?? User::query()->first();
        $samples = [
            ['call', 'interested', 'Relance devis cartes de visite', 'Le client souhaite 500 cartes pelliculées mat, rappeler pour le BAT.', 2],
            ['whatsapp', 'reached', 'Promotion flyers', 'Offre spéciale flyers A5 envoyée par WhatsApp.', null],
            ['call', 'no_answer', 'Suivi commande', 'Pas de réponse, relancer demain matin.', 1],
            ['visit', 'converted', 'Rendez-vous agence', 'Commande de 2 bâches 3×1 m confirmée.', null],
            ['call', 'callback', 'Renouvellement calendriers', 'Rappeler après validation du budget.', 0],
        ];
        foreach (Client::query()->orderBy('id')->take(count($samples))->get() as $i => $client) {
            if ($client->interactions()->doesntExist()) {
                [$channel, $outcome, $subject, $notes, $days] = $samples[$i];
                $followUps->record($client, $agent, ['channel' => $channel, 'outcome' => $outcome, 'subject' => $subject, 'notes' => $notes, 'duration_minutes' => $channel === 'call' ? 4 : null,
                    'next_follow_up_at' => $days === null ? null : now()->addDays($days)->setTime(10 + $i, 0)->max(now()->addHour())->toDateTimeString()]);
            }
        }
    }
}
