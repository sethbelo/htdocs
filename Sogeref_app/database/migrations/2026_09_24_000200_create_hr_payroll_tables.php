<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| SOGEREF ERP — Ressources humaines & Paie
|--------------------------------------------------------------------------
| Départements, postes, employés (permanents et journaliers), congés,
| avances sur salaire, périodes de paie et bulletins.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('color', 20)->default('#104A63');
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('job_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('matricule', 30);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender', 1)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('national_id')->nullable();
            $table->string('cnss_number')->nullable();
            $table->unsignedTinyInteger('dependents')->default(0);
            $table->string('category', 20)->default('permanent');   // permanent | contractuel | journalier | stagiaire
            $table->string('pay_type', 10)->default('monthly');     // monthly | daily
            $table->decimal('base_salary', 14, 2)->default(0);
            $table->decimal('daily_rate', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->string('payment_method', 20)->default('cash');
            $table->string('bank_account')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('leave_balance', 6, 2)->default(0);
            $table->string('status', 20)->default('active');        // active | suspended | left
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'matricule']);
            $table->index(['company_id', 'category', 'status']);
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->unsignedSmallInteger('days_per_year')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('deducts_balance')->default(true);
            $table->string('color', 20)->default('#3ACECE');
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->string('reference', 30);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 6, 2);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');       // pending | approved | rejected | cancelled
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_comment')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('granted_at');
            $table->string('status', 20)->default('open');          // open | deducted | cancelled
            $table->unsignedBigInteger('payslip_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 30);
            $table->string('name');
            $table->string('pay_type', 10);                         // monthly | daily
            $table->unsignedBigInteger('site_id')->nullable();      // paie des journaliers par chantier
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('exchange_rate', 14, 4)->default(1);    // CDF pour 1 USD (barème IPR)
            $table->string('status', 20)->default('draft');         // draft | computed | validated | paid
            $table->decimal('total_gross', 16, 2)->default(0);
            $table->decimal('total_net', 16, 2)->default(0);
            $table->decimal('total_employer', 16, 2)->default(0);
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 30);
            $table->decimal('days_worked', 6, 2)->default(0);
            $table->decimal('overtime_hours', 6, 2)->default(0);
            $table->decimal('gross', 14, 2)->default(0);
            $table->decimal('taxable', 14, 2)->default(0);
            $table->decimal('cnss_employee', 14, 2)->default(0);
            $table->decimal('ipr', 14, 2)->default(0);
            $table->decimal('other_deductions', 14, 2)->default(0);
            $table->decimal('net', 14, 2)->default(0);
            $table->decimal('employer_charges', 14, 2)->default(0);
            $table->json('lines')->nullable();
            $table->string('status', 20)->default('draft');         // draft | validated | paid
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        foreach (['payslips', 'payroll_periods', 'salary_advances', 'leave_requests', 'leave_types', 'employees', 'job_positions', 'departments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
