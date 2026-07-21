<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zakat_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('masjid_id')->constrained('masjids');
            $table->enum('type', ['fitrah', 'maal', 'profesi', 'infaq']);
            $table->string('payer_name');
            $table->string('payer_phone')->nullable();
            $table->integer('dependents')->default(1);
            $table->decimal('amount_per_person', 10, 2)->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->enum('payment_type', ['beras', 'uang'])->default('uang');
            $table->decimal('rice_kg', 8, 2)->nullable();
            $table->integer('year');
            $table->boolean('ramadhan')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zakat_records');
    }
};
