<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use App\Models\Masjid;
use Illuminate\Database\Seeder;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $masjid = Masjid::firstOrFail();

        $categories = [
            'Bangunan', 'Elektronik', 'Furnitur', 'Perlengkapan Ibadah',
            'Kendaraan', 'Alat Kebersihan', 'Tanah', 'Lainnya',
        ];

        foreach ($categories as $name) {
            AssetCategory::firstOrCreate([
                'masjid_id' => $masjid->id,
                'name' => $name,
            ]);
        }
    }
}
