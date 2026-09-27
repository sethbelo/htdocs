<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('register_sessions', function (Blueprint $table): void {
            $table->uuid('sync_uuid')->nullable()->unique();
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->uuid('sync_uuid')->nullable()->unique();
            $table->string('sync_status', 20)->default('pending')->index();
            $table->dateTime('synced_at')->nullable();
        });

        Schema::create('sync_outbox', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('establishment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale')->cascadeOnDelete();
            $table->string('aggregate_type', 50);
            $table->uuid('aggregate_uuid');
            $table->string('event_type', 80);
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('available_at')->nullable();
            $table->dateTime('last_attempt_at')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['status', 'available_at'], 'sync_outbox_ready_idx');
            $table->index(['company_id', 'point_of_sale_id', 'status'], 'sync_outbox_tenant_idx');
        });

        Schema::create('pos_sync_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale')->cascadeOnDelete();
            $table->string('name');
            $table->char('token_hash', 64)->index();
            $table->string('token_hint', 12)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->dateTime('last_used_at')->nullable();
            $table->timestamps();
            $table->unique(['point_of_sale_id', 'name']);
        });

        Schema::create('sync_receipts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->char('payload_checksum', 64);
            $table->json('response')->nullable();
            $table->dateTime('received_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_receipts');
        Schema::dropIfExists('pos_sync_credentials');
        Schema::dropIfExists('sync_outbox');

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique(['sync_uuid']);
            $table->dropIndex(['sync_status']);
            $table->dropColumn(['sync_uuid', 'sync_status', 'synced_at']);
        });
        Schema::table('register_sessions', function (Blueprint $table): void {
            $table->dropUnique(['sync_uuid']);
            $table->dropColumn('sync_uuid');
        });
    }
};
