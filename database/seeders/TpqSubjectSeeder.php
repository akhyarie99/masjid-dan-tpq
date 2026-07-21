<?php

namespace Database\Seeders;

use App\Models\Masjid;
use App\Models\TpqSubject;
use Illuminate\Database\Seeder;

class TpqSubjectSeeder extends Seeder
{
    public function run(): void
    {
        $masjid = Masjid::firstOrFail();

        $subjects = [
            'Bacaan Al-Quran (Makhorijul Huruf & Tajwid)',
            'Hafalan Surah/Juz',
            'Adab & Akhlaq',
            'Doa Harian & Ibadah Praktis',
            'Pengetahuan Islam',
        ];

        foreach ($subjects as $order => $name) {
            TpqSubject::firstOrCreate([
                'masjid_id' => $masjid->id,
                'name' => $name,
            ], ['order' => $order, 'is_active' => true]);
        }
    }
}
