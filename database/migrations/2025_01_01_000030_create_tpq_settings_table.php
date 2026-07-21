<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpq_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('masjid_id')->unique()->constrained('masjids');
            $table->string('name');
            $table->string('sk_number')->nullable();
            $table->string('head_name');
            $table->string('head_nip')->nullable();
            $table->string('head_signature')->nullable();
            $table->string('logo')->nullable();
            $table->text('address')->nullable();
            $table->json('grade_scale')->nullable();
            $table->integer('min_attendance_percent')->default(75);
            $table->integer('min_avg_grade')->default(70);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpq_settings');
    }
};
