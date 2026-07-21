<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('masjid_id')->constrained('masjids');
            $table->foreignUuid('category_id')->constrained('asset_categories');
            $table->string('name');
            $table->string('asset_code')->unique();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('location');
            $table->enum('condition', ['baik', 'cukup', 'rusak_ringan', 'rusak_berat']);
            $table->enum('status', ['aktif', 'dipinjam', 'perbaikan', 'dihapus'])->default('aktif');
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('vendor')->nullable();
            $table->text('description')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->integer('maintenance_interval_days')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
