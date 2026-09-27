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
        if (!Schema::hasColumn('students', 'matricule')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('matricule')->nullable()->unique()->after('id');
            });
        }

        Schema::dropIfExists('enrollments');

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('type'); // 'inscription' or 'reinscription'
            $table->string('status')->default('active');
            $table->timestamps();

            // Constraint: one enrollment per academic year
            $table->unique(['student_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('matricule');
        });
    }
};
