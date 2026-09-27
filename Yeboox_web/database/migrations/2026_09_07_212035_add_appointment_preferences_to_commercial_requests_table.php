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
        Schema::table('commercial_requests', function (Blueprint $table) {
            $table->date('appointment_date')->nullable()->after('preferred_contact');
            $table->time('appointment_time')->nullable()->after('appointment_date');
            $table->string('timezone', 80)->nullable()->after('appointment_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commercial_requests', function (Blueprint $table) {
            $table->dropColumn(['appointment_date', 'appointment_time', 'timezone']);
        });
    }
};
