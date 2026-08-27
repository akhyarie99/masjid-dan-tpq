<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masjids', function (Blueprint $table) {
            // Informasional saja, TIDAK menggerbang akses (is_active tetap satu-
            // satunya saklar akses) — superadmin sengaja memutuskan manual kapan
            // tenant yang lewat masa aktifnya benar-benar dinonaktifkan, supaya
            // tidak ada tenant ter-lock otomatis padahal sudah bayar tapi
            // pembayarannya belum sempat dicatat.
            $table->date('active_until')->nullable()->after('monthly_fee');
        });
    }

    public function down(): void
    {
        Schema::table('masjids', function (Blueprint $table) {
            $table->dropColumn('active_until');
        });
    }
};
