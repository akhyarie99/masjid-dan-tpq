<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['masjid_id', 'status', 'type', 'transaction_date'], 'transactions_report_idx');
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->index(['masjid_id', 'status', 'paid_at'], 'donations_status_paid_idx');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->index(['masjid_id', 'status', 'start_at'], 'activities_upcoming_idx');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->index(['masjid_id', 'is_published', 'published_at'], 'announcements_published_idx');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->index(['masjid_id', 'status'], 'assets_status_idx');
            $table->index('next_maintenance_date', 'assets_maintenance_idx');
        });

        Schema::table('tpq_attendances', function (Blueprint $table) {
            $table->index(['class_id', 'date'], 'tpq_attendances_class_date_idx');
        });

        Schema::table('tpq_spp_bills', function (Blueprint $table) {
            $table->index('status', 'tpq_spp_bills_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_report_idx');
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->dropIndex('donations_status_paid_idx');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('activities_upcoming_idx');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex('announcements_published_idx');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('assets_status_idx');
            $table->dropIndex('assets_maintenance_idx');
        });

        Schema::table('tpq_attendances', function (Blueprint $table) {
            $table->dropIndex('tpq_attendances_class_date_idx');
        });

        Schema::table('tpq_spp_bills', function (Blueprint $table) {
            $table->dropIndex('tpq_spp_bills_status_idx');
        });
    }
};
