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
        Schema::create('class_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->date('date');
            $table->string('start_time'); // e.g. "08:00"
            $table->string('end_time');   // e.g. "10:00"
            $table->string('title');      // Lesson topic
            $table->text('context')->nullable(); // Program details / context
            $table->timestamps();
        });

        Schema::create('course_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_journal_id')->constrained('class_journals')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->string('title');
            $table->text('content')->nullable(); // Preparation note body
            $table->boolean('is_published')->default(false); // If true, visible to admins; otherwise private to teacher
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_notes');
        Schema::dropIfExists('class_journals');
    }
};
