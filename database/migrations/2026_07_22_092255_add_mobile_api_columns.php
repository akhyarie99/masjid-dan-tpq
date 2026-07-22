<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fcm_token')->nullable()->after('avatar');
        });

        Schema::table('masjids', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('bank_accounts');
        });

        Schema::table('tpq_attendances', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('recorded_by');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('device_info')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });

        Schema::table('masjids', function (Blueprint $table) {
            $table->dropColumn('logo');
        });

        Schema::table('tpq_attendances', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'device_info']);
        });
    }
};
