<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpq_hafalan_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('tpq_students');
            $table->integer('surah_number');
            $table->string('surah_name');
            $table->integer('total_ayah');
            $table->integer('memorized_ayah')->default(0);
            $table->enum('status', ['belum', 'sedang', 'hafal'])->default('belum');
            $table->date('memorized_date')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->unique(['student_id', 'surah_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpq_hafalan_progress');
    }
};
