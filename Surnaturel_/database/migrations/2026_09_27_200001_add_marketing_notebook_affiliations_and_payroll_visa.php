<?php

use App\Models\Setting;
use App\Support\DocumentSignatures;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visa DG des fiches de paie, suivi marketing des clients (appels / WhatsApp), cahier de conception,
     * affiliations santé & assurance vie des agents et de leurs familles, cartes d'agents.
     */
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->string('dg_status', 20)->default('pending')->after('lines')->index();
            $table->foreignId('dg_approved_by')->nullable()->after('dg_status')->constrained('users')->nullOnDelete();
            $table->timestamp('dg_approved_at')->nullable()->after('dg_approved_by');
            $table->text('dg_comment')->nullable()->after('dg_approved_at');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->timestamp('last_contacted_at')->nullable()->after('notes');
            $table->timestamp('next_follow_up_at')->nullable()->after('last_contacted_at')->index();
            $table->foreignId('follow_up_owner_id')->nullable()->after('next_follow_up_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('client_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('channel', 20)->index();
            $table->string('direction', 10)->default('outgoing');
            $table->string('outcome', 30)->nullable()->index();
            $table->string('subject')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('design_briefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_job_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('objective')->nullable();
            $table->string('target_audience')->nullable();
            $table->text('content')->nullable();
            $table->text('style_notes')->nullable();
            $table->string('final_format', 40)->nullable();
            $table->decimal('width_mm', 8, 1)->nullable();
            $table->decimal('height_mm', 8, 1)->nullable();
            $table->decimal('bleed_mm', 4, 1)->default(3);
            $table->string('orientation', 10)->default('portrait');
            $table->string('colors', 40)->nullable();
            $table->string('paper')->nullable();
            $table->unsignedSmallInteger('grammage')->nullable();
            $table->string('finishing')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedSmallInteger('pages')->nullable();
            $table->string('final_file_name')->nullable();
            $table->json('checklist')->nullable();
            $table->text('print_notes')->nullable();
            $table->foreignId('validated_for_print_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_for_print_at')->nullable();
            $table->timestamps();
        });

        Schema::create('design_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type', 20)->default('note');
            $table->text('body');
            $table->boolean('is_done')->default(false);
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('blood_group', 5)->nullable()->after('emergency_contact');
            $table->date('card_issued_at')->nullable()->after('badge_code');
            $table->date('card_expires_at')->nullable()->after('card_issued_at');
        });

        Schema::create('employee_dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('relationship', 20);
            $table->string('gender', 1)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_student')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 10)->index();
            $table->string('provider');
            $table->string('policy_number')->nullable();
            $table->text('coverage')->nullable();
            $table->decimal('coverage_limit', 15, 2)->nullable();
            $table->decimal('employer_share', 5, 2)->default(100);
            $table->decimal('monthly_premium', 15, 2)->default(0);
            $table->string('contact_phone', 30)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('affiliations', function (Blueprint $table) {
            $table->id();
            $table->string('member_number', 30)->unique();
            $table->foreignId('insurance_policy_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_dependent_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('beneficiary_relationship')->nullable();
            $table->decimal('beneficiary_share', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['insurance_policy_id', 'employee_id', 'employee_dependent_id'], 'affiliations_member_unique');
        });

        Schema::create('care_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('affiliation_id')->constrained()->cascadeOnDelete();
            $table->string('care_provider');
            $table->string('care_type', 30);
            $table->text('description')->nullable();
            $table->decimal('estimated_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('issued')->index();
            $table->date('issued_on');
            $table->date('valid_until');
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        if (Setting::get('company.name') === 'Surnaturel Imprimerie' || Setting::get('company.name') === null) {
            Setting::put('company.name', 'Surnaturel Print');
            Setting::put('company.legal_name', 'Surnaturel Print SARL');
        }
        Setting::get('company.slogan') ?? Setting::put('company.slogan', 'L’impression qui marque les esprits');
        Setting::get('documents.signatures') ?? Setting::put('documents.signatures', DocumentSignatures::DEFAULTS);
        Setting::get('hr.card_validity_years') ?? Setting::put('hr.card_validity_years', 2);
        Setting::get('marketing.whatsapp_templates') ?? Setting::put('marketing.whatsapp_templates', [
            ['name' => 'Relance devis', 'body' => 'Bonjour {client}, c’est {agent} de {entreprise}. Avez-vous pu consulter notre proposition ? Je reste disponible pour toute question.'],
            ['name' => 'Commande prête', 'body' => 'Bonjour {client}, votre commande est prête chez {entreprise}. Vous pouvez passer la retirer. Merci de votre confiance !'],
            ['name' => 'Promotion', 'body' => 'Bonjour {client} ! Cette semaine chez {entreprise} : offres spéciales sur cartes de visite, flyers et bâches. Contactez-nous pour en profiter.'],
            ['name' => 'Remerciement', 'body' => 'Bonjour {client}, merci pour votre confiance. Votre avis compte : êtes-vous satisfait de nos impressions ?'],
        ]);
    }

    public function down(): void
    {
        foreach (['care_vouchers', 'affiliations', 'insurance_policies', 'employee_dependents', 'design_notes', 'design_briefs', 'client_interactions'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn(['blood_group', 'card_issued_at', 'card_expires_at']));
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('follow_up_owner_id');
            $table->dropColumn(['last_contacted_at', 'next_follow_up_at']);
        });
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dg_approved_by');
            $table->dropColumn(['dg_status', 'dg_approved_at', 'dg_comment']);
        });
    }
};
