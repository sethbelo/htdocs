<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Espace personnel : notes riches, tableurs et tâches du jour.
 * Données privées : propriétaire (user_id) au sein d'une société (company_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Sans titre');
            $table->longText('content')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->index(['company_id', 'user_id', 'is_pinned']);
        });

        Schema::create('personal_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Classeur sans titre');
            $table->unsignedSmallInteger('rows')->default(20);
            $table->unsignedSmallInteger('cols')->default(8);
            $table->json('cells')->nullable();   // {"A1": "Libellé", "B2": "=SUM(B1:B1)"}
            $table->json('values')->nullable();  // valeurs calculées à l'enregistrement
            $table->timestamps();
            $table->index(['company_id', 'user_id']);
        });

        Schema::create('personal_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('task_date');
            $table->string('title');
            $table->text('notes')->nullable();
            $table->string('priority', 12)->default('normale');
            $table->string('start_time', 5)->nullable();
            $table->string('end_time', 5)->nullable();
            $table->boolean('is_done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->date('carried_from')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'user_id', 'task_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_tasks');
        Schema::dropIfExists('personal_sheets');
        Schema::dropIfExists('personal_notes');
    }
};
