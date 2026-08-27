<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpq_level_promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('tpq_students')->cascadeOnDelete();
            $table->enum('from_method', ['iqro', 'quran']);
            $table->unsignedTinyInteger('from_jilid')->nullable();
            $table->enum('to_method', ['iqro', 'quran']);
            $table->unsignedTinyInteger('to_jilid')->nullable();
            $table->foreignUuid('promoted_by')->constrained('users');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpq_level_promotions');
    }
};
