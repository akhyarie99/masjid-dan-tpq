<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ganti kunci subscription push dari string guardian_phone (rawan beda
     * format, mis. "0812..." vs "+62812...") ke wali_account_id yang jadi
     * identitas otentikasi resmi sekarang. Tabel ini baru dibuat & masih
     * kosong di produksi, aman diubah strukturnya langsung tanpa migrasi data.
     */
    public function up(): void
    {
        Schema::table('tpq_guardian_push_subscriptions', function (Blueprint $table) {
            $table->dropUnique('guardian_endpoint_unique');
            $table->dropIndex(['guardian_phone']);
            $table->dropColumn('guardian_phone');
            $table->foreignUuid('wali_account_id')->after('id')->constrained('wali_accounts')->cascadeOnDelete();
            $table->unique(['wali_account_id', 'endpoint'], 'wali_account_endpoint_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tpq_guardian_push_subscriptions', function (Blueprint $table) {
            $table->dropUnique('wali_account_endpoint_unique');
            $table->dropForeign(['wali_account_id']);
            $table->dropColumn('wali_account_id');
            $table->string('guardian_phone')->after('id');
            $table->index('guardian_phone');
            $table->unique(['guardian_phone', 'endpoint'], 'guardian_endpoint_unique');
        });
    }
};
