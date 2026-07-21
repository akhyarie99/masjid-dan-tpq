<?php

namespace Tests\Feature;

use App\Models\PrayerSchedule;
use App\Services\PrayerTimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class PrayerScheduleTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    public function test_service_calculates_six_daily_prayer_times(): void
    {
        $service = app(PrayerTimeService::class);

        $times = $service->calculate(-7.4894, 109.0044, Carbon::parse('2026-07-21'));

        foreach (['fajr', 'sunrise', 'dhuhr', 'asr', 'maghrib', 'isha'] as $key) {
            $this->assertArrayHasKey($key, $times);
            $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $times[$key]);
        }

        // Urutan waktu shalat harus logis: subuh < terbit < dzuhur < ashar < maghrib < isya
        $order = array_map(fn ($t) => Carbon::parse($t), $times);
        $this->assertTrue($order['fajr']->lt($order['sunrise']));
        $this->assertTrue($order['sunrise']->lt($order['dhuhr']));
        $this->assertTrue($order['dhuhr']->lt($order['asr']));
        $this->assertTrue($order['asr']->lt($order['maghrib']));
        $this->assertTrue($order['maghrib']->lt($order['isha']));
    }

    public function test_generate_command_creates_30_days_of_schedule(): void
    {
        $masjid = $this->createMasjid();

        $this->artisan('prayer:generate-schedule')->assertSuccessful();

        $this->assertSame(30, PrayerSchedule::where('masjid_id', $masjid->id)->count());
    }

    public function test_generate_command_skips_existing_dates(): void
    {
        $masjid = $this->createMasjid();

        $this->artisan('prayer:generate-schedule')->assertSuccessful();
        $firstRunCount = PrayerSchedule::where('masjid_id', $masjid->id)->count();

        $this->artisan('prayer:generate-schedule')->assertSuccessful();
        $secondRunCount = PrayerSchedule::where('masjid_id', $masjid->id)->count();

        $this->assertSame($firstRunCount, $secondRunCount);
    }

    public function test_generate_command_caches_today_schedule(): void
    {
        $masjid = $this->createMasjid();

        $this->artisan('prayer:generate-schedule')->assertSuccessful();

        $cached = Cache::get("prayer-schedule:{$masjid->id}:today");
        $this->assertNotNull($cached);
        $this->assertSame(now()->toDateString(), Carbon::parse($cached->date)->toDateString());
    }

    public function test_generate_command_skips_masjid_without_coordinates(): void
    {
        $this->createMasjid(['latitude' => null, 'longitude' => null]);

        $this->artisan('prayer:generate-schedule')->assertSuccessful();

        $this->assertSame(0, PrayerSchedule::count());
    }
}
