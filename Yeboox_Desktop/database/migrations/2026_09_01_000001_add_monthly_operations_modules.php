<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('purchase_date');
            $table->string('supplier');
            $table->string('description');
            $table->string('expense_account', 20)->default('601');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->string('status', 20)->default('draft');
            $table->string('supplier_reference')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('disbursement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('requested_date');
            $table->string('beneficiary');
            $table->string('purpose');
            $table->decimal('amount', 14, 2);
            $table->string('expense_account', 20)->default('658');
            $table->string('treasury_account', 20)->default('5711');
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('equity_movements', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('movement_date');
            $table->string('partner');
            $table->string('type', 20);
            $table->decimal('amount', 14, 2);
            $table->string('equity_account', 20)->default('101');
            $table->string('treasury_account', 20)->default('5211');
            $table->string('description');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
        DB::table('settings')->updateOrInsert(['key' => 'monthly_modules'], ['value' => json_encode(collect(['expenses', 'purchases', 'disbursements', 'equity', 'planning'])->mapWithKeys(fn ($key) => [$key => ['enabled' => true, 'expires_at' => now()->endOfMonth()->toDateString()]])->all()), 'updated_at' => now(), 'created_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('equity_movements');
        Schema::dropIfExists('disbursement_requests');
        Schema::dropIfExists('purchases');
        DB::table('settings')->where('key', 'monthly_modules')->delete();
    }
};
