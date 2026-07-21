<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('asset_id')->constrained('assets');
            $table->foreignUuid('reported_by')->constrained('users');
            $table->foreignUuid('handled_by')->nullable()->constrained('users');
            $table->enum('type', ['scheduled', 'repair', 'inspection']);
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->enum('status', ['scheduled', 'in_progress', 'done'])->default('scheduled');
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenances');
    }
};
