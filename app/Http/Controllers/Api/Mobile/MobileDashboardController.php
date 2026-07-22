<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ScopesTeacherClasses;
use App\Http\Controllers\Controller;
use App\Models\TpqAcademicYear;
use App\Models\TpqAttendance;
use App\Models\TpqClass;
use App\Models\TpqStudentClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{
    use ScopesTeacherClasses;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $masjidId = $user->masjid_id;
        $activeYear = TpqAcademicYear::where('masjid_id', $masjidId)->where('is_active', true)->first();

        $classes = $this->scopedClasses($user, $masjidId, $activeYear?->id);
        $classIds = $classes->pluck('id');

        $totalStudents = TpqStudentClass::whereIn('class_id', $classIds)
            ->where('academic_year_id', $activeYear?->id)
            ->count();

        $presentToday = TpqAttendance::whereIn('class_id', $classIds)
            ->where('status', 'hadir')
            ->whereDate('date', now()->toDateString())
            ->count();

        $myClasses = $classes->map(function (TpqClass $class) use ($activeYear) {
            $studentCount = TpqStudentClass::where('class_id', $class->id)
                ->where('academic_year_id', $activeYear?->id)
                ->count();

            $attendedToday = TpqAttendance::where('class_id', $class->id)
                ->whereDate('date', now()->toDateString())
                ->count();

            return [
                'id' => $class->id,
                'name' => $class->name,
                'student_count' => $studentCount,
                'attendance_submitted_today' => $attendedToday > 0,
            ];
        })->values();

        return response()->json([
            'stats' => [
                'totalClasses' => $classes->count(),
                'totalStudents' => $totalStudents,
                'presentToday' => $presentToday,
            ],
            'classes' => $myClasses,
        ]);
    }
}
