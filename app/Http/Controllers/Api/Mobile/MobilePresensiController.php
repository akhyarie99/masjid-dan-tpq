<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ScopesTeacherClasses;
use App\Http\Controllers\Controller;
use App\Jobs\SendAlfaAlertJob;
use App\Models\TpqAcademicYear;
use App\Models\TpqAttendance;
use App\Models\TpqClass;
use App\Models\TpqStudentClass;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MobilePresensiController extends Controller
{
    use ScopesTeacherClasses;

    public function kelasList(Request $request): JsonResponse
    {
        $user = $request->user();
        $activeYear = TpqAcademicYear::where('masjid_id', $user->masjid_id)->where('is_active', true)->first();

        $classes = $this->scopedClasses($user, $user->masjid_id, $activeYear?->id)
            ->map(fn (TpqClass $class) => [
                'id' => $class->id,
                'name' => $class->name,
                'student_count' => TpqStudentClass::where('class_id', $class->id)
                    ->where('academic_year_id', $activeYear?->id)
                    ->count(),
            ])->values();

        return response()->json(['classes' => $classes]);
    }

    public function santriList(Request $request, TpqClass $class): JsonResponse
    {
        if (! $this->authorizeClass($request, $class)) {
            return response()->json(['message' => 'Anda tidak mengampu kelas ini.'], 403);
        }

        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();

        $students = TpqStudentClass::with('student:id,name,nis,photo')
            ->where('class_id', $class->id)
            ->where('academic_year_id', $activeYear?->id)
            ->get()
            ->pluck('student')
            ->filter()
            ->values();

        return response()->json(['students' => $students]);
    }

    public function todayAttendance(Request $request, TpqClass $class): JsonResponse
    {
        if (! $this->authorizeClass($request, $class)) {
            return response()->json(['message' => 'Anda tidak mengampu kelas ini.'], 403);
        }

        $attendances = TpqAttendance::where('class_id', $class->id)
            ->whereDate('date', now()->toDateString())
            ->get(['student_id', 'status', 'notes']);

        return response()->json([
            'date' => now()->toDateString(),
            'attendances' => $attendances,
        ]);
    }

    public function submit(Request $request, TpqClass $class): JsonResponse
    {
        if (! $this->authorizeClass($request, $class)) {
            return response()->json(['message' => 'Anda tidak mengampu kelas ini.'], 403);
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'uuid', 'exists:tpq_students,id'],
            'attendances.*.status' => ['required', 'in:hadir,izin,sakit,alfa'],
            'attendances.*.notes' => ['nullable', 'string', 'max:200'],
        ]);

        $user = $request->user();
        $masjid = $user->masjid;

        if ($masjid->latitude && $masjid->longitude) {
            $distance = $this->haversineDistance(
                (float) $masjid->latitude,
                (float) $masjid->longitude,
                (float) $data['latitude'],
                (float) $data['longitude']
            );

            if ($distance > 500) {
                return response()->json([
                    'message' => "Anda berada terlalu jauh dari masjid (".round($distance)."m). Presensi harus dilakukan di area masjid (maks. 500m).",
                    'distance' => round($distance),
                ], 422);
            }
        }

        $saved = 0;

        foreach ($data['attendances'] as $item) {
            $existing = TpqAttendance::where('student_id', $item['student_id'])
                ->whereDate('date', $data['date'])
                ->first();

            $values = [
                'class_id' => $class->id,
                'status' => $item['status'],
                'notes' => $item['notes'] ?? null,
                'recorded_by' => $user->id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'device_info' => $request->userAgent(),
            ];

            if ($existing) {
                $existing->update($values);
            } else {
                TpqAttendance::create([
                    'student_id' => $item['student_id'],
                    'date' => $data['date'],
                    ...$values,
                ]);
            }

            $saved++;
        }

        app(FcmService::class)->notifyPresensiSubmitted($class, $data['date'], $saved, $user);

        collect($data['attendances'])
            ->where('status', 'alfa')
            ->each(fn ($item) => SendAlfaAlertJob::dispatch($item['student_id'], $data['date']));

        return response()->json([
            'message' => "Presensi berhasil disimpan untuk {$saved} santri.",
            'saved' => $saved,
            'date' => $data['date'],
        ]);
    }

    public function rekap(Request $request, TpqClass $class): JsonResponse
    {
        if (! $this->authorizeClass($request, $class)) {
            return response()->json(['message' => 'Anda tidak mengampu kelas ini.'], 403);
        }

        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();

        $students = TpqStudentClass::with('student:id,name,nis')
            ->where('class_id', $class->id)
            ->where('academic_year_id', $activeYear?->id)
            ->get()
            ->pluck('student')
            ->filter();

        $attendances = TpqAttendance::where('class_id', $class->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('student_id');

        $recap = $students->map(function ($student) use ($attendances, $start, $end) {
            $records = $attendances->get($student->id, collect());
            $totalDays = $start->diffInDays($end) + 1;
            $presentCount = $records->where('status', 'hadir')->count();

            return [
                'student' => ['id' => $student->id, 'name' => $student->name, 'nis' => $student->nis],
                'present_count' => $presentCount,
                'sick_count' => $records->where('status', 'sakit')->count(),
                'permission_count' => $records->where('status', 'izin')->count(),
                'absent_count' => $records->where('status', 'alfa')->count(),
                'percent' => $totalDays > 0 ? round(($presentCount / $totalDays) * 100, 1) : 0,
            ];
        })->values();

        return response()->json([
            'month' => (int) $month,
            'year' => (int) $year,
            'recap' => $recap,
        ]);
    }

    private function authorizeClass(Request $request, TpqClass $class): bool
    {
        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();

        return $this->assertClassAccessible($request->user(), $class, $activeYear?->id);
    }

    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
