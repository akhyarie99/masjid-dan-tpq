<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_program_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->constrained('social_programs');
            $table->foreignUuid('jamaah_id')->nullable()->constrained('jamaah_profiles');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('aid_type')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->timestamp('distributed_at')->nullable();
            $table->string('receipt_signature')->nullable();
            $table->foreignUuid('distributed_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_program_recipients');
    }
};
