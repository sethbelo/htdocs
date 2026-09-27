<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->uuid('branch_id')->nullable();
            $table->uuid('table_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->string('guest_name');
            $table->string('guest_phone')->nullable();
            $table->string('guest_email')->nullable();
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->integer('duration_minutes')->default(120);
            $table->integer('party_size')->default(2);
            $table->string('status')->default('pending'); // pending, confirmed, seated, completed, cancelled, no_show
            $table->text('special_requests')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('confirmed_by')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('table_id')->references('id')->on('tables')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->index(['establishment_id', 'reservation_date', 'status']);
            $table->index(['table_id', 'reservation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
