<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| SOGEREF ERP — Mise à niveau du socle
|--------------------------------------------------------------------------
| Enrichit les tables historiques (contacts, articles, documents, users)
| et ajoute le compteur de numérotation centralisé.
*/
return new class extends Migration
{
    public function up(): void
    {
        // ── Contacts : segmentation B2B / B2C et remise négociée ───────────
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('segment', 10)->default('b2b')->after('type');
            $table->decimal('discount_rate', 8, 4)->default(0)->after('credit_limit');
            $table->string('city')->nullable()->after('phone');
        });

        // ── Articles : matières, produits finis, stock et réappro ─────────
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('sku');
            $table->boolean('is_sellable')->default(true)->after('track_stock');
            $table->boolean('is_purchasable')->default(true)->after('is_sellable');
            $table->boolean('is_manufactured')->default(false)->after('is_purchasable');
            $table->boolean('pos_visible')->default(false)->after('is_manufactured');
            $table->foreignId('default_supplier_id')->nullable()->after('category_id')->constrained('contacts')->nullOnDelete();
            $table->decimal('reorder_quantity', 18, 4)->default(0)->after('reorder_level');
            $table->unsignedSmallInteger('lead_time_days')->default(7)->after('reorder_quantity');
        });

        // ── Lignes de documents : suivi des réceptions fournisseurs ───────
        Schema::table('document_items', function (Blueprint $table) {
            $table->decimal('received_quantity', 18, 4)->default(0)->after('quantity');
        });

        // ── Utilisateurs : statut, poste, dernière connexion ──────────────
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('theme');
            $table->string('job_title')->nullable()->after('is_active');
            $table->string('avatar_path')->nullable()->after('job_title');
            $table->timestamp('last_login_at')->nullable()->after('avatar_path');
        });

        // ── Numérotation atomique de toutes les pièces ────────────────────
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('key', 40);
            $table->string('prefix', 20);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('next_value')->default(1);
            $table->timestamps();
            $table->unique(['company_id', 'key', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['is_active', 'job_title', 'avatar_path', 'last_login_at']));
        Schema::table('document_items', fn (Blueprint $t) => $t->dropColumn('received_quantity'));
        Schema::table('products', function (Blueprint $t) {
            $t->dropConstrainedForeignId('default_supplier_id');
            $t->dropColumn(['barcode', 'is_sellable', 'is_purchasable', 'is_manufactured', 'pos_visible', 'reorder_quantity', 'lead_time_days']);
        });
        Schema::table('contacts', fn (Blueprint $t) => $t->dropColumn(['segment', 'discount_rate', 'city']));
    }
};
