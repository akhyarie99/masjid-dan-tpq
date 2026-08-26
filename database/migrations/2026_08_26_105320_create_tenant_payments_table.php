<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('masjid_id')->constrained('masjids')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->date('paid_at');
            $table->text('note')->nullable();
            $table->foreignUuid('recorded_by')->nullable()->constrained('platform_admins')->nullOnDelete();
            $table->timestamps();

            // Satu tenant cuma bisa punya satu pembayaran tercatat per periode
            // bulan — mencegah double-entry kalau superadmin tidak sengaja catat
            // dua kali untuk bulan yang sama.
            $table->unique(['masjid_id', 'period_month', 'period_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payments');
    }
};
