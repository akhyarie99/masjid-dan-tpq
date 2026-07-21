<?php

namespace App\Console\Commands;

use App\Models\Masjid;
use App\Models\PrayerSchedule;
use App\Services\PrayerTimeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class GeneratePrayerSchedule extends Command
{
    protected $signature = 'prayer:generate-schedule {--days=30 : Jumlah hari ke depan yang dijadwalkan}';

    protected $description = 'Hitung dan simpan jadwal shalat harian untuk setiap masjid, lalu cache untuk portal publik';

    public function handle(PrayerTimeService $prayerTimeService): int
    {
        $days = (int) $this->option('days');

        Masjid::query()->where('is_active', true)->each(function (Masjid $masjid) use ($prayerTimeService, $days) {
            if (! $masjid->latitude || ! $masjid->longitude) {
                $this->warn("Melewati {$masjid->name}: koordinat belum diatur.");

                return;
            }

            $created = 0;

            for ($i = 0; $i < $days; $i++) {
                $date = Carbon::today()->addDays($i);

                if (PrayerSchedule::where('masjid_id', $masjid->id)->whereDate('date', $date->toDateString())->exists()) {
                    continue;
                }

                $times = $prayerTimeService->calculate((float) $masjid->latitude, (float) $masjid->longitude, $date);

                PrayerSchedule::create([
                    'masjid_id' => $masjid->id,
                    'date' => $date->toDateString(),
                    ...$times,
                ]);

                $created++;
            }

            $this->cacheUpcomingSchedule($masjid);

            $this->info("{$masjid->name}: {$created} jadwal baru dibuat.");
        });

        return self::SUCCESS;
    }

    private function cacheUpcomingSchedule(Masjid $masjid): void
    {
        $schedules = PrayerSchedule::where('masjid_id', $masjid->id)
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->orderBy('date')
            ->limit(30)
            ->get();

        Cache::put("prayer-schedule:{$masjid->id}", $schedules, now()->addDay());

        $today = $schedules->first(fn (PrayerSchedule $s) => $s->date->isToday());

        if ($today) {
            Cache::put("prayer-schedule:{$masjid->id}:today", $today, now()->endOfDay());
        }
    }
}
