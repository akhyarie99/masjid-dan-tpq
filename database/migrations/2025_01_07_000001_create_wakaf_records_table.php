<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wakaf_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('masjid_id')->constrained('masjids');
            $table->string('wakif_name');
            $table->string('wakif_phone')->nullable();
            $table->enum('type', ['tanah', 'bangunan', 'uang', 'lainnya']);
            $table->text('description')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->string('certificate_number')->nullable();
            $table->enum('status', ['proses', 'selesai'])->default('proses');
            $table->date('donated_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wakaf_records');
    }
};
