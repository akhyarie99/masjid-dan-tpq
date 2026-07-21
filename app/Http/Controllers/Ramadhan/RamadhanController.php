<?php

namespace App\Http\Controllers\Ramadhan;

use App\Http\Controllers\Controller;
use App\Models\PrayerSchedule;
use App\Services\PrayerTimeService;
use App\Support\HijriDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class RamadhanController extends Controller
{
    public function index(Request $request): Response
    {
        $masjid = $request->user()->masjid;
        $today = Carbon::today();
        $hijriToday = HijriDate::fromGregorian($today);
        $isRamadhanNow = $hijriToday['month'] === 9;

        [$start, $end] = $this->ramadhanRange($today, $hijriToday);

        $schedules = PrayerSchedule::where('masjid_id', $masjid->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        return Inertia::render('Ramadhan/Index', [
            'isRamadhanNow' => $isRamadhanNow,
            'hijriToday' => $hijriToday,
            'ramadhanYear' => $hijriToday['month'] <= 9 ? $hijriToday['year'] : $hijriToday['year'] + 1,
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'imsakiyah' => $schedules->map(fn (PrayerSchedule $s) => [
                'date' => $s->date->toDateString(),
                'imsak' => Carbon::parse($s->fajr)->subMinutes(10)->format('H:i'),
                'fajr' => Carbon::parse($s->fajr)->format('H:i'),
                'dhuhr' => Carbon::parse($s->dhuhr)->format('H:i'),
                'asr' => Carbon::parse($s->asr)->format('H:i'),
                'maghrib' => Carbon::parse($s->maghrib)->format('H:i'),
                'isha' => Carbon::parse($s->isha)->format('H:i'),
            ]),
        ]);
    }

    public function generateImsakiyah(Request $request, PrayerTimeService $prayerTimeService): RedirectResponse
    {
        $masjid = $request->user()->masjid;

        if (! $masjid->latitude || ! $masjid->longitude) {
            return back()->with('error', 'Koordinat masjid belum diatur. Silakan atur di menu Pengaturan.');
        }

        $today = Carbon::today();
        [$start, $end] = $this->ramadhanRange($today, HijriDate::fromGregorian($today));
        $created = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
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

        return back()->with('success', "Imsakiyah berhasil dibuat ({$created} hari baru).");
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function ramadhanRange(Carbon $today, array $hijriToday): array
    {
        // Perkiraan tabular: cari tanggal Masehi saat 1 dan akhir Ramadhan
        // dengan menggeser dari hari ini berdasarkan selisih tanggal Hijriah.
        $daysInRamadhan = 30;

        if ($hijriToday['month'] === 9) {
            $start = $today->copy()->subDays($hijriToday['day'] - 1);
        } elseif ($hijriToday['month'] < 9) {
            $approxDaysUntil = (9 - $hijriToday['month']) * 29 - $hijriToday['day'];
            $start = $today->copy()->addDays(max($approxDaysUntil, 0));
        } else {
            $approxDaysUntil = (9 + 12 - $hijriToday['month']) * 29 - $hijriToday['day'];
            $start = $today->copy()->addDays(max($approxDaysUntil, 0));
        }

        return [$start, $start->copy()->addDays($daysInRamadhan - 1)];
    }
}
