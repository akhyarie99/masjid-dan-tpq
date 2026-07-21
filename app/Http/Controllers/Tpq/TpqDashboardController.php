<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Models\TpqAttendance;
use App\Models\TpqReportCard;
use App\Models\TpqSemester;
use App\Models\TpqSppBill;
use App\Models\TpqStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class TpqDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;

        $stats = Cache::remember("tpq-dashboard:{$masjidId}", 60, fn () => $this->buildStats($masjidId));

        return Inertia::render('Learning/Tpq/Dashboard', ['stats' => $stats]);
    }

    private function buildStats(string $masjidId): array
    {
        $totalStudents = TpqStudent::where('masjid_id', $masjidId)->where('status', 'aktif')->count();

        $presentToday = TpqAttendance::where('status', 'hadir')
            ->whereDate('date', now()->toDateString())
            ->whereHas('student', fn ($q) => $q->where('masjid_id', $masjidId))
            ->count();

        $sppOutstanding = TpqSppBill::whereIn('status', ['unpaid', 'partial'])
            ->whereHas('student', fn ($q) => $q->where('masjid_id', $masjidId))
            ->sum('amount');

        $activeSemester = TpqSemester::whereHas('academicYear', fn ($q) => $q->where('masjid_id', $masjidId))
            ->where('is_active', true)
            ->first();

        $reportPending = 0;
        if ($activeSemester) {
            $generatedCount = TpqReportCard::where('semester_id', $activeSemester->id)
                ->whereHas('student', fn ($q) => $q->where('masjid_id', $masjidId))
                ->count();
            $reportPending = max(0, $totalStudents - $generatedCount);
        }

        return [
            'totalStudents' => $totalStudents,
            'presentToday' => $presentToday,
            'sppOutstanding' => (float) $sppOutstanding,
            'reportPending' => $reportPending,
        ];
    }
}
