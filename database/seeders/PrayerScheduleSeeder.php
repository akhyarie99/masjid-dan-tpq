<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class PrayerScheduleSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('prayer:generate-schedule', ['--days' => 30]);
    }
}
