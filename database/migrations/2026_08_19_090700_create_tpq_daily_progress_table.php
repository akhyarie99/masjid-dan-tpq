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
        Schema::create('tpq_daily_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('tpq_students');
            $table->foreignUuid('class_id')->nullable()->constrained('tpq_classes');
            $table->date('date');
            $table->enum('method', ['iqro', 'quran']);
            // Iqro
            $table->unsignedTinyInteger('jilid')->nullable();
            $table->unsignedSmallInteger('halaman')->nullable();
            // Al-Quran
            $table->string('surah')->nullable();
            $table->unsignedSmallInteger('ayat_awal')->nullable();
            $table->unsignedSmallInteger('ayat_akhir')->nullable();
            $table->enum('keterangan', ['lancar', 'ulang'])->default('lancar');
            $table->text('catatan')->nullable();
            $table->foreignUuid('recorded_by')->constrained('users');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpq_daily_progress');
    }
};
