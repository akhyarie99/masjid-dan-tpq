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
        Schema::table('masjids', function (Blueprint $table) {
            $table->string('custom_domain')->nullable()->unique()->after('slug');
            $table->timestamp('custom_domain_verified_at')->nullable()->after('custom_domain');
            $table->string('custom_domain_verification_token')->nullable()->after('custom_domain_verified_at');
            $table->string('subscription_status')->default('trial')->after('is_active');
            $table->string('theme_color')->nullable()->after('subscription_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('masjids', function (Blueprint $table) {
            $table->dropColumn([
                'custom_domain',
                'custom_domain_verified_at',
                'custom_domain_verification_token',
                'subscription_status',
                'theme_color',
            ]);
        });
    }
};
