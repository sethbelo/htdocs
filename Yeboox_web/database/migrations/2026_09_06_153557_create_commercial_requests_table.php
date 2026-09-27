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
        Schema::create('commercial_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('request_type', 30)->index();
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone', 40);
            $table->string('country')->default('RDC');
            $table->string('city')->nullable();
            $table->string('business_type', 60);
            $table->unsignedSmallInteger('locations_count')->default(1);
            $table->string('preferred_contact', 20)->default('whatsapp');
            $table->text('message')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->boolean('consent')->default(false);
            $table->string('source')->default('website');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['email', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_requests');
    }
};
