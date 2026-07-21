<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpq_class_teachers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('class_id')->constrained('tpq_classes');
            $table->foreignUuid('academic_year_id')->constrained('tpq_academic_years');
            $table->foreignUuid('teacher_id')->constrained('users');
            $table->boolean('is_homeroom')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpq_class_teachers');
    }
};
