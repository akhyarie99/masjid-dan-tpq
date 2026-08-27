<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Models\TpqAcademicYear;
use App\Models\TpqAttendance;
use App\Models\TpqClass;
use App\Models\TpqStudentClass;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TpqAttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Learning/Tpq/Attendance/Index', [
            'classes' => TpqClass::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->orderBy('order')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, TpqClass $class): Response
    {
        $this->authorizeSameMasjid($request, $class);

        $date = $request->string('date')->toString() ?: now()->toDateString();
        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();

        $students = TpqStudentClass::with('student:id,name,nis,photo')
            ->where('class_id', $class->id)
            ->where('academic_year_id', $activeYear?->id)
            ->get()
            ->pluck('student')
            ->filter();

        $attendances = TpqAttendance::where('class_id', $class->id)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('student_id');

        return Inertia::render('Learning/Tpq/Attendance/Show', [
            'class' => $class->only(['id', 'name']),
            'date' => $date,
            'students' => $students->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'photo' => $student->photo,
                'status' => $attendances->get($student->id)?->status,
                'notes' => $attendances->get($student->id)?->notes,
            ])->values(),
        ]);
    }

    public function store(Request $request, TpqClass $class): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $class);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'attendances' => ['required', 'array'],
            'attendances.*.student_id' => ['required', 'uuid', 'exists:tpq_students,id'],
            'attendances.*.status' => ['required', 'in:hadir,izin,sakit,alfa'],
            'attendances.*.notes' => ['nullable', 'string'],
        ]);

        foreach ($data['attendances'] as $item) {
            $existing = TpqAttendance::where('student_id', $item['student_id'])
                ->whereDate('date', $data['date'])
                ->first();

            $values = [
                'class_id' => $class->id,
                'status' => $item['status'],
                'notes' => $item['notes'] ?? null,
                'recorded_by' => $request->user()->id,
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
        }

        return back()->with('success', 'Absensi berhasil disimpan.');
    }

    public function recap(Request $request, TpqClass $class): Response
    {
        $this->authorizeSameMasjid($request, $class);

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
                'days' => $records->mapWithKeys(fn ($r) => [$r->date->toDateString() => $r->status]),
                'present_count' => $presentCount,
                'percent' => $totalDays > 0 ? round(($presentCount / $totalDays) * 100, 1) : 0,
            ];
        });

        return Inertia::render('Learning/Tpq/Attendance/Recap', [
            'class' => $class->only(['id', 'name']),
            'month' => (int) $month,
            'year' => (int) $year,
            'recap' => $recap->values(),
        ]);
    }

    public function exportRecapPdf(Request $request, TpqClass $class): \Illuminate\Http\Response
    {
        $this->authorizeSameMasjid($request, $class);

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
                'student' => $student,
                'present' => $presentCount,
                'sick' => $records->where('status', 'sakit')->count(),
                'permission' => $records->where('status', 'izin')->count(),
                'absent' => $records->where('status', 'alfa')->count(),
                'percent' => $totalDays > 0 ? round(($presentCount / $totalDays) * 100, 1) : 0,
            ];
        });

        $pdf = Pdf::loadView('pdf.tpq-attendance-recap', [
            'masjid' => $request->user()->masjid,
            'class' => $class,
            'month' => $start,
            'recap' => $recap,
        ]);

        return $pdf->download("rekap-absensi-{$class->name}-{$start->format('Ym')}.pdf");
    }

    /**
     * TpqClass tidak punya global scope tenant — route-model binding cuma cari
     * berdasarkan id, jadi tanpa ini admin tenant A yang tahu/menebak UUID kelas
     * tenant B bisa lihat/isi absensinya lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, TpqClass $class): void
    {
        abort_unless($class->masjid_id === $request->user()->masjid_id, 404);
    }
}
