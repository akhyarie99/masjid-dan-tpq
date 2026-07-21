<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpq_report_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('tpq_students');
            $table->foreignUuid('class_id')->constrained('tpq_classes');
            $table->foreignUuid('semester_id')->constrained('tpq_semesters');
            $table->decimal('average_score', 5, 2)->nullable();
            $table->string('grade_rank')->nullable();
            $table->integer('present_count')->default(0);
            $table->integer('sick_count')->default(0);
            $table->integer('permission_count')->default(0);
            $table->integer('absent_count')->default(0);
            $table->text('homeroom_notes')->nullable();
            $table->text('head_notes')->nullable();
            $table->enum('promotion_status', ['naik', 'tinggal', 'lulus'])->nullable();
            $table->string('pdf_path')->nullable();
            $table->boolean('is_distributed')->default(false);
            $table->timestamp('distributed_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpq_report_cards');
    }
};
