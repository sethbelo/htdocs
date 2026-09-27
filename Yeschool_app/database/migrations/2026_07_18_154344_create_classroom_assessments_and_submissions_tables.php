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
        Schema::create('classroom_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->string('title');
            $table->string('type'); // Devoir, Travail, Examen
            $table->decimal('max_score', 8, 2)->default(20.00);
            $table->dateTime('due_date')->nullable();
            $table->text('instructions')->nullable();
            $table->json('questions')->nullable(); // [{"id":"q1", "type":"mcq", "question":"...", "options":["..."], "correct_option":"...", "image_url":"..."}, ...]
            $table->timestamps();
        });

        Schema::create('classroom_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_assessment_id')->constrained('classroom_assessments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('status')->default('Non remis'); // Non remis, À corriger, Corrigé
            $table->json('answers')->nullable(); // [{"question_id":"q1", "student_answer":"..."}]
            $table->decimal('score', 8, 2)->nullable();
            $table->text('teacher_comments')->nullable();
            $table->text('parent_signature')->nullable(); // Base64 or digital signature content
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom_submissions');
        Schema::dropIfExists('classroom_assessments');
    }
};
