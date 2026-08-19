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
        Schema::create('tpq_guardian_push_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Wali login-nya berbasis nomor HP saja (lihat WaliController), bukan model
            // User/Notifiable terpisah — jadi subscription push disimpan per nomor, bukan per santri,
            // supaya satu wali dengan beberapa anak cukup subscribe sekali.
            $table->string('guardian_phone');
            $table->string('endpoint', 500);
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding')->nullable();
            $table->timestamps();
            $table->index('guardian_phone');
            $table->unique(['guardian_phone', 'endpoint'], 'guardian_endpoint_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpq_guardian_push_subscriptions');
    }
};
