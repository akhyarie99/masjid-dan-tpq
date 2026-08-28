<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tpq_spp_bills', function (Blueprint $table) {
            // Wali upload bukti transfer sendiri lewat portal, admin yang
            // memverifikasi ke rekening lalu Setujui/Tolak — proof_status
            // TERPISAH dari status pembayaran (unpaid/partial/paid) supaya bisa
            // membedakan "belum ada aksi apa-apa" dari "sudah kirim bukti,
            // menunggu direview". Disetujui -> catat pembayaran normal (lihat
            // TpqSppController::approveProof) sekaligus reset proof_status ke
            // 'none'; file buktinya tetap disimpan sebagai riwayat/arsip.
            $table->string('proof_file')->nullable()->after('reminder_sent');
            $table->enum('proof_status', ['none', 'pending', 'rejected'])->default('none')->after('proof_file');
            $table->text('proof_rejection_reason')->nullable()->after('proof_status');
            $table->timestamp('proof_submitted_at')->nullable()->after('proof_rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('tpq_spp_bills', function (Blueprint $table) {
            $table->dropColumn(['proof_file', 'proof_status', 'proof_rejection_reason', 'proof_submitted_at']);
        });
    }
};
