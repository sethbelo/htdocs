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
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('civil_status')->nullable();
            $table->string('spouse_name')->nullable();
            $table->integer('children_count')->default(0);
            $table->text('children_names')->nullable();
            $table->string('academic_qualification')->nullable();
            $table->string('job_type')->nullable();
            $table->string('folder_number')->nullable();
            $table->string('manager_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn([
                'civil_status',
                'spouse_name',
                'children_count',
                'children_names',
                'academic_qualification',
                'job_type',
                'folder_number',
                'manager_name',
            ]);
        });
    }
};
