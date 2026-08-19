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
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('masjid_id')->constrained('masjids');
            $table->date('date');

            $table->timestamp('clock_in')->nullable();
            $table->decimal('clock_in_lat', 10, 8)->nullable();
            $table->decimal('clock_in_lng', 11, 8)->nullable();
            $table->string('clock_in_photo')->nullable();
            $table->foreignUuid('clock_in_location_id')->nullable()->constrained('masjid_locations');
            $table->boolean('clock_in_is_mock_location')->default(false);
            $table->float('clock_in_gps_accuracy')->nullable();
            $table->json('clock_in_device_info')->nullable();
            $table->boolean('clock_in_liveness_verified')->default(false);

            $table->timestamp('clock_out')->nullable();
            $table->decimal('clock_out_lat', 10, 8)->nullable();
            $table->decimal('clock_out_lng', 11, 8)->nullable();
            $table->string('clock_out_photo')->nullable();
            $table->foreignUuid('clock_out_location_id')->nullable()->constrained('masjid_locations');
            $table->boolean('clock_out_is_mock_location')->default(false);
            $table->float('clock_out_gps_accuracy')->nullable();
            $table->json('clock_out_device_info')->nullable();
            $table->boolean('clock_out_liveness_verified')->default(false);

            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
    }
};
