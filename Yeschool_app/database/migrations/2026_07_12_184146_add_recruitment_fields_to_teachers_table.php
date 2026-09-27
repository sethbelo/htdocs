<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->date('contract_signed_at')->nullable()->after('status');
            $table->date('contract_ended_at')->nullable()->after('contract_signed_at');
            $table->text('recruitment_notes')->nullable()->after('contract_ended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['contract_signed_at', 'contract_ended_at', 'recruitment_notes']);
        });
    }
};
