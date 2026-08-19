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
        Schema::create('staff_face_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users');
            $table->foreignUuid('masjid_id')->constrained('masjids');
            // Embedding wajah on-device (MobileFaceNet, 192-d) — lihat FaceRecognitionService.
            // Perbandingan kecocokan SELALU di server, tidak pernah dipercayakan ke klien.
            $table->json('mobile_descriptor');
            $table->string('photo_path')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_face_profiles');
    }
};
