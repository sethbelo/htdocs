<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CRM : clients, pipeline commercial, fidélisation, promotions.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->string('type', 30)->index();
            $table->string('segment', 5)->default('b2c');
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('phone')->nullable()->index();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('tax_id')->nullable();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('allow_credit')->default(false);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('color', 20)->default('zinc');
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamps();
        });

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('prospect_name')->nullable();
            $table->string('prospect_phone')->nullable();
            $table->string('prospect_email')->nullable();
            $table->foreignId('pipeline_stage_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('estimated_amount', 15, 2)->default(0);
            $table->date('expected_close_date')->nullable();
            $table->string('lost_reason')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->text('needs')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('min_points')->default(0);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('points_multiplier', 5, 2)->default(1);
            $table->string('color', 20)->default('zinc');
            $table->timestamps();
        });

        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('card_number', 30)->unique();
            $table->integer('points_balance')->default(0);
            $table->unsignedInteger('lifetime_points')->default(0);
            $table->foreignId('loyalty_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_account_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->integer('points');
            $table->integer('balance_after');
            $table->nullableMorphs('source');
            $table->string('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('points_cost');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->nullable()->unique();
            $table->string('type', 20);
            $table->decimal('value', 15, 2);
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['promotions', 'rewards', 'loyalty_transactions', 'loyalty_accounts', 'loyalty_tiers', 'opportunities', 'pipeline_stages', 'clients'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
