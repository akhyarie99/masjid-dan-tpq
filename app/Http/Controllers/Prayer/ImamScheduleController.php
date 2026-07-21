<?php

namespace App\Http\Controllers\Prayer;

use App\Http\Controllers\Controller;
use App\Models\Imam;
use App\Models\ImamSchedule;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ImamScheduleController extends Controller
{
    private array $prayers = ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha', 'jumuah'];

    public function index(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $schedules = ImamSchedule::with('imam:id,name,type', 'substituteImam:id,name')
            ->where('masjid_id', $masjidId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return Inertia::render('Prayer/ImamSchedule', [
            'month' => (int) $month,
            'year' => (int) $year,
            'prayers' => $this->prayers,
            'schedules' => $schedules,
            'imams' => Imam::where('masjid_id', $masjidId)->where('is_active', true)->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'prayer' => ['required', 'in:fajr,dhuhr,asr,maghrib,isha,jumuah,tarawih'],
            'imam_id' => ['required', 'uuid', 'exists:imams,id'],
            'is_khatib' => ['boolean'],
            'khutbah_theme' => ['nullable', 'string'],
        ]);

        $masjidId = $request->user()->masjid_id;

        $existing = ImamSchedule::where('masjid_id', $masjidId)
            ->whereDate('date', $data['date'])
            ->where('prayer', $data['prayer'])
            ->first();

        $values = [
            'imam_id' => $data['imam_id'],
            'is_khatib' => $data['is_khatib'] ?? false,
            'khutbah_theme' => $data['khutbah_theme'] ?? null,
            'is_substituted' => false,
            'substitute_imam_id' => null,
        ];

        if ($existing) {
            $existing->update($values);
        } else {
            ImamSchedule::create([
                'masjid_id' => $masjidId,
                'date' => $data['date'],
                'prayer' => $data['prayer'],
                ...$values,
            ]);
        }

        return back()->with('success', 'Jadwal imam berhasil disimpan.');
    }

    public function substitute(Request $request, ImamSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'substitute_imam_id' => ['required', 'uuid', 'exists:imams,id'],
        ]);

        $schedule->update([
            'substitute_imam_id' => $data['substitute_imam_id'],
            'is_substituted' => true,
        ]);

        return back()->with('success', 'Imam pengganti berhasil diatur.');
    }

    public function notifyAll(Request $request, WhatsAppService $whatsAppService): RedirectResponse
    {
        $masjid = $request->user()->masjid;
        $tomorrow = Carbon::tomorrow();

        $schedules = ImamSchedule::with(['imam:id,name,phone', 'substituteImam:id,name,phone'])
            ->where('masjid_id', $masjid->id)
            ->whereDate('date', $tomorrow->toDateString())
            ->get();

        foreach ($schedules as $schedule) {
            $imam = $schedule->is_substituted ? $schedule->substituteImam : $schedule->imam;

            if (! $imam || ! $imam->phone) {
                continue;
            }

            $message = <<<TEXT
            Assalamu'alaikum Ust. {$imam->name},

            Mengingatkan jadwal imam besok:
            📅 {$tomorrow->translatedFormat('l, d F Y')}
            🕌 Shalat: {$this->prayerLabel($schedule->prayer)}
            🏛️ {$masjid->name}

            Jazakallahu khairan.
            TEXT;

            $whatsAppService->send($imam->phone, $message);
        }

        return back()->with('success', 'Notifikasi berhasil dikirim ke semua imam besok.');
    }

    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $masjid = $request->user()->masjid;
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $schedules = ImamSchedule::with('imam:id,name', 'substituteImam:id,name')
            ->where('masjid_id', $masjid->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        $pdf = Pdf::loadView('pdf.imam-schedule', [
            'masjid' => $masjid,
            'month' => $start,
            'schedules' => $schedules,
            'prayers' => $this->prayers,
        ]);

        return $pdf->download("jadwal-imam-{$start->format('Ym')}.pdf");
    }

    private function prayerLabel(string $prayer): string
    {
        return [
            'fajr' => 'Subuh', 'dhuhr' => 'Dzuhur', 'asr' => 'Ashar', 'maghrib' => 'Maghrib',
            'isha' => 'Isya', 'jumuah' => 'Jumat', 'tarawih' => 'Tarawih',
        ][$prayer] ?? $prayer;
    }
}
