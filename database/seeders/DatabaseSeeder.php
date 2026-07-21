<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MasjidSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            TransactionCategorySeeder::class,
            KasAccountSeeder::class,
            AssetCategorySeeder::class,
            TpqSubjectSeeder::class,
            PrayerScheduleSeeder::class,
        ]);
    }
}
