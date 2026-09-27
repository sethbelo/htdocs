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
        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreignId('head_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('teacher_name');
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
