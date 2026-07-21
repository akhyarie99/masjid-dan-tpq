<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imam_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('masjid_id')->constrained('masjids');
            $table->foreignUuid('imam_id')->constrained('imams');
            $table->foreignUuid('substitute_imam_id')->nullable()->constrained('imams');
            $table->date('date');
            $table->enum('prayer', ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha', 'jumuah', 'tarawih']);
            $table->boolean('is_khatib')->default(false);
            $table->text('khutbah_theme')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->boolean('is_substituted')->default(false);
            $table->timestamps();
            $table->unique(['masjid_id', 'date', 'prayer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imam_schedules');
    }
};
