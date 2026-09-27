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
        Schema::create('hr_conflicts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_involved_id')->nullable();
            $table->unsignedBigInteger('reporter_id');
            $table->string('title');
            $table->text('description');
            $table->date('date_reported');
            $table->string('severity')->default('Moyenne'); // 'Faible', 'Moyenne', 'Critique'
            $table->string('status')->default('En cours'); // 'En cours', 'Résolu', 'En attente', 'Escalé'
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_involved_id')->references('id')->on('teachers')->onDelete('set null');
            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_conflicts');
    }
};
