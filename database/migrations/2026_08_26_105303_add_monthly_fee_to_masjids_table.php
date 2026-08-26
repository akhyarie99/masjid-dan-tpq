<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masjids', function (Blueprint $table) {
            // Null = pakai tarif default platform (platform_settings.default_monthly_fee) —
            // diisi cuma kalau tenant ini punya harga khusus (diskon, negosiasi, dsb).
            $table->decimal('monthly_fee', 12, 2)->nullable()->after('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::table('masjids', function (Blueprint $table) {
            $table->dropColumn('monthly_fee');
        });
    }
};
