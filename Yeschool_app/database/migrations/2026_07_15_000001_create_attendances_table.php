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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->date('date');
            $table->string('status'); // 'present', 'absent', 'late'
            $table->string('reason')->nullable(); // Motif de l'absence / retard
            $table->boolean('is_justified')->default(false); // Si justifié par le parent
            $table->boolean('whatsapp_sent')->default(false); // Alerte WhatsApp envoyée ou non
            $table->text('whatsapp_log')->nullable(); // Infos techniques / logs d'envoi
            $table->timestamps();

            // Unicité : un élève n'a qu'un statut de présence par jour
            $table->unique(['student_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
