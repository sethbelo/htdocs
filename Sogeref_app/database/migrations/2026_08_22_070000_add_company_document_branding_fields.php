<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('vat_number')->nullable()->after('tax_number');
            $table->string('seal_path')->nullable()->after('logo_path');
            $table->text('document_footer')->nullable()->after('seal_path');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['vat_number', 'seal_path', 'document_footer']);
        });
    }
};
