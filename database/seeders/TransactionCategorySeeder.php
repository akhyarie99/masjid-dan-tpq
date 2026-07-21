<?php

namespace Database\Seeders;

use App\Models\Masjid;
use App\Models\TransactionCategory;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $masjid = Masjid::firstOrFail();

        $income = [
            'Infaq Jumat', 'Donasi', 'Zakat Fitrah', 'Zakat Maal', 'Wakaf', 'Sewa Aset', 'Lainnya',
        ];

        $expense = [
            'Listrik', 'Air', 'Kebersihan', 'Keamanan', 'Honorarium Imam', 'Honorarium Marbot',
            'Perlengkapan Ibadah', 'Kegiatan', 'Renovasi', 'Sosial', 'Lainnya',
        ];

        foreach ($income as $name) {
            TransactionCategory::firstOrCreate([
                'masjid_id' => $masjid->id,
                'name' => $name,
                'type' => 'income',
            ], ['is_system' => true]);
        }

        foreach ($expense as $name) {
            TransactionCategory::firstOrCreate([
                'masjid_id' => $masjid->id,
                'name' => $name,
                'type' => 'expense',
            ], ['is_system' => true]);
        }
    }
}
