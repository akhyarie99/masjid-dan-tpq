<?php

namespace Database\Seeders;

use App\Models\Masjid;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasjidSeeder extends Seeder
{
    public function run(): void
    {
        Masjid::firstOrCreate(
            ['slug' => 'masjid-default'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Masjid Default',
                'address' => 'Alamat masjid belum diatur. Silakan ubah di menu Pengaturan.',
                'latitude' => (float) env('MASJID_LATITUDE', -7.4894),
                'longitude' => (float) env('MASJID_LONGITUDE', 109.0044),
                'prayer_method' => 'kemenag',
                'is_active' => true,
            ]
        );
    }
}
