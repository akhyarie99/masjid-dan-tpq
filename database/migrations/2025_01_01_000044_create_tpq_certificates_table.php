<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpq_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('tpq_students');
            $table->enum('type', ['khatam_iqra', 'khatam_quran', 'tahfidz', 'ijazah']);
            $table->string('certificate_number')->unique();
            $table->date('issued_date');
            $table->string('achievement')->nullable();
            $table->string('pdf_path')->nullable();
            $table->foreignUuid('issued_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpq_certificates');
    }
};
