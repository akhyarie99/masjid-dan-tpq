<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ScopesTeacherClasses;
use App\Http\Controllers\Controller;
use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqGrade;
use App\Models\TpqHafalanProgress;
use App\Models\TpqSemester;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use App\Models\TpqSubject;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileCapaianController extends Controller
{
    use ScopesTeacherClasses;

    public function santriList(Request $request, TpqClass $class): JsonResponse
    {
        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();

        if (! $this->assertClassAccessible($request->user(), $class, $activeYear?->id)) {
            return response()->json(['message' => 'Anda tidak mengampu kelas ini.'], 403);
        }

        $students = TpqStudentClass::with('student:id,name,nis,photo')
            ->where('class_id', $class->id)
            ->where('academic_year_id', $activeYear?->id)
            ->get()
            ->pluck('student')
            ->filter()
            ->values();

        return response()->json(['students' => $students]);
    }

    public function detail(Request $request, TpqStudent $student): JsonResponse
    {
        if (! $this->assertStudentAccessible($request, $student)) {
            return response()->json(['message' => 'Santri tidak ditemukan di masjid Anda.'], 403);
        }

        $activeSemester = TpqSemester::whereHas('academicYear', fn ($q) => $q->where('masjid_id', $request->user()->masjid_id))
            ->where('is_active', true)
            ->first();

        $grades = TpqGrade::with('subject:id,name')
            ->where('student_id', $student->id)
            ->when($activeSemester, fn ($q) => $q->where('semester_id', $activeSemester->id))
            ->get()
            ->map(fn (TpqGrade $grade) => [
                'subject_id' => $grade->subject_id,
                'subject' => $grade->subject?->name,
                'score' => $grade->score,
                'grade_letter' => $grade->grade_letter,
                'description' => $grade->description,
            ]);

        $subjects = TpqSubject::where('masjid_id', $request->user()->masjid_id)
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['id', 'name']);

        return response()->json([
            'student' => $student->only(['id', 'name', 'nis', 'photo']),
            'active_semester' => $activeSemester?->only(['id', 'name']),
            'subjects' => $subjects,
            'grades' => $grades,
        ]);
    }

    public function hafalan(Request $request, TpqStudent $student): JsonResponse
    {
        if (! $this->assertStudentAccessible($request, $student)) {
            return response()->json(['message' => 'Santri tidak ditemukan di masjid Anda.'], 403);
        }

        $hafalan = TpqHafalanProgress::where('student_id', $student->id)
            ->orderBy('surah_number')
            ->get();

        return response()->json(['hafalan' => $hafalan]);
    }

    public function inputNilai(Request $request, TpqStudent $student): JsonResponse
    {
        if (! $this->assertStudentAccessible($request, $student)) {
            return response()->json(['message' => 'Santri tidak ditemukan di masjid Anda.'], 403);
        }

        $data = $request->validate([
            'class_id' => ['required', 'uuid', 'exists:tpq_classes,id'],
            'subject_id' => ['required', 'uuid', 'exists:tpq_subjects,id'],
            'semester_id' => ['required', 'uuid', 'exists:tpq_semesters,id'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $grade = TpqGrade::updateOrCreate(
            ['student_id' => $student->id, 'subject_id' => $data['subject_id'], 'semester_id' => $data['semester_id']],
            [
                'class_id' => $data['class_id'],
                'score' => $data['score'],
                'grade_letter' => $this->scoreToLetter((float) $data['score']),
                'description' => $data['description'] ?? null,
                'graded_by' => $request->user()->id,
            ]
        );

        $subject = TpqSubject::find($data['subject_id']);
        app(FcmService::class)->notifyCapaianUpdated($student, $request->user(), $subject?->name ?? 'mapel');

        return response()->json(['message' => 'Nilai berhasil disimpan.', 'grade' => $grade]);
    }

    public function updateHafalan(Request $request, TpqStudent $student): JsonResponse
    {
        if (! $this->assertStudentAccessible($request, $student)) {
            return response()->json(['message' => 'Santri tidak ditemukan di masjid Anda.'], 403);
        }

        $data = $request->validate([
            'surah_number' => ['required', 'integer', 'min:1', 'max:114'],
            'surah_name' => ['required', 'string'],
            'total_ayah' => ['required', 'integer', 'min:1'],
            'memorized_ayah' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:belum,sedang,hafal'],
        ]);

        $progress = TpqHafalanProgress::updateOrCreate(
            ['student_id' => $student->id, 'surah_number' => $data['surah_number']],
            [
                'surah_name' => $data['surah_name'],
                'total_ayah' => $data['total_ayah'],
                'memorized_ayah' => $data['memorized_ayah'],
                'status' => $data['status'],
                'memorized_date' => $data['status'] === 'hafal' ? now()->toDateString() : null,
                'verified_by' => $request->user()->id,
            ]
        );

        return response()->json(['message' => 'Hafalan berhasil diperbarui.', 'hafalan' => $progress]);
    }

    private function assertStudentAccessible(Request $request, TpqStudent $student): bool
    {
        return $student->masjid_id === $request->user()->masjid_id;
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
