<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StaffAttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $staff = User::where('masjid_id', $request->user()->masjid_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $attendances = StaffAttendance::where('masjid_id', $request->user()->masjid_id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('user_id');

        $recap = $staff->map(function (User $user) use ($attendances, $start, $end) {
            $records = $attendances->get($user->id, collect());
            $totalDays = $start->diffInDays($end) + 1;
            $completeCount = $records->whereNotNull('clock_in')->whereNotNull('clock_out')->count();

            return [
                'user' => ['id' => $user->id, 'name' => $user->name],
                'days' => $records->keyBy(fn ($r) => $r->date->toDateString())->map(fn ($r) => [
                    'clock_in' => $r->clock_in?->format('H:i'),
                    'clock_out' => $r->clock_out?->format('H:i'),
                    'clock_in_mock' => $r->clock_in_is_mock_location,
                    'clock_out_mock' => $r->clock_out_is_mock_location,
                    'clock_in_liveness_ok' => $r->clock_in_liveness_verified,
                    'clock_out_liveness_ok' => $r->clock_out_liveness_verified,
                ]),
                'present_count' => $records->whereNotNull('clock_in')->count(),
                'complete_count' => $completeCount,
                'percent' => $totalDays > 0 ? round(($completeCount / $totalDays) * 100, 1) : 0,
            ];
        });

        return Inertia::render('Staff/Attendance/Index', [
            'month' => (int) $month,
            'year' => (int) $year,
            'recap' => $recap->values(),
        ]);
    }
}
