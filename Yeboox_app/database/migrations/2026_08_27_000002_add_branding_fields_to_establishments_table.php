<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('establishments', function (Blueprint $table) {
            $table->string('slogan')->nullable()->after('name');
            $table->string('import_id')->nullable()->after('id_nat');
            $table->string('vat_number')->nullable()->after('nif');
            $table->text('document_footer')->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('establishments', function (Blueprint $table) {
            $table->dropColumn(['slogan', 'import_id', 'vat_number', 'document_footer']);
        });
    }
};
