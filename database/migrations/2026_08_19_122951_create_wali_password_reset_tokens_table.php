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
        Schema::create('wali_password_reset_tokens', function (Blueprint $table) {
            // Satu token aktif per akun — request baru menimpa yang lama (mirip
            // tabel password_reset_tokens bawaan Laravel, dikunci per email di sana).
            $table->foreignUuid('wali_account_id')->primary()->constrained('wali_accounts')->cascadeOnDelete();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wali_password_reset_tokens');
    }
};
