<?php

namespace App\Http\Controllers\Staff;

use App\Exports\StaffAttendanceExport;
use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

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
                    'id' => $r->id,
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

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $records = StaffAttendance::where('masjid_id', $request->user()->masjid_id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with(['user:id,name', 'clockInLocation:id,name', 'clockOutLocation:id,name'])
            ->orderBy('date')
            ->get();

        return Excel::download(new StaffAttendanceExport($records), "rekap-presensi-staf-{$year}-{$month}.xlsx");
    }

    public function detail(Request $request, StaffAttendance $attendance): JsonResponse
    {
        abort_unless($attendance->masjid_id === $request->user()->masjid_id, 403);

        $attendance->load(['user:id,name', 'clockInLocation:id,name', 'clockOutLocation:id,name']);

        return response()->json([
            'date' => $attendance->date->format('Y-m-d'),
            'user' => $attendance->user?->name,
            'clock_in' => $attendance->clock_in?->format('H:i:s'),
            'clock_out' => $attendance->clock_out?->format('H:i:s'),
            'clock_in_location' => $attendance->clockInLocation?->name,
            'clock_out_location' => $attendance->clockOutLocation?->name,
            'clock_in_lat' => $attendance->clock_in_lat,
            'clock_in_lng' => $attendance->clock_in_lng,
            'clock_out_lat' => $attendance->clock_out_lat,
            'clock_out_lng' => $attendance->clock_out_lng,
            'clock_in_gps_accuracy' => $attendance->clock_in_gps_accuracy,
            'clock_out_gps_accuracy' => $attendance->clock_out_gps_accuracy,
            'clock_in_is_mock_location' => $attendance->clock_in_is_mock_location,
            'clock_out_is_mock_location' => $attendance->clock_out_is_mock_location,
            'clock_in_liveness_verified' => $attendance->clock_in_liveness_verified,
            'clock_out_liveness_verified' => $attendance->clock_out_liveness_verified,
            'clock_in_device_info' => $attendance->clock_in_device_info,
            'clock_out_device_info' => $attendance->clock_out_device_info,
            'clock_in_photo_url' => $attendance->clock_in_photo ? route('admin.staff-attendance.photo', [$attendance, 'masuk']) : null,
            'clock_out_photo_url' => $attendance->clock_out_photo ? route('admin.staff-attendance.photo', [$attendance, 'pulang']) : null,
        ]);
    }

    public function photo(Request $request, StaffAttendance $attendance, string $type): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless($attendance->masjid_id === $request->user()->masjid_id, 403);
        abort_unless(in_array($type, ['masuk', 'pulang']), 404);

        $path = $type === 'masuk' ? $attendance->clock_in_photo : $attendance->clock_out_photo;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
