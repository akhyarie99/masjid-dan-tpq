<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ScopesTeacherClasses;
use App\Http\Controllers\Controller;
use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqDailyProgress;
use App\Models\TpqGrade;
use App\Models\TpqHafalanProgress;
use App\Models\TpqSemester;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use App\Models\TpqSubject;
use App\Services\FcmService;
use App\Services\GuardianNotifier;
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

    /**
     * Cari santri lintas kelas (nama/NIS) atau resolve hasil scan QR kartu santri
     * (isi QR = student id, lihat TpqStudentController::card) — dipakai supaya
     * ustadz tidak perlu pilih kelas dulu buat cari satu anak di antara ratusan.
     */
    public function searchStudents(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));

        if ($query === '') {
            return response()->json(['students' => []]);
        }

        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();

        $students = TpqStudent::where('masjid_id', $request->user()->masjid_id)
            ->where('status', 'aktif')
            ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('nis', 'like', "%{$query}%"))
            ->with(['studentClasses' => fn ($q) => $q->where('academic_year_id', $activeYear?->id)->with('class:id,name')])
            ->limit(10)
            ->get()
            ->map(fn (TpqStudent $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'nis' => $s->nis,
                'photo' => $s->photo,
                'class_id' => $s->studentClasses->first()?->class_id,
                'class_name' => $s->studentClasses->first()?->class?->name,
            ]);

        return response()->json(['students' => $students]);
    }

    public function findStudent(Request $request, TpqStudent $student): JsonResponse
    {
        if (! $this->assertStudentAccessible($request, $student)) {
            return response()->json(['message' => 'Santri tidak ditemukan di masjid Anda.'], 403);
        }

        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();

        $classData = TpqStudentClass::with('class:id,name')
            ->where('student_id', $student->id)
            ->where('academic_year_id', $activeYear?->id)
            ->first();

        return response()->json([
            'id' => $student->id,
            'name' => $student->name,
            'nis' => $student->nis,
            'photo' => $student->photo,
            'class_id' => $classData?->class_id,
            'class_name' => $classData?->class?->name,
        ]);
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

    public function dailyProgress(Request $request, TpqStudent $student): JsonResponse
    {
        if (! $this->assertStudentAccessible($request, $student)) {
            return response()->json(['message' => 'Santri tidak ditemukan di masjid Anda.'], 403);
        }

        $entries = TpqDailyProgress::where('student_id', $student->id)
            ->orderByDesc('date')
            ->limit(20)
            ->get()
            ->map(fn (TpqDailyProgress $p) => [
                'id' => $p->id,
                'date' => $p->date->toDateString(),
                'method' => $p->method,
                'jilid' => $p->jilid,
                'halaman' => $p->halaman,
                'surah' => $p->surah,
                'ayat_awal' => $p->ayat_awal,
                'ayat_akhir' => $p->ayat_akhir,
                'keterangan' => $p->keterangan,
                'catatan' => $p->catatan,
                'summary' => $p->summary(),
            ]);

        return response()->json(['daily_progress' => $entries]);
    }

    public function inputDailyProgress(Request $request, TpqStudent $student): JsonResponse
    {
        if (! $this->assertStudentAccessible($request, $student)) {
            return response()->json(['message' => 'Santri tidak ditemukan di masjid Anda.'], 403);
        }

        $data = $request->validate([
            'class_id' => ['required', 'uuid', 'exists:tpq_classes,id'],
            'date' => ['required', 'date'],
            'method' => ['required', 'in:iqro,quran'],
            'jilid' => ['nullable', 'integer', 'min:1', 'max:6', 'required_if:method,iqro'],
            'halaman' => ['nullable', 'integer', 'min:1'],
            'surah' => ['nullable', 'string', 'max:255', 'required_if:method,quran'],
            'ayat_awal' => ['nullable', 'integer', 'min:1'],
            'ayat_akhir' => ['nullable', 'integer', 'min:1'],
            'keterangan' => ['required', 'in:lancar,ulang'],
            'catatan' => ['nullable', 'string'],
        ]);

        $isNew = ! TpqDailyProgress::where('student_id', $student->id)->whereDate('date', $data['date'])->exists();

        $progress = TpqDailyProgress::updateOrCreate(
            ['student_id' => $student->id, 'date' => $data['date']],
            [
                'class_id' => $data['class_id'],
                'method' => $data['method'],
                'jilid' => $data['method'] === 'iqro' ? ($data['jilid'] ?? null) : null,
                'halaman' => $data['halaman'] ?? null,
                'surah' => $data['method'] === 'quran' ? ($data['surah'] ?? null) : null,
                'ayat_awal' => $data['method'] === 'quran' ? ($data['ayat_awal'] ?? null) : null,
                'ayat_akhir' => $data['method'] === 'quran' ? ($data['ayat_akhir'] ?? null) : null,
                'keterangan' => $data['keterangan'],
                'catatan' => $data['catatan'] ?? null,
                'recorded_by' => $request->user()->id,
            ],
        );

        if ($isNew) {
            app(GuardianNotifier::class)->notify(
                $student,
                "Assalamu'alaikum, Ananda {$student->name} hari ini mengaji: {$progress->summary()} ({$progress->keterangan}). Barakallahu fiik."
            );
            $progress->update(['notified_at' => now()]);
        }

        return response()->json(['message' => 'Progres mengaji berhasil disimpan.', 'daily_progress' => $progress]);
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
