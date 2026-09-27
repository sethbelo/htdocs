<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $blueprint) {
            if (!Schema::hasColumn('payments', 'status')) {
                $blueprint->string('status')->default('approved'); // 'pending', 'approved', 'rejected'
            }
            if (!Schema::hasColumn('payments', 'beneficiary_name')) {
                $blueprint->string('beneficiary_name')->nullable();
            }
            if (!Schema::hasColumn('payments', 'motif')) {
                $blueprint->string('motif')->nullable(); // 'Achat', 'Transport', 'Salaires', 'Loyer', 'Maintenance', 'Autre'
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['status', 'beneficiary_name', 'motif']);
        });
    }
};
