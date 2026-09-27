<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Compte de charge SYSCOHADA de chaque dépense (601, 622, 624, 638…). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('account_code', 10)->default('605')->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', fn (Blueprint $t) => $t->dropColumn('account_code'));
    }
};
