<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_distributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('masjid_id')->constrained('masjids');
            $table->foreignUuid('jamaah_id')->nullable()->constrained('jamaah_profiles');
            $table->string('recipient_name');
            $table->text('address')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->integer('package_count')->default(1);
            $table->integer('year');
            $table->timestamp('distributed_at')->nullable();
            $table->foreignUuid('distributed_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_distributions');
    }
};
