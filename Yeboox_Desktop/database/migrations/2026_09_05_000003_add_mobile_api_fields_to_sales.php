<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->string('client_reference', 100)->nullable()->unique()->after('id');
            $table->string('source', 20)->default('desktop')->after('client_reference');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique(['client_reference']);
            $table->dropColumn(['client_reference', 'source']);
        });
    }
};
