<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqGrade;
use App\Models\TpqSemester;
use App\Models\TpqStudentClass;
use App\Models\TpqSubject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TpqGradeController extends Controller
{
    public function index(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;

        return Inertia::render('Learning/Tpq/Grades/Index', [
            'classes' => TpqClass::where('masjid_id', $masjidId)->where('is_active', true)->orderBy('order')->get(['id', 'name']),
            'semesters' => TpqSemester::whereHas('academicYear', fn ($q) => $q->where('masjid_id', $masjidId))->orderByDesc('start_date')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, TpqClass $class, TpqSemester $semester): Response
    {
        $this->authorizeSameMasjid($request, $class);
        $this->authorizeSameMasjid($request, $semester);

        $masjidId = $request->user()->masjid_id;
        $activeYear = TpqAcademicYear::where('masjid_id', $masjidId)->where('is_active', true)->first();

        $students = TpqStudentClass::with('student:id,name,nis')
            ->where('class_id', $class->id)
            ->where('academic_year_id', $activeYear?->id)
            ->get()
            ->pluck('student')
            ->filter()
            ->values();

        $subjects = TpqSubject::where('masjid_id', $masjidId)->where('is_active', true)->orderBy('order')->get();

        $grades = TpqGrade::where('class_id', $class->id)
            ->where('semester_id', $semester->id)
            ->get();

        return Inertia::render('Learning/Tpq/Grades/Show', [
            'class' => $class->only(['id', 'name']),
            'semester' => $semester->only(['id', 'name']),
            'students' => $students,
            'subjects' => $subjects,
            'grades' => $grades->map(fn ($g) => [
                'student_id' => $g->student_id,
                'subject_id' => $g->subject_id,
                'score' => $g->score,
                'description' => $g->description,
            ]),
        ]);
    }

    public function store(Request $request, TpqClass $class, TpqSemester $semester): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $class);
        $this->authorizeSameMasjid($request, $semester);

        $data = $request->validate([
            'grades' => ['required', 'array'],
            'grades.*.student_id' => ['required', 'uuid', 'exists:tpq_students,id'],
            'grades.*.subject_id' => ['required', 'uuid', 'exists:tpq_subjects,id'],
            'grades.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.description' => ['nullable', 'string'],
        ]);

        foreach ($data['grades'] as $item) {
            if ($item['score'] === null) {
                continue;
            }

            TpqGrade::updateOrCreate(
                ['student_id' => $item['student_id'], 'subject_id' => $item['subject_id'], 'semester_id' => $semester->id],
                [
                    'class_id' => $class->id,
                    'score' => $item['score'],
                    'grade_letter' => $this->scoreToLetter((float) $item['score']),
                    'description' => $item['description'] ?? null,
                    'graded_by' => $request->user()->id,
                ]
            );
        }

        return back()->with('success', 'Nilai berhasil disimpan.');
    }

    /**
     * TpqClass/TpqSemester tidak punya global scope tenant — route-model binding
     * cuma cari berdasarkan id, jadi tanpa ini admin tenant A yang tahu/menebak
     * UUID kelas/semester tenant B bisa lihat/isi nilainya lewat URL langsung.
     * TpqSemester sendiri tidak punya kolom masjid_id, kepemilikannya diturunkan
     * dari tahun ajaran induknya.
     */
    private function authorizeSameMasjid(Request $request, TpqClass|TpqSemester $model): void
    {
        $masjidId = $model instanceof TpqSemester
            ? $model->academicYear?->masjid_id
            : $model->masjid_id;

        abort_unless($masjidId === $request->user()->masjid_id, 404);
    }

    private function scoreToLetter(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            default => 'D',
        };
    }
}
