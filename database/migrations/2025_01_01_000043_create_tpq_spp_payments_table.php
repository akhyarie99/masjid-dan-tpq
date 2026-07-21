<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpq_spp_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bill_id')->constrained('tpq_spp_bills');
            $table->foreignUuid('received_by')->constrained('users');
            $table->decimal('amount', 10, 2);
            $table->date('paid_date');
            $table->string('payment_method')->default('cash');
            $table->string('receipt_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpq_spp_payments');
    }
};
