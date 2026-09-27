<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| SOGEREF ERP — Opérations
|--------------------------------------------------------------------------
| Chaîne complète d'une commande client :
|   Devis → Validation → Facture → Encaissement (caisse)
|        → Fabrication (atelier) / Construction (chantier) → Livraison.
| Inclut nomenclatures, ordres de fabrication, projets, chantiers,
| présences journalières et bons (achat, décaissement, sortie matériel).
*/
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Commandes clients & journal de suivi ───────────────────────
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->string('number', 30);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('kind', 20)->default('fabrication');     // vente | fabrication | construction | service
            $table->string('channel', 10)->default('b2b');          // b2b | b2c
            $table->string('stage', 20)->default('devis');
            $table->string('priority', 10)->default('normale');
            $table->foreignId('quote_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->unsignedBigInteger('manufacturing_order_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->decimal('total', 16, 2)->default(0);
            $table->decimal('amount_paid', 16, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->date('delivery_date')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'stage']);
        });

        Schema::create('sales_order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_stage', 20)->nullable();
            $table->string('to_stage', 20)->nullable();
            $table->string('title');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // ── 2. Nomenclatures (BOM) ────────────────────────────────────────
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('yield_quantity', 12, 4)->default(1);
            $table->decimal('labour_hours', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bom_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->timestamps();
        });

        // ── 3. Ordres de fabrication / dossiers de construction ───────────
        Schema::create('manufacturing_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('responsible_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('number', 30);
            $table->string('type', 20)->default('fabrication');     // fabrication | construction
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('specifications')->nullable();              // dimensions, matériaux, finitions…
            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('status', 20)->default('draft');         // draft | confirmed | in_progress | quality | done | cancelled
            $table->string('priority', 10)->default('normale');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->decimal('labour_cost', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('manufacturing_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('unit')->default('unité');
            $table->decimal('quantity_planned', 14, 4)->default(0);
            $table->decimal('quantity_consumed', 14, 4)->default(0);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('manufacturing_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('workstation')->nullable();
            $table->decimal('planned_hours', 8, 2)->default(0);
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->string('status', 20)->default('todo');          // todo | in_progress | done
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // ── 4. Projets, tâches & feuilles de temps ────────────────────────
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');        // draft | active | on_hold | done | cancelled
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 16, 2)->default(0);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('color', 20)->default('#104A63');
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('todo');          // todo | in_progress | review | done
            $table->string('priority', 10)->default('normale');
            $table->date('due_date')->nullable();
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date');
            $table->decimal('hours', 6, 2);
            $table->string('description')->nullable();
            $table->boolean('billable')->default(true);
            $table->string('status', 20)->default('draft');         // draft | submitted | approved
            $table->timestamps();
        });

        // ── 5. Chantiers ──────────────────────────────────────────────────
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('planned');       // planned | in_progress | suspended | completed
            $table->date('start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->decimal('budget', 16, 2)->default(0);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('site_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->decimal('daily_rate', 12, 2)->nullable();       // surcharge du taux de l'employé
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->timestamps();
            $table->unique(['site_id', 'employee_id']);
        });

        Schema::create('site_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->date('assigned_at');
            $table->date('returned_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('site_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('report_date');
            $table->string('weather', 20)->nullable();
            $table->unsignedSmallInteger('workforce')->default(0);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->text('work_done');
            $table->text('issues')->nullable();
            $table->text('next_steps')->nullable();
            $table->json('photos')->nullable();
            $table->timestamps();
        });

        Schema::create('site_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('evaluated_at');
            $table->json('scores');                                   // qualité, délais, sécurité, budget, propreté
            $table->decimal('score', 4, 2);
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        // ── 6. Listes de présence (base de la paie des journaliers) ───────
        Schema::create('attendance_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->string('status', 20)->default('draft');         // draft | validated
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->unique(['site_id', 'work_date']);
        });

        Schema::create('attendance_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('status', 10)->default('present');       // present | half | absent | leave | late
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('daily_rate', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['attendance_sheet_id', 'employee_id']);
        });

        // ── 7. Bons : achat, décaissement, sortie matériel ────────────────
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);                               // achat | decaissement | sortie
            $table->string('number', 30);
            $table->date('voucher_date');
            $table->string('beneficiary')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manufacturing_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('financial_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose');
            $table->decimal('amount', 16, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->string('payment_method', 20)->nullable();
            $table->string('status', 20)->default('draft');         // draft | submitted | approved | rejected | executed | cancelled
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'type', 'status']);
        });

        Schema::create('voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 14, 4)->default(1);
            $table->string('unit')->default('unité');
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('total', 16, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['voucher_lines', 'vouchers', 'attendance_entries', 'attendance_sheets', 'site_evaluations', 'site_reports', 'site_equipment', 'site_members', 'sites', 'timesheets', 'tasks', 'projects', 'manufacturing_operations', 'manufacturing_materials', 'manufacturing_orders', 'bom_lines', 'boms', 'sales_order_events', 'sales_orders'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
