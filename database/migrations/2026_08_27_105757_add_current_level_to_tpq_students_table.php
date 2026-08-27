<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tpq_students', function (Blueprint $table) {
            // Jenjang mengaji (Iqro 1-6 -> Al-Qur'an) — TERPISAH dari "Kelas"
            // (tpq_classes), yang cuma pengelompokan administratif per tahun
            // ajaran. Ini sumber kebenaran jenjang santri, berubah lewat aksi
            // "Naik Jilid" yang disengaja (lihat tpq_level_promotions), bukan
            // ditebak diam-diam dari entri progres harian terakhir.
            $table->enum('current_method', ['iqro', 'quran'])->default('iqro')->after('photo');
            $table->unsignedTinyInteger('current_jilid')->nullable()->default(1)->after('current_method');
        });

        // Backfill dari entri progres harian terakhir tiap santri (kalau ada) —
        // supaya jenjang yang sudah berjalan tidak terlihat "reset" ke Iqro 1
        // begitu kolom ini pertama kali ada.
        DB::table('tpq_students')->orderBy('id')->select('id')->chunkById(200, function ($students) {
            foreach ($students as $student) {
                $last = DB::table('tpq_daily_progress')
                    ->where('student_id', $student->id)
                    ->orderByDesc('date')
                    ->first();

                if ($last) {
                    DB::table('tpq_students')->where('id', $student->id)->update([
                        'current_method' => $last->method,
                        'current_jilid' => $last->method === 'iqro' ? $last->jilid : null,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tpq_students', function (Blueprint $table) {
            $table->dropColumn(['current_method', 'current_jilid']);
        });
    }
};
