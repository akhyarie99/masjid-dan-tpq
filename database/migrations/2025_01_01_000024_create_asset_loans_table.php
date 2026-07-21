<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('asset_id')->constrained('assets');
            $table->foreignUuid('requested_by')->constrained('users');
            $table->foreignUuid('approved_by')->nullable()->constrained('users');
            $table->string('borrower_name');
            $table->string('borrower_phone');
            $table->string('purpose');
            $table->date('loan_date');
            $table->date('return_date_planned');
            $table->date('return_date_actual')->nullable();
            $table->enum('condition_out', ['baik', 'cukup', 'rusak_ringan']);
            $table->enum('condition_in', ['baik', 'cukup', 'rusak_ringan', 'rusak_berat'])->nullable();
            $table->enum('status', ['pending', 'approved', 'active', 'returned', 'overdue'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_loans');
    }
};
