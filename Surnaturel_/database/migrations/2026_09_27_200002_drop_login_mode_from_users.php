<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Connexion unifiée : identifiant (nom d'utilisateur ou e-mail) + mot de passe pour tous les comptes.
     */
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('login_mode'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('login_mode', 20)->default('password')->after('password'));
    }
};
