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
        Schema::table('tpq_students', function (Blueprint $table) {
            $table->boolean('notify_whatsapp')->default(true)->after('guardian_whatsapp');
            $table->boolean('notify_webpush')->default(false)->after('notify_whatsapp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tpq_students', function (Blueprint $table) {
            $table->dropColumn(['notify_whatsapp', 'notify_webpush']);
        });
    }
};
