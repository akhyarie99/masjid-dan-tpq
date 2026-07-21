<?php

namespace App\Http\Controllers\Prayer;

use App\Http\Controllers\Controller;
use App\Models\PrayerSchedule;
use App\Services\PrayerTimeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PrayerScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $masjid = $request->user()->masjid;

        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;

        $schedules = PrayerSchedule::where('masjid_id', $masjid->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        return Inertia::render('Prayer/Schedule', [
            'schedules' => $schedules,
            'month' => (int) $month,
            'year' => (int) $year,
            'masjid' => $masjid->only(['id', 'name', 'latitude', 'longitude', 'prayer_method']),
        ]);
    }

    public function generate(Request $request, PrayerTimeService $prayerTimeService): RedirectResponse
    {
        $masjid = $request->user()->masjid;

        if (! $masjid->latitude || ! $masjid->longitude) {
            return back()->with('error', 'Koordinat masjid belum diatur. Silakan atur di menu Pengaturan.');
        }

        $days = $request->integer('days', 30);
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

        Cache::forget("prayer-schedule:{$masjid->id}");
        Cache::forget("prayer-schedule:{$masjid->id}:today");

        return back()->with('success', "{$created} jadwal shalat baru berhasil dibuat.");
    }
}
