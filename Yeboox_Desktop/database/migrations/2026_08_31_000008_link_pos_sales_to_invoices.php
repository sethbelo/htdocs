<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->unique()->after('customer_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        // SQLite doit retirer l'index unique avant de reconstruire la table
        // sans la colonne, sinon le rollback conserve une référence invalide.
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_invoice_id_unique');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });
    }
};
