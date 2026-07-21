<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('majelis_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('majelis_id')->constrained('majelis');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->date('joined_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('majelis_members');
    }
};
