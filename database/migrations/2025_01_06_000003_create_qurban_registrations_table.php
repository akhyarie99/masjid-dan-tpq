<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('masjid_id')->constrained('masjids');
            $table->string('shohibul_name');
            $table->string('phone');
            $table->enum('animal_type', ['sapi', 'kambing']);
            $table->integer('share_count')->default(1);
            $table->string('animal_name')->nullable();
            $table->integer('year');
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_registrations');
    }
};
