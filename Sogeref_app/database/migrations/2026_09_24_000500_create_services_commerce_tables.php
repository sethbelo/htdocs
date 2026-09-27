<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| SOGEREF ERP — Services, réception & commerce
|--------------------------------------------------------------------------
| Rendez-vous, planification, accueil des visiteurs, registre du courrier,
| programmes de fidélité, location de matériel, point de vente (sessions
| de caisse, tickets) et notes de frais.
*/
return new class extends Migration
{
    public function up(): void
    {
        // ── Services : rendez-vous & planification ────────────────────────
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // interlocuteur SOGEREF
            $table->string('title');
            $table->string('type', 20)->default('reunion');         // reunion | visite_chantier | showroom | mesure | livraison
            $table->string('visitor_name')->nullable();
            $table->string('visitor_phone')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('location')->nullable();
            $table->string('status', 20)->default('scheduled');     // scheduled | confirmed | done | cancelled | no_show
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'starts_at']);
        });

        Schema::create('planning_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manufacturing_order_id')->nullable()->constrained()->nullOnDelete();
            $table->date('slot_date');
            $table->string('shift', 10)->default('journee');        // matin | apres_midi | journee
            $table->string('role')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'slot_date']);
        });

        // ── Réception : visiteurs & courrier ──────────────────────────────
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('host_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('organization')->nullable();
            $table->string('phone')->nullable();
            $table->string('id_document')->nullable();
            $table->string('purpose');
            $table->string('badge_number', 20)->nullable();
            $table->dateTime('checked_in_at');
            $table->dateTime('checked_out_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'checked_in_at']);
        });

        Schema::create('mail_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('direction', 5);                          // in | out
            $table->string('reference', 30);
            $table->date('record_date');
            $table->string('correspondent');
            $table->string('subject');
            $table->string('channel', 20)->default('physique');     // physique | email | coursier
            $table->string('status', 20)->default('registered');    // registered | forwarded | processed | archived
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        // ── CRM : fidélité ────────────────────────────────────────────────
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('segment', 10)->default('all');          // all | b2b | b2c
            $table->decimal('points_per_unit', 8, 4)->default(1);   // points gagnés par 1 USD
            $table->decimal('point_value', 8, 4)->default(0.05);    // valeur d'un point en USD
            $table->unsignedInteger('min_redeem_points')->default(100);
            $table->json('tiers')->nullable();                        // [{name, threshold, discount}]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('loyalty_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loyalty_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('card_number', 30);
            $table->integer('points')->default(0);
            $table->decimal('total_spent', 16, 2)->default(0);
            $table->string('tier')->nullable();
            $table->date('issued_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'card_number']);
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_card_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);                               // earn | redeem | adjust
            $table->integer('points');
            $table->nullableMorphs('source');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // ── Location de matériel ──────────────────────────────────────────
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('number', 30);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('returned_at')->nullable();
            $table->string('status', 20)->default('reserved');      // reserved | ongoing | returned | invoiced | cancelled
            $table->decimal('deposit', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('rental_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->restrictOnDelete();
            $table->decimal('daily_price', 12, 2);
            $table->unsignedSmallInteger('days');
            $table->decimal('total', 14, 2);
            $table->string('return_condition')->nullable();
            $table->timestamps();
        });

        // ── Caisse & point de vente ───────────────────────────────────────
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_account_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 30);
            $table->string('register', 20)->default('caisse');      // caisse | pos
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('opening_amount', 14, 2)->default(0);
            $table->decimal('expected_amount', 14, 2)->default(0);
            $table->decimal('closing_amount', 14, 2)->nullable();
            $table->string('status', 10)->default('open');          // open | closed
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('tendered', 14, 2)->default(0);
            $table->decimal('change_due', 14, 2)->default(0);
            $table->string('payment_method', 20)->default('cash');
            $table->integer('points_earned')->default(0);
            $table->integer('points_redeemed')->default(0);
            $table->string('status', 10)->default('paid');          // paid | refunded
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('pos_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('discount_rate', 6, 2)->default(0);
            $table->decimal('tax_rate', 6, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });

        // ── Notes de frais ────────────────────────────────────────────────
        Schema::create('expense_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->string('title');
            $table->decimal('total', 14, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->string('status', 20)->default('draft');         // draft | submitted | approved | rejected | paid
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('expense_claim_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_claim_id')->constrained()->cascadeOnDelete();
            $table->date('spent_at');
            $table->string('category', 30);
            $table->string('description');
            $table->decimal('amount', 14, 2);
            $table->string('receipt_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['expense_claim_lines', 'expense_claims', 'pos_order_lines', 'pos_orders', 'cash_sessions', 'rental_lines', 'rentals', 'loyalty_transactions', 'loyalty_cards', 'loyalty_programs', 'mail_records', 'visitors', 'planning_slots', 'appointments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
