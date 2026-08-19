<?php

use App\Models\TpqStudent;
use App\Models\WaliAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Satu akun wali per nomor HP unik yang sudah punya password wali (login
     * lama di WaliController berbasis cocokkan guardian_phone+guardian_password
     * langsung ke tpq_students) — dikelompokkan supaya satu akun otomatis
     * terhubung ke SEMUA anak dengan nomor HP itu, bukan asumsi ulang di kode
     * aplikasi tiap kali.
     */
    public function up(): void
    {
        $groups = TpqStudent::whereNotNull('guardian_phone')
            ->whereNotNull('guardian_password')
            ->get(['id', 'guardian_phone', 'guardian_password'])
            ->groupBy('guardian_phone');

        foreach ($groups as $phone => $students) {
            $account = WaliAccount::create([
                'phone' => $phone,
                // guardian_password sudah ter-hash (cast 'hashed' di TpqStudent) — pakai apa adanya, jangan di-hash ulang.
                'password' => $students->first()->guardian_password,
            ]);

            $account->students()->attach($students->pluck('id'));
        }
    }

    public function down(): void
    {
        DB::table('wali_account_student')->truncate();
        DB::table('wali_accounts')->truncate();
    }
};
