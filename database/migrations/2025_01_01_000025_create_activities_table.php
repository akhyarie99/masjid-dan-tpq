<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('masjid_id')->constrained('masjids');
            $table->foreignUuid('user_id')->constrained('users');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['kajian_rutin', 'pengajian_akbar', 'sosial', 'phbi', 'rapat', 'lainnya']);
            $table->string('location');
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->string('pic_name')->nullable();
            $table->string('pic_phone')->nullable();
            $table->integer('quota')->nullable();
            $table->enum('status', ['draft', 'published', 'ongoing', 'done', 'cancelled'])->default('draft');
            $table->string('registration_link')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->string('streaming_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
