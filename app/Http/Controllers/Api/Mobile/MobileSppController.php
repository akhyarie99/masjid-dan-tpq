<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ScopesTeacherClasses;
use App\Http\Controllers\Controller;
use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqSppBill;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileSppController extends Controller
{
    use ScopesTeacherClasses;

    public function kelasSpp(Request $request, TpqClass $class): JsonResponse
    {
        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();

        if (! $this->assertClassAccessible($request->user(), $class, $activeYear?->id)) {
            return response()->json(['message' => 'Anda tidak mengampu kelas ini.'], 403);
        }

        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;

        $students = TpqStudentClass::with('student:id,name,nis')
            ->where('class_id', $class->id)
            ->where('academic_year_id', $activeYear?->id)
            ->get()
            ->pluck('student')
            ->filter();

        $bills = TpqSppBill::whereIn('student_id', $students->pluck('id'))
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('student_id');

        $data = $students->map(fn ($student) => [
            'student' => ['id' => $student->id, 'name' => $student->name, 'nis' => $student->nis],
            'bill' => $bills->get($student->id)?->only(['id', 'amount', 'paid_amount', 'status', 'is_scholarship']),
        ])->values();

        return response()->json(['month' => (int) $month, 'year' => (int) $year, 'students' => $data]);
    }

    public function santriSpp(Request $request, TpqStudent $student): JsonResponse
    {
        if ($student->masjid_id !== $request->user()->masjid_id) {
            return response()->json(['message' => 'Santri tidak ditemukan di masjid Anda.'], 403);
        }

        $bills = TpqSppBill::where('student_id', $student->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get(['id', 'year', 'month', 'amount', 'paid_amount', 'status', 'is_scholarship']);

        return response()->json([
            'student' => $student->only(['id', 'name', 'nis']),
            'bills' => $bills,
        ]);
    }
}
