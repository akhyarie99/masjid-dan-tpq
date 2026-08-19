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
        Schema::create('wali_account_student', function (Blueprint $table) {
            $table->foreignUuid('wali_account_id')->constrained('wali_accounts')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('tpq_students')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['wali_account_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wali_account_student');
    }
};
