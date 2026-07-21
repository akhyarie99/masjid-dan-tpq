<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpq_grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('tpq_students');
            $table->foreignUuid('class_id')->constrained('tpq_classes');
            $table->foreignUuid('subject_id')->constrained('tpq_subjects');
            $table->foreignUuid('semester_id')->constrained('tpq_semesters');
            $table->decimal('score', 5, 2)->nullable();
            $table->string('grade_letter')->nullable();
            $table->text('description')->nullable();
            $table->foreignUuid('graded_by')->constrained('users');
            $table->timestamps();
            $table->unique(['student_id', 'subject_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpq_grades');
    }
};
